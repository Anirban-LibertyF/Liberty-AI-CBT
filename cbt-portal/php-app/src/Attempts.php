<?php
declare(strict_types=1);

/** A candidate's sitting of an exam: start, autosave, server-side clock, scoring. */
final class Attempts
{
    public const STATES = ['NOT_VISITED', 'NOT_ANSWERED', 'ANSWERED', 'MARKED_FOR_REVIEW', 'ANSWERED_AND_MARKED'];
    /** Longest slice of time one request may credit to a question (heartbeats come every ~15s). */
    private const MAX_TIME_DELTA = 300;
    /** Allowed lateness for the final save when the clock runs out. */
    private const GRACE_SECONDS = 15;

    public static function start(int $examId): int
    {
        $exam = Exams::find($examId);
        if (!$exam || $exam['status'] !== 'ready') {
            throw new AppError('That test is not available.', 404);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO attempts (exam_id, started_at) VALUES (?, UTC_TIMESTAMP())")->execute([$examId]);
            $attemptId = (int) $pdo->lastInsertId();
            // Question 1 is visited immediately (NOT_ANSWERED); the rest start NOT_VISITED.
            $pdo->prepare(
                "INSERT INTO attempt_answers (attempt_id, question_id, cbt_state)
                 SELECT ?, id, IF(position = (SELECT MIN(position) FROM questions WHERE exam_id = ?), 'NOT_ANSWERED', 'NOT_VISITED')
                   FROM questions WHERE exam_id = ?"
            )->execute([$attemptId, $examId, $examId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $attemptId;
    }

    /** Attempt joined with its exam, plus remaining seconds. */
    public static function load(int $attemptId): array
    {
        // Clock maths happens in PHP: doing it in SQL trips "BIGINT UNSIGNED out of range"
        // exactly when the exam is over and the difference goes negative.
        $st = db()->prepare(
            'SELECT a.*, e.title, e.duration_minutes, e.positive_marks, e.negative_marks,
                    UNIX_TIMESTAMP(UTC_TIMESTAMP()) AS now_ts, UNIX_TIMESTAMP(a.started_at) AS start_ts
               FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = ?'
        );
        $st->execute([$attemptId]);
        $row = $st->fetch();
        if (!$row) {
            throw new AppError('Attempt not found.', 404);
        }
        $deadline = (int) $row['start_ts'] + (int) $row['duration_minutes'] * 60;
        $row['remaining'] = max(0, $deadline - (int) $row['now_ts']);
        $row['past_deadline'] = max(0, (int) $row['now_ts'] - $deadline);
        return $row;
    }

    /** Loads an attempt this session owns, auto-submitting it if the clock has run out. */
    public static function loadOwned(int $attemptId): array
    {
        if (!owns('attempt', $attemptId)) {
            throw new AppError('Attempt not found.', 404);
        }
        $a = self::load($attemptId);
        if ($a['status'] === 'in_progress' && $a['remaining'] === 0) {
            self::submit($attemptId);
            $a = self::load($attemptId);
        }
        return $a;
    }

    /** Question list for the exam hall. The answer key is deliberately NOT included. */
    public static function examPayload(int $attemptId, int $examId): array
    {
        $st = db()->prepare(
            'SELECT q.id, q.position, q.section, q.topic, q.difficulty, q.question_text, q.has_diagram, q.svg_diagram,
                    aa.selected_option, aa.cbt_state, aa.time_spent_seconds
               FROM questions q JOIN attempt_answers aa ON aa.question_id = q.id AND aa.attempt_id = ?
              WHERE q.exam_id = ? ORDER BY q.position'
        );
        $st->execute([$attemptId, $examId]);
        $rows = $st->fetchAll();
        $options = self::optionsFor(array_column($rows, 'id'));

        return array_map(fn ($r) => [
            'id'       => (int) $r['id'],
            'number'   => (int) $r['position'],
            'section'  => $r['section'],
            'topic'    => $r['topic'],
            'difficulty' => $r['difficulty'],
            'hasDiagram' => (bool) $r['has_diagram'] && Svg::sanitize($r['svg_diagram']) !== null,
            'svg'      => $r['has_diagram'] ? Svg::sanitize($r['svg_diagram']) : null,
            'html'     => MathRenderer::render($r['question_text']),
            'options'  => array_map(fn ($o) => ['id' => $o['label'], 'html' => MathRenderer::render($o['option_text'])], $options[$r['id']] ?? []),
            'selected' => $r['selected_option'],
            'state'    => $r['cbt_state'],
            'time'     => (int) $r['time_spent_seconds'],
        ], $rows);
    }

    /** @return array<int,array<int,array{label:string,option_text:string}>> */
    private static function optionsFor(array $questionIds): array
    {
        if (!$questionIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($questionIds), '?'));
        $st = db()->prepare("SELECT question_id, label, option_text FROM question_options WHERE question_id IN ($in) ORDER BY question_id, label");
        $st->execute(array_map('intval', $questionIds));
        $out = [];
        foreach ($st->fetchAll() as $o) {
            $out[(int) $o['question_id']][] = $o;
        }
        return $out;
    }

    // ------------------------------------------------------------ autosave

    /**
     * Applies one exam-hall action.
     *   save   Save & Next          -> ANSWERED / NOT_ANSWERED
     *   review Mark for Review      -> ANSWERED_AND_MARKED / MARKED_FOR_REVIEW
     *   clear  Clear Response       -> NOT_ANSWERED, no selection
     *   tick   heartbeat / leaving  -> only credits time
     * `time_delta` is credited to question_id; `visit_id` (if any) flips NOT_VISITED -> NOT_ANSWERED.
     *
     * @return int seconds remaining
     */
    public static function save(int $attemptId, array $in): int
    {
        $a = self::load($attemptId);
        if ($a['status'] !== 'in_progress') {
            throw new AppError('This exam has already been submitted.', 409);
        }
        if ($a['past_deadline'] > self::GRACE_SECONDS) {
            self::submit($attemptId);
            throw new AppError('Time is up. Your exam was submitted.', 409);
        }

        $action = (string) ($in['action'] ?? 'tick');
        if (!in_array($action, ['save', 'review', 'clear', 'tick'], true)) {
            throw new AppError('Unknown action.');
        }
        $questionId = (int) ($in['question_id'] ?? 0);
        $selected = $in['selected'] ?? null;
        if ($selected !== null && !in_array($selected, ['A', 'B', 'C', 'D'], true)) {
            throw new AppError('Invalid option.');
        }
        $delta = max(0, min(self::MAX_TIME_DELTA, (int) ($in['time_delta'] ?? 0)));

        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($questionId > 0) {
                $state = match ($action) {
                    'save'   => $selected ? 'ANSWERED' : 'NOT_ANSWERED',
                    'review' => $selected ? 'ANSWERED_AND_MARKED' : 'MARKED_FOR_REVIEW',
                    'clear'  => 'NOT_ANSWERED',
                    default  => null,
                };
                if ($action === 'clear') {
                    $selected = null;
                }
                $sql = 'UPDATE attempt_answers SET time_spent_seconds = time_spent_seconds + ?';
                $args = [$delta];
                if ($state !== null) {
                    $sql .= ', cbt_state = ?, selected_option = ?';
                    array_push($args, $state, $selected);
                }
                $sql .= ' WHERE attempt_id = ? AND question_id = ?';
                array_push($args, $attemptId, $questionId);
                $upd = $pdo->prepare($sql);
                $upd->execute($args);
                if ($upd->rowCount() === 0 && $state !== null) {
                    $chk = $pdo->prepare('SELECT 1 FROM attempt_answers WHERE attempt_id = ? AND question_id = ?');
                    $chk->execute([$attemptId, $questionId]);
                    if (!$chk->fetchColumn()) {
                        throw new AppError('Unknown question.');
                    }
                }
            }
            $visit = (int) ($in['visit_id'] ?? 0);
            if ($visit > 0) {
                $pdo->prepare(
                    "UPDATE attempt_answers SET cbt_state = 'NOT_ANSWERED'
                      WHERE attempt_id = ? AND question_id = ? AND cbt_state = 'NOT_VISITED'"
                )->execute([$attemptId, $visit]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $a['remaining'];
    }

    // ------------------------------------------------------------ submit / score

    /** Locks the attempt and stores its score. Idempotent. */
    public static function submit(int $attemptId): void
    {
        $a = self::load($attemptId);
        if ($a['status'] === 'submitted') {
            return;
        }
        $stats = self::analytics($attemptId);
        db()->prepare(
            "UPDATE attempts SET status = 'submitted', submitted_at = UTC_TIMESTAMP(),
                    correct_count = ?, incorrect_count = ?, unattempted_count = ?, total_score = ?, max_score = ?
              WHERE id = ? AND status = 'in_progress'"
        )->execute([
            $stats['correctCount'], $stats['incorrectCount'], $stats['unattemptedCount'],
            $stats['totalScore'], $stats['maxScore'], $attemptId,
        ]);
    }

    /** Submitted attempts from this browser session's own list, most recent first. */
    public static function historyForSession(array $attemptIds): array
    {
        $attemptIds = array_values(array_unique(array_map('intval', $attemptIds)));
        if (!$attemptIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($attemptIds), '?'));
        $st = db()->prepare(
            "SELECT a.id, a.exam_id, a.submitted_at, a.correct_count, a.incorrect_count, a.unattempted_count,
                    a.total_score, a.max_score, e.title
               FROM attempts a JOIN exams e ON e.id = a.exam_id
              WHERE a.id IN ($in) AND a.status = 'submitted'
              ORDER BY a.submitted_at DESC"
        );
        $st->execute($attemptIds);
        return $st->fetchAll();
    }

    /** Raw per-question rows including the answer key. Result page only. */
    public static function reviewRows(int $attemptId): array
    {
        $st = db()->prepare(
            'SELECT q.id, q.position, q.section, q.topic, q.difficulty, q.question_text, q.has_diagram, q.svg_diagram,
                    q.explanation, q.correct_option, aa.selected_option, aa.cbt_state, aa.time_spent_seconds
               FROM attempts a
               JOIN questions q ON q.exam_id = a.exam_id
               JOIN attempt_answers aa ON aa.attempt_id = a.id AND aa.question_id = q.id
              WHERE a.id = ? ORDER BY q.position'
        );
        $st->execute([$attemptId]);
        $rows = $st->fetchAll();
        $options = self::optionsFor(array_column($rows, 'id'));
        foreach ($rows as &$r) {
            $r['options'] = $options[$r['id']] ?? [];
            // Both ANSWERED and ANSWERED_AND_MARKED are evaluated, as in TCS iON / NTA exams.
            $r['attempted'] = in_array($r['cbt_state'], ['ANSWERED', 'ANSWERED_AND_MARKED'], true) && $r['selected_option'] !== null;
            $r['correct'] = $r['attempted'] && $r['selected_option'] === $r['correct_option'];
        }
        return $rows;
    }

    public static function analytics(int $attemptId): array
    {
        $a = self::load($attemptId);
        $pos = (float) $a['positive_marks'];
        $neg = (float) $a['negative_marks'];

        $sections = [];
        $topics = [];
        $correct = $incorrect = $unattempted = $time = 0;

        $rows = self::reviewRows($attemptId);
        foreach ($rows as $q) {
            $time += (int) $q['time_spent_seconds'];
            $sec = &$sections[$q['section']];
            $sec ??= ['section' => $q['section'], 'total' => 0, 'attempted' => 0, 'correct' => 0, 'incorrect' => 0, 'score' => 0.0, 'timeSpent' => 0];
            $top = &$topics[$q['topic']];
            $top ??= ['topic' => $q['topic'], 'total' => 0, 'correct' => 0, 'incorrect' => 0, 'timeSpent' => 0];

            $sec['total']++;
            $top['total']++;
            $sec['timeSpent'] += (int) $q['time_spent_seconds'];
            $top['timeSpent'] += (int) $q['time_spent_seconds'];

            if (!$q['attempted']) {
                $unattempted++;
            } else {
                $sec['attempted']++;
                if ($q['correct']) {
                    $correct++;
                    $sec['correct']++;
                    $top['correct']++;
                    $sec['score'] += $pos;
                } else {
                    $incorrect++;
                    $sec['incorrect']++;
                    $top['incorrect']++;
                    $sec['score'] -= $neg;
                }
            }
            unset($sec, $top);
        }

        $total = count($rows);
        $maxScore = $total * $pos;
        $totalScore = round($correct * $pos - $incorrect * $neg, 2);
        $attempted = $correct + $incorrect;
        $scorePct = $maxScore > 0 ? max(0, $totalScore / $maxScore * 100) : 0;

        return [
            'totalQuestions'     => $total,
            'attemptedCount'     => $attempted,
            'correctCount'       => $correct,
            'incorrectCount'     => $incorrect,
            'unattemptedCount'   => $unattempted,
            'totalScore'         => $totalScore,
            'maxScore'           => $maxScore,
            'accuracy'           => $attempted > 0 ? (int) round($correct / $attempted * 100) : 0,
            'totalTimeSpent'     => $time,
            // Rough percentile curve, same formula as the original app (an estimate, not real ranking data).
            'estimatedPercentile' => min(99.8, max(12.5, round(35 + $scorePct * 0.64, 1))),
            'sectionBreakdown'   => array_map(fn ($s) => $s + ['score' => round($s['score'], 2)], array_values($sections)),
            'topicBreakdown'     => array_values($topics),
        ];
    }
}
