<?php
declare(strict_types=1);

/**
 * Keeps each AI-generated paper different from the ones before it.
 *  - history():   earlier questions of the same section, plus recently used diagrams
 *  - similarity(): word-overlap score used to spot a question that is a copy or a light rewording
 *  - repeatQuota(): how many near-repeats one paper may contain (at most 10, and about a quarter)
 */
final class Freshness
{
    /** Two questions this similar (Jaccard on content words) count as the same question. */
    public const SAME = 0.6;
    private const HISTORY_ROWS = 400;
    public const MAX_REPEATS = 10;

    private const STOP = ['the', 'a', 'an', 'of', 'is', 'are', 'in', 'to', 'and', 'or', 'for', 'on', 'by', 'with', 'what', 'which', 'following',
        'that', 'this', 'it', 'as', 'be', 'if', 'then', 'from', 'at', 'its', 'was', 'were', 'has', 'have', 'how', 'many', 'much', 'value',
        'correct', 'statement', 'shown', 'given', 'find', 'identify', 'below', 'above', 'frac', 'text', 'sqrt'];

    /** @return array<string,true> content words of a question stem */
    public static function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/\\\\[a-z]+/u', ' ', $text) ?? $text; // \frac, \sqrt ... markup
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $set = [];
        foreach ($words as $w) {
            if (!in_array($w, self::STOP, true)) {
                $set[$w] = true;
            }
        }
        return $set;
    }

    public static function similarity(array $a, array $b): float
    {
        if (!$a || !$b) {
            return 0.0;
        }
        $inter = count(array_intersect_key($a, $b));
        $union = count($a) + count($b) - $inter;
        return $union > 0 ? $inter / $union : 0.0;
    }

    /** Highest similarity of $set against a list of token sets. */
    public static function maxSimilarity(array $set, array $others): float
    {
        $max = 0.0;
        foreach ($others as $o) {
            $max = max($max, self::similarity($set, $o));
        }
        return $max;
    }

    /**
     * What was generated before for this section (any earlier paper, built-in or AI), newest first.
     *
     * @return array{stems:string[],sets:array<int,array<string,true>>,diagrams:string[]}
     */
    public static function history(string $section, int $currentExamId): array
    {
        $st = db()->prepare(
            'SELECT question_text FROM questions WHERE section = ? AND exam_id <> ? ORDER BY id DESC LIMIT ' . self::HISTORY_ROWS
        );
        $st->execute([$section, $currentExamId]);
        $stems = $st->fetchAll(PDO::FETCH_COLUMN);

        $diagrams = [];
        if (self::hasDiagramKey()) {
            $d = db()->query('SELECT diagram_key FROM questions WHERE diagram_key IS NOT NULL ORDER BY id DESC LIMIT 400')->fetchAll(PDO::FETCH_COLUMN);
            $diagrams = array_slice(array_values(array_unique($d)), 0, 25);
        }
        return ['stems' => $stems, 'sets' => array_map([self::class, 'tokens'], $stems), 'diagrams' => $diagrams];
    }

    /** Questions already stored in this exam by sibling batches (so batches don't repeat each other). */
    public static function siblingSets(int $examId): array
    {
        $st = db()->prepare('SELECT question_text FROM questions WHERE exam_id = ?');
        $st->execute([$examId]);
        return array_map([self::class, 'tokens'], $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** How many times each diagram already appears in this paper (written by sibling batches). @return array<string,int> */
    public static function siblingKeys(int $examId): array
    {
        if (!self::hasDiagramKey()) {
            return [];
        }
        $st = db()->prepare('SELECT diagram_key, COUNT(*) FROM questions WHERE exam_id = ? AND diagram_key IS NOT NULL GROUP BY diagram_key');
        $st->execute([$examId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_KEY_PAIR));
    }

    /** Near-repeats this batch may keep: the paper-wide allowance shared out by batch size. */
    public static function repeatQuota(int $examId, int $batchCount): int
    {
        $st = db()->prepare('SELECT COALESCE(SUM(question_count), 0) FROM exam_batches WHERE exam_id = ?');
        $st->execute([$examId]);
        $total = max(1, (int) $st->fetchColumn());
        $allowed = min(self::MAX_REPEATS, intdiv($total, 4));
        return (int) floor($allowed * $batchCount / $total);
    }

    /** Short identity of a question's diagram, remembered so later papers can pick different ones. */
    public static function diagramKey(?array $spec): ?string
    {
        $kind = (string) ($spec['kind'] ?? '');
        return match ($kind) {
            ''                    => null,
            'none'                => null,
            'catalog'             => 'catalog:' . ($spec['diagramId'] ?? '?'),
            'punnett'             => 'punnett:' . ($spec['parent1'] ?? '') . 'x' . ($spec['parent2'] ?? ''),
            'chart'               => 'chart:' . ($spec['chartType'] ?? '') . ':' . mb_substr((string) ($spec['title'] ?? ''), 0, 40),
            'pyramid', 'pedigree' => $kind . ':' . substr(md5(json_encode($spec)), 0, 6),
            default               => $kind . ':' . substr(md5(json_encode($spec)), 0, 6),
        };
    }

    /** True once the diagram_key column (database/migrations) exists. */
    public static function hasDiagramKey(): bool
    {
        static $has = null;
        if ($has === null) {
            $has = (bool) db()->query("SHOW COLUMNS FROM questions LIKE 'diagram_key'")->fetch();
        }
        return $has;
    }

    /**
     * A random menu of catalog diagrams for one batch, preferring ones that were NOT used recently, so the
     * model can't keep reaching for the same favourites.
     *
     * @param string[] $recentKeys e.g. "catalog:human_heart"
     * @return string[] diagram ids
     */
    public static function catalogMenu(array $recentKeys, int $size = 7): array
    {
        $recent = [];
        foreach ($recentKeys as $k) {
            if (str_starts_with($k, 'catalog:')) {
                $recent[substr($k, 8)] = true;
            }
        }
        $fresh = [];
        $stale = [];
        foreach (array_keys(BioSvg::CATALOG) as $id) {
            isset($recent[$id]) ? $stale[] = $id : $fresh[] = $id;
        }
        shuffle($fresh);
        shuffle($stale);
        return array_slice(array_merge($fresh, $stale), 0, $size);
    }

    /** Prompt text that makes the model write something new. */
    public static function promptHints(array $history, array $avoidStems, string $topics): string
    {
        $topicList = array_values(array_filter(array_map('trim', explode(',', $topics))));
        shuffle($topicList);
        $out = 'FRESHNESS: this paper must be different from earlier papers. Set code ' . bin2hex(random_bytes(3)) . ' (use it to vary your choices). ';
        if ($topicList) {
            $out .= 'Draw the questions from the topics in THIS order of priority, spreading them across different sub-topics: ' . implode('; ', $topicList) . '. ';
        }
        $out .= 'Use new scenarios, numbers, data, organisms, compounds and question styles (direct, application, assertion-reason, numerical, statement-based). ';
        if ($avoidStems) {
            $out .= "\nDo NOT repeat, rephrase or reuse the same concept+numbers of any of these earlier questions:\n";
            foreach (array_slice($avoidStems, 0, 45) as $s) {
                $out .= '- ' . mb_substr(preg_replace('/\s+/u', ' ', $s) ?? $s, 0, 110) . "\n";
            }
        }
        if ($history['diagrams']) {
            $out .= 'Diagrams used in recent papers (prefer DIFFERENT diagrams, charts, crosses and structures this time): ' . implode(', ', $history['diagrams']) . '. ';
        }
        return $out;
    }
}
