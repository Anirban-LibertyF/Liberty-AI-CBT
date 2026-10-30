<?php
declare(strict_types=1);

/** Presets, AI paper generation (planned as parallel batches), and loading papers. */
final class Exams
{
    public const DIFFICULTY_MIXES = [
        'Balanced (30% Easy, 50% Medium, 20% Hard)',
        'Advanced Tier-II Level (10% Easy, 40% Medium, 50% Hard)',
        'Foundation Level (50% Easy, 40% Medium, 10% Hard)',
    ];
    private const MAX_PER_BATCH = 12;

    // ------------------------------------------------------------ presets

    public static function presets(): array
    {
        $presets = db()->query('SELECT * FROM presets ORDER BY sort_order')->fetchAll();
        $sections = db()->query('SELECT * FROM preset_sections ORDER BY preset_id, position')->fetchAll();
        $by = [];
        foreach ($sections as $s) {
            $by[$s['preset_id']][] = [
                'id'     => 's' . $s['id'],
                'name'   => $s['name'],
                'topics' => $s['topics'],
                'weight' => (int) $s['weight'],
            ];
        }
        return array_map(fn ($p) => [
            'id'              => $p['id'],
            'name'            => $p['name'],
            'badge'           => $p['badge'],
            'durationMinutes' => (int) $p['duration_minutes'],
            'positiveMarks'   => (float) $p['positive_marks'],
            'negativeMarks'   => (float) $p['negative_marks'],
            'defaultCount'    => (int) $p['default_count'],
            'sections'        => $by[$p['id']] ?? [],
        ], $presets);
    }

    /** Ready-made papers that ship with the site (source = 'demo'), each with its question count. */
    public static function builtins(): array
    {
        return db()->query(
            "SELECT e.*, (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count
               FROM exams e WHERE e.source = 'demo' AND e.status = 'ready' AND e.slug IS NOT NULL
              ORDER BY e.id"
        )->fetchAll();
    }

    public static function builtin(string $slug): ?array
    {
        foreach (self::builtins() as $b) {
            if ($b['slug'] === $slug) {
                return $b;
            }
        }
        return null;
    }

    public static function demo(): ?array
    {
        return self::builtin('demo');
    }

    public static function find(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM exams WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    // ------------------------------------------------------------ planning

    /**
     * Splits the blueprint into batches: each section gets a share of the total by weight
     * (min 5), and sections bigger than 12 questions are cut into parallel slices.
     *
     * @param array<int,array{name:string,topics:string,weight:int}> $sections
     * @return array<int,array{section:string,topics:string,count:int,part:string}>
     */
    public static function plan(array $sections, int $total): array
    {
        $totalWeight = array_sum(array_map(fn ($s) => $s['weight'] ?: 1, $sections));
        $batches = [];
        $allocated = 0;
        $last = count($sections) - 1;

        foreach (array_values($sections) as $idx => $sec) {
            $sectionCount = $idx === $last
                ? max(5, $total - $allocated)
                : max(5, (int) round(($sec['weight'] ?: 1) / $totalWeight * $total));
            $allocated += $sectionCount;

            $remaining = $sectionCount;
            $part = 1;
            while ($remaining > 0) {
                $take = min(self::MAX_PER_BATCH, $remaining);
                $batches[] = [
                    'section' => $sec['name'],
                    'topics'  => $sec['topics'],
                    'count'   => $take,
                    'part'    => $sectionCount > self::MAX_PER_BATCH ? "Part $part" : 'Full Section',
                ];
                $remaining -= $take;
                $part++;
            }
        }
        return $batches;
    }

    /** Validates the config sent by the browser; returns clean values or throws AppError. */
    public static function validateConfig(array $in): array
    {
        $title = trim((string) ($in['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            throw new AppError('Please give the test a name (up to 200 characters).');
        }
        $num = fn (string $k, float $min, float $max, string $label) =>
            (is_numeric($in[$k] ?? null) && $in[$k] >= $min && $in[$k] <= $max)
                ? (float) $in[$k]
                : throw new AppError("$label must be between $min and $max.");

        $total = (int) $num('total', 5, 120, 'Total questions');
        $duration = (int) $num('duration', 10, 180, 'Duration');
        $positive = $num('positive', 0.25, 20, 'Correct marks');
        $negative = $num('negative', 0, 20, 'Negative marks');

        $mix = (string) ($in['difficulty'] ?? '');
        if (!in_array($mix, self::DIFFICULTY_MIXES, true)) {
            $mix = self::DIFFICULTY_MIXES[0];
        }

        $sections = [];
        foreach (array_slice((array) ($in['sections'] ?? []), 0, 12) as $s) {
            $name = trim((string) ($s['name'] ?? ''));
            if ($name === '') {
                throw new AppError('Every subject needs a name.');
            }
            $weight = (int) ($s['weight'] ?? 25);
            $sections[] = [
                'name'   => mb_substr($name, 0, 120),
                'topics' => mb_substr(trim((string) ($s['topics'] ?? '')), 0, 500),
                'weight' => max(1, min(100, $weight)),
            ];
        }
        if (!$sections) {
            throw new AppError('Add at least one subject.');
        }

        return [
            'title' => $title, 'total' => $total, 'duration' => $duration,
            'positive' => $positive, 'negative' => $negative, 'difficulty' => $mix,
            'diagrams' => !empty($in['include_diagrams']), 'sections' => $sections,
        ];
    }

    /** Creates the exam shell and its batch rows. @return array{exam_id:int,batches:array} */
    public static function createAi(array $cfg): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO exams (title, source, status, duration_minutes, positive_marks, negative_marks, difficulty_mix, include_diagrams)
                 VALUES (?, 'ai', 'generating', ?, ?, ?, ?, ?)"
            )->execute([$cfg['title'], $cfg['duration'], $cfg['positive'], $cfg['negative'], $cfg['difficulty'], (int) $cfg['diagrams']]);
            $examId = (int) $pdo->lastInsertId();

            $ins = $pdo->prepare(
                'INSERT INTO exam_batches (exam_id, section_name, topics, question_count, part_label) VALUES (?, ?, ?, ?, ?)'
            );
            $batches = [];
            foreach (self::plan($cfg['sections'], $cfg['total']) as $b) {
                $ins->execute([$examId, $b['section'], $b['topics'], $b['count'], $b['part']]);
                $batches[] = [
                    'id' => (int) $pdo->lastInsertId(), 'section' => $b['section'], 'topics' => $b['topics'],
                    'count' => $b['count'], 'part' => $b['part'], 'status' => 'pending',
                ];
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return ['exam_id' => $examId, 'batches' => $batches];
    }

    // ------------------------------------------------------------ generation

    /** @param string[] $menu catalog diagram ids the model may use in this call (empty = all) */
    private static function responseSchema(array $menu = []): array
    {
        // Property order matters: the diagram and the worked explanation come BEFORE correctOption,
        // so the model solves the problem first and only then picks a letter.
        $order = ['topic', 'difficulty', 'questionText', 'hasDiagram', 'svgDiagram', 'structure', 'chart', 'pedigree', 'options', 'explanation', 'correctOption'];
        $required = ['topic', 'difficulty', 'questionText', 'hasDiagram', 'svgDiagram', 'structure', 'options', 'explanation', 'correctOption'];
        $letters = ['type' => 'STRING', 'enum' => ['A', 'B', 'C', 'D']];
        $ints = ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']];
        $strings = ['type' => 'ARRAY', 'items' => ['type' => 'STRING']];
        $positioned = ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['position' => ['type' => 'INTEGER'], 'group' => ['type' => 'STRING']], 'required' => ['position', 'group']]];
        $branches = ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['carbon' => ['type' => 'INTEGER'], 'group' => ['type' => 'STRING']], 'required' => ['carbon', 'group']]];
        // the server draws every structure below from these fields; the model never draws
        $molecule = [
            'type' => 'OBJECT',
            'properties' => [
                'kind' => ['type' => 'STRING', 'enum' => ['benzene', 'naphthalene', 'chain', 'ring']],
                'ringSize' => ['type' => 'INTEGER'], 'ringBonds' => $ints, 'ringSubstituents' => $positioned,
                'chainBonds' => $ints, 'chainBranches' => $branches,
            ],
            'required' => ['kind'],
        ];
        $chart = [
            'type' => 'OBJECT',
            'properties' => [
                'chartType' => ['type' => 'STRING', 'enum' => ['bar', 'line', 'pie']],
                'title' => ['type' => 'STRING'], 'xLabel' => ['type' => 'STRING'], 'yLabel' => ['type' => 'STRING'],
                'labels' => $strings,
                'series' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['name' => ['type' => 'STRING'], 'values' => ['type' => 'ARRAY', 'items' => ['type' => 'NUMBER']]], 'required' => ['name', 'values']]],
            ],
            'required' => ['chartType', 'title', 'labels', 'series'],
        ];
        $pedigree = [
            'type' => 'OBJECT',
            'properties' => ['people' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => [
                'id' => ['type' => 'INTEGER'], 'gen' => ['type' => 'INTEGER'], 'sex' => ['type' => 'STRING', 'enum' => ['M', 'F']],
                'affected' => ['type' => 'BOOLEAN'], 'mate' => ['type' => 'INTEGER'], 'father' => ['type' => 'INTEGER'], 'mother' => ['type' => 'INTEGER'],
            ], 'required' => ['id', 'gen', 'sex', 'affected', 'mate', 'father', 'mother']]]],
            'required' => ['people'],
        ];
        $structure = [
            'type' => 'OBJECT',
            'properties' => [
                'kind' => ['type' => 'STRING', 'enum' => ['none', 'benzene', 'naphthalene', 'chain', 'ring', 'compare', 'catalog', 'punnett', 'pyramid', 'flow', 'dna_seq']],
                // chemistry
                'ringSize' => ['type' => 'INTEGER'], 'ringBonds' => $ints, 'ringSubstituents' => $positioned,
                'chainBonds' => $ints, 'chainBranches' => $branches,
                'items' => ['type' => 'ARRAY', 'items' => $molecule],
                // biology
                'diagramId' => ['type' => 'STRING', 'enum' => $menu ?: array_keys(BioSvg::CATALOG)],
                'parent1' => ['type' => 'STRING'], 'parent2' => ['type' => 'STRING'],
                'layout' => ['type' => 'STRING', 'enum' => ['cycle', 'chain']], 'nodes' => $strings, 'edgeLabels' => $strings, 'title' => ['type' => 'STRING'],
                'template' => ['type' => 'STRING'], 'show' => ['type' => 'STRING', 'enum' => ['mrna', 'protein']],
                'levels' => ['type' => 'ARRAY', 'items' => ['type' => 'OBJECT', 'properties' => ['name' => ['type' => 'STRING'], 'value' => ['type' => 'STRING']], 'required' => ['name', 'value']]],
            ],
            'required' => ['kind'],
        ];
        return [
            'type' => 'OBJECT',
            'properties' => [
                'questions' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'topic'        => ['type' => 'STRING'],
                            'difficulty'   => ['type' => 'STRING', 'enum' => ['Easy', 'Medium', 'Hard']],
                            'questionText' => ['type' => 'STRING'],
                            'hasDiagram'   => ['type' => 'BOOLEAN'],
                            'svgDiagram'   => ['type' => 'STRING'],
                            'structure'    => $structure,
                            'chart'        => $chart,
                            'pedigree'     => $pedigree,
                            'options'      => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'properties' => ['id' => $letters, 'text' => ['type' => 'STRING']],
                                    'required' => ['id', 'text'],
                                    'propertyOrdering' => ['id', 'text'],
                                ],
                            ],
                            'explanation'   => ['type' => 'STRING'],
                            'correctOption' => $letters,
                        ],
                        'required' => $required,
                        'propertyOrdering' => $order,
                    ],
                ],
            ],
            'required' => ['questions'],
            'propertyOrdering' => ['questions'],
        ];
    }

    /**
     * Runs one Gemini batch and stores its questions. Safe to call again after a failure.
     *
     * Freshness: the prompt lists earlier questions to avoid, and every question that comes back is
     * compared with the earlier ones. Near-copies are dropped (the model is asked again for the
     * shortfall); only if it still can't fill the batch are a few near-repeats let through, within
     * the paper's allowance (at most 10, about a quarter). Copies inside the same paper are never kept.
     */
    public static function runBatch(int $batchId, int $examId): array
    {
        $pdo = db();
        $st = $pdo->prepare(
            'SELECT b.*, e.title, e.difficulty_mix, e.include_diagrams
               FROM exam_batches b JOIN exams e ON e.id = b.exam_id
              WHERE b.id = ? AND b.exam_id = ?'
        );
        $st->execute([$batchId, $examId]);
        $b = $st->fetch();
        if (!$b) {
            throw new AppError('That part of the test was not found.', 404);
        }
        if ($b['status'] === 'completed') {
            return ['status' => 'completed', 'generated' => (int) $b['generated_count']];
        }

        // Claim the batch so a double-click can't generate it twice.
        $claim = $pdo->prepare("UPDATE exam_batches SET status = 'running' WHERE id = ? AND status IN ('pending','failed')");
        $claim->execute([$batchId]);
        if ($claim->rowCount() === 0) {
            throw new AppError('This part of the test is already being created.', 409);
        }

        // If this request dies (browser gave up, PHP time limit, crash) the batch must not stay
        // "running" forever, or it could never be retried.
        ignore_user_abort(true);
        $finished = false;
        register_shutdown_function(function () use ($batchId, &$finished): void {
            if (!$finished) {
                db()->prepare("UPDATE exam_batches SET status = 'failed' WHERE id = ? AND status = 'running'")->execute([$batchId]);
            }
        });

        try {
            set_time_limit(300);
            $want = (int) $b['question_count'];
            $mode = self::diagramMode($b);
            $history = Freshness::history($b['section_name'], $examId);
            $siblings = Freshness::siblingSets($examId);
            $quota = Freshness::repeatQuota($examId, $want);

            $fresh = [];
            $freshSets = [];
            $repeats = [];
            $held = [];       // fresh questions held back only because their picture was overused
            $newStems = [];
            // Picture variety: a catalog diagram at most once per paper (anything else twice), and at most 25% of a
            // batch's diagrams may be ones that appeared in recent papers.
            $usedKeys = Freshness::siblingKeys($examId);
            $recent = array_flip($history['diagrams']);
            $recentCap = (int) ceil($want * 0.25);
            $recentUsed = 0;
            $sawValid = false; // the model did return usable questions (even if all were copies)
            for ($round = 0; $round < 3 && count($fresh) < $want; $round++) {
                $ask = $round === 0 ? $want : $want - count($fresh) + 4;
                $payload = self::batchPayload($b, $ask, $history, array_merge($newStems, $history['stems']));
                $parsed = json_decode(Gemini::generate($payload), true);
                foreach (is_array($parsed['questions'] ?? null) ? $parsed['questions'] : [] as $q) {
                    $clean = self::cleanQuestion($q, $b['section_name'], $mode, (bool) $b['include_diagrams']);
                    if ($clean === null) {
                        continue; // malformed (not 4 options / bad key / no text): skip rather than ship a wrong answer key
                    }
                    $sawValid = true;
                    $set = Freshness::tokens($clean['text']);
                    array_unshift($newStems, $clean['text']);
                    if (Freshness::maxSimilarity($set, array_merge($siblings, $freshSets)) >= Freshness::SAME) {
                        continue; // a copy of a question already in this paper
                    }
                    $sim = Freshness::maxSimilarity($set, $history['sets']);
                    if ($sim >= Freshness::SAME) {
                        $repeats[] = [$sim, $clean, $set];
                        continue; // a copy of an earlier paper's question: hold back
                    }
                    $key = $clean['key'];
                    if ($key !== null && (($usedKeys[$key] ?? 0) >= (str_starts_with($key, 'catalog:') ? 1 : 2) || (isset($recent[$key]) && $recentUsed >= $recentCap))) {
                        $held[] = [$clean, $set];
                        array_unshift($history['diagrams'], $key); // tell the retry which pictures to avoid
                        continue;
                    }
                    if (count($fresh) < $want) {
                        $fresh[] = $clean;
                        $freshSets[] = $set;
                        if ($key !== null) {
                            $usedKeys[$key] = ($usedKeys[$key] ?? 0) + 1;
                            $recentUsed += isset($recent[$key]) ? 1 : 0;
                        }
                    }
                }
            }
            // Still short? A new question with a familiar picture beats a repeated question.
            foreach ($held as [$clean, $set]) {
                if (count($fresh) >= $want) {
                    break;
                }
                $fresh[] = $clean;
                $freshSets[] = $set;
            }
            // Still short? Let the least similar near-repeats in, up to the paper's allowance.
            usort($repeats, fn ($x, $y) => $x[0] <=> $y[0]);
            foreach ($repeats as [$sim, $clean, $set]) {
                if (count($fresh) >= $want || $quota <= 0) {
                    break;
                }
                if (Freshness::maxSimilarity($set, array_merge($siblings, $freshSets)) >= Freshness::SAME) {
                    continue;
                }
                $fresh[] = $clean;
                $freshSets[] = $set;
                $quota--;
            }
            if (!$fresh && !$sawValid) {
                throw new AppError('We could not create this part of the test. Please try again.', 502);
            }
            // (if it only produced copies we complete the batch short: a slightly smaller paper beats no paper)

            $pdo->beginTransaction();
            $withKey = Freshness::hasDiagramKey();
            $insQ = $pdo->prepare(
                'INSERT INTO questions (exam_id, batch_id, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option'
                . ($withKey ? ', diagram_key' : '') . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?' . ($withKey ? ', ?' : '') . ')'
            );
            $insO = $pdo->prepare('INSERT INTO question_options (question_id, label, option_text) VALUES (?, ?, ?)');
            $count = 0;
            foreach ($fresh as $clean) {
                $row = [$examId, $batchId, $b['section_name'], $clean['topic'], $clean['difficulty'], $clean['text'],
                    (int) ($clean['svg'] !== null), $clean['svg'], $clean['explanation'], $clean['correct']];
                if ($withKey) {
                    $row[] = $clean['key'];
                }
                $insQ->execute($row);
                $qid = (int) $pdo->lastInsertId();
                foreach ($clean['options'] as $label => $text) {
                    $insO->execute([$qid, $label, $text]);
                }
                $count++;
            }
            $pdo->prepare("UPDATE exam_batches SET status = 'completed', generated_count = ? WHERE id = ?")->execute([$count, $batchId]);
            $pdo->commit();
            $finished = true;
            return ['status' => 'completed', 'generated' => $count];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pdo->prepare("UPDATE exam_batches SET status = 'failed' WHERE id = ?")->execute([$batchId]);
            throw $e;
        }
    }

    private static function batchPayload(array $b, int $want, array $history, array $avoidStems): array
    {
        $menu = self::diagramMode($b) === 'bio' ? Freshness::catalogMenu($history['diagrams']) : [];
        $chartRule = 'DATA CHARTS: for bar, line or pie charts (data interpretation, growth curves, enzyme activity, population data) do NOT draw SVG: set "hasDiagram": true, leave "svgDiagram" empty, keep "structure" kind "none", and fill the "chart" object completely: chartType, title, xLabel, yLabel, labels (the category names) and series [{name, values}] with ONE number per label - the server plots exactly those numbers, so write a question that can be answered from them. NEVER say "refer to the chart/graph" unless you filled "chart" with real labels and values. ';

        $diagram = $b['include_diagrams']
            ? $chartRule . 'OTHER DIAGRAMS: for geometry, mensuration, Venn diagrams, seating arrangements, circuits or trees, you may draw an inline <svg viewBox="0 0 440 230" xmlns="http://www.w3.org/2000/svg">...</svg> inside "svgDiagram" with light background (#f8fafc), clear dark labels, and exact numbers matching the question (leave "structure" kind "none" then). Aim for at least 30% to 40% of the questions to carry some diagram, and use a DIFFERENT kind of diagram for each. For questions without a diagram set "hasDiagram": false and "svgDiagram": "".'
            : 'Set "hasDiagram": false and "svgDiagram": "" for all questions.';

        // Chemistry sections: the model describes the molecule in `structure` and the server draws it.
        if ($b['include_diagrams'] && self::isChemistry($b)) {
            $diagram = 'STRUCTURE DIAGRAMS (organic chemistry): do NOT draw chemical structures yourself. For every question that refers to a drawn molecule, set "hasDiagram": true, leave "svgDiagram" empty, and describe the molecule in "structure" - the server draws it. '
                . 'structure.kind is "benzene" (ringSubstituents: position 1-6 round the ring, group e.g. OH, NO2, CH3, Cl, Br, NH2, COOH, CHO, OCH3), '
                . '"naphthalene" (ringSubstituents use IUPAC positions 1-8: 1-4 on one ring, 5-8 on the other), '
                . '"ring" (a cycloalkane or cycloalkene: ringSize 3-8, ringBonds = numbers of the ring bonds that are double, ringSubstituents on positions 1..ringSize), '
                . '"chain" (zig-zag skeletal chain) or "compare" (items = 2 or 3 of the above shown side by side and captioned I, II, III - use it for isomer and comparison questions; each item needs its own kind and fields). '
                . 'For "chain": chainBonds lists the bond order (1, 2 or 3) between each pair of consecutive carbons (5 entries = 6 carbons), and chainBranches lists groups on carbon numbers 1..n (group "C" is a bare methyl line, "=O" a carbonyl oxygen, otherwise OH, NH2, Cl, Br, CH3, COOH ...; an end carbon or a quaternary carbon may carry two groups). '
                . 'Vary the kinds of structure you use (rings, chains, aromatics, comparisons) from question to question. '
                . 'Use kind "none" when the question needs no drawing. Never write "in the figure" or "the structure shown" unless structure.kind is not "none", and make the question, options and explanation agree exactly with the structure you specified (positions, bonds, substituents). '
                . 'Write formulas in text with underscores and carets, e.g. C_6H_6, CH_3COOH, NO_2^-.';
        }

        if (self::diagramMode($b) === 'bio') {
            $diagram = 'BIOLOGY DIAGRAMS: do NOT draw anything yourself. When a question needs a figure, set "hasDiagram": true, leave "svgDiagram" empty, and choose ONE of these in "structure": '
                . '(1) kind "catalog" with a diagramId from the MENU below. The picture is pre-drawn and its letters are shuffled by the server, so NEVER write a letter yourself: refer to a part by its exact name inside double braces, e.g. "the structure labelled {{Ovary}} develops into ..." or give options such as "{{Stigma}}", "{{Anther}}" - the server replaces each {{Part name}} with the letter shown on the picture (use the part names exactly as listed; also use {{...}} in the explanation). '
                . '(2) kind "punnett" with parent1 and parent2 as genotypes, e.g. "Tt" and "Tt", or "RrYy" and "RrYy" for a dihybrid cross (both parents need the same genes; uppercase = dominant) - the grid is computed by the server, so ask about ratios or counts, and do not give a wrong ratio. Choose DIFFERENT genes and crosses (test crosses, incomplete dominance, dihybrid) each time; '
                . '(3) kind "pyramid" with levels listed bottom to top as {name, value}, e.g. Producers 10,000 J, Primary consumers "?" ...; '
                . '(4) a pedigree: keep "structure" kind "none" and fill the "pedigree" object with people [{id, gen 1-4, sex M or F, affected, mate, father, mother}] listed left to right within each generation (mates must sit side by side; father and mother must be an adjacent male and female in the previous generation; use 0 when there is none) - invent a NEW family tree and make the question consistent with it; '
                . '(5) ' . $chartRule . ' '
                . '(6) kind "flow" for food chains, nutrient cycles (carbon, nitrogen, water), biochemical cycles and physiological pathways: layout "cycle" (3-8 nodes) or "chain" (2-8 nodes), nodes = the box labels in order, edgeLabels = a label for each arrow (a cycle has as many arrows as nodes: the last arrow goes back to the first node; a chain has one fewer), title; use "?" as a node or arrow label for the missing step the student must identify; '
                . '(7) kind "dna_seq" with template = the TEMPLATE strand written 3 to 5 (letters A, C, G, T only, 6 to 24 long, a multiple of 3), show "mrna" or "protein" - the server computes and draws the coding strand, mRNA and amino acids, so you may ask for the mRNA, the polypeptide or the effect of a mutation (use a DIFFERENT sequence each time). '
                . 'Use a different figure for every question, vary the kinds, and use each catalog diagram at most once. Use kind "none" if no figure is needed. Never write "in the figure shown" unless structure.kind is not "none", and never use the words "catalog" or "pre-drawn" in a question (just say "the diagram"). '
                . 'MENU of catalog diagrams for this paper (use only these, and only where they fit the topic):' . "\n"
                . BioSvg::legend($menu) . "\n"
                . 'Write formulas and charges in text with underscores and carets, e.g. CO_2, Ca^{2+}.';
        }

        $system = "You are a senior competitive exam paper setter for {$b['title']}.\n"
            . "Generate realistic, exam-standard Multiple Choice Questions (MCQs).\n"
            . 'MATH & SYMBOL FORMATTING: Whenever using mathematical or scientific expressions (integrals, Greek letters like λ, θ, α, β, π, Δ, Ω, square roots, fractions, exponents), either use clean Unicode symbols (∫, λ, θ, π, √, ≤, ≥, ≠, Δ, Ω) or standard LaTeX commands (\\int_{a}^{b}, \\frac{a}{b}, \\sqrt{x}, \\lambda, \\theta, x^2) which our portal renders natively.' . "\n"
            . $diagram . "\n"
            . "IMPORTANT: Always write the step-by-step 'explanation' first to verify the math or logic, and then set 'correctOption' to match the verified answer.";

        $user = "Generate exactly {$want} unique, high-quality competitive exam MCQs for the section \"{$b['section_name']}\" ({$b['part_label']}).\n"
            . "Target Topics to cover: {$b['topics']}.\n"
            . "Difficulty Distribution: {$b['difficulty_mix']}.\n"
            . Freshness::promptHints($history, $avoidStems, $b['topics']) . "\n"
            . 'Ensure each question has 4 clear options labeled A, B, C, D, a verified step-by-step explanation, and the exact correctOption.';

        return [
            'contents'          => [['parts' => [['text' => $user]]]],
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'generationConfig'  => [
                'responseMimeType' => 'application/json',
                'responseSchema'   => self::responseSchema($menu),
                'temperature'      => mt_rand(85, 100) / 100, // a little different every time
            ],
        ];
    }

    /** Self-corrections and internal jargon that must never reach students. */
    private static function looksLikeScratchwork(string $t): bool
    {
        return (bool) preg_match('/\b(?:let\W?s\s+(?:adjust|re-?\w+|fix|try|correct|change|modify)|adjust the question|(?:fix|correct|modify|change) the (?:question|options?)|to fit the (?:catalog|diagram|menu)|wait[,.!]|oops|hmm+\b|re-?evaluat\w+|catalog\b|\{\{|\}\})/i', $t);
    }

    /** Does the question text rely on a picture ("refer to the bar chart", "the figure shown")? */
    private static function refersToFigure(string $text): bool
    {
        return (bool) preg_match('/\b(refer(?:ring)? to|from|in|using|study|observe|examine|consider|based on|according to)\s+(?:the\s+)?(?:given\s+|following\s+|above\s+|below\s+|accompanying\s+|shown\s+)?(?:figure|diagram|bar chart|pie chart|line graph|chart|graph|pedigree|picture|venn diagram)\b|\b(?:figure|diagram|chart|graph|pedigree)\s+(?:given|shown|below|above)\b|\b(?:given|shown|following|above|below)\s+(?:figure|diagram|chart|graph|pedigree)\b/i', $text);
    }

    /**
     * Draws the question's figure. Server-drawn specs for charts/chemistry/biology; model SVG only elsewhere.
     * $seed (non-zero) shuffles the letters of a catalog diagram; "letters" maps part name => letter shown.
     *
     * @return ?array{svg:string,letters:?array<string,string>}
     */
    private static function drawFigure(array $q, ?string $mode, int $seed = 0): ?array
    {
        $spec = is_array($q['structure'] ?? null) ? $q['structure'] : null;
        $plain = fn (?string $svg) => $svg === null ? null : ['svg' => $svg, 'letters' => null];
        if (is_array($q['chart'] ?? null) && !empty($q['chart']['labels'])) {
            return $plain(ChartSvg::fromSpec($q['chart'])); // charts are drawn the same way for every subject
        }
        if (is_array($q['pedigree'] ?? null) && !empty($q['pedigree']['people']) && $mode === 'bio') {
            return $plain(BioSvg::fromSpec(['kind' => 'pedigree', 'people' => $q['pedigree']['people']]));
        }
        return match ($mode) {
            // Chemistry and biology batches ignore model-drawn SVG (it gets structures and anatomy wrong).
            'chem'  => $plain(MoleculeSvg::fromSpec($spec)),
            'bio'   => BioSvg::figure($spec, $seed),
            default => $plain(!empty($q['hasDiagram']) ? Svg::sanitize((string) ($q['svgDiagram'] ?? '')) : null),
        };
    }

    /** Swaps {{Part name}} for the letter shown on the picture. Null when a name can't be matched. */
    private static function fillLetters(string $text, array $letters): ?string
    {
        $bad = false;
        $out = preg_replace_callback('/\{\{\s*([^{}]+?)\s*\}\}/u', function ($m) use ($letters, &$bad) {
            $l = BioSvg::resolvePart($letters, $m[1]);
            if ($l === null) {
                $bad = true;
                return $m[0];
            }
            return $l;
        }, $text);
        return $bad ? null : $out;
    }

    /** @return array{topic:string,difficulty:string,text:string,svg:?string,key:?string,explanation:string,correct:string,options:array<string,string>}|null */
    private static function cleanQuestion(mixed $q, string $section, ?string $mode = null, bool $figuresOn = false): ?array
    {
        if (!is_array($q)) {
            return null;
        }
        $text = trim((string) ($q['questionText'] ?? ''));
        $correct = (string) ($q['correctOption'] ?? '');
        $opts = $q['options'] ?? null;
        if ($text === '' || !in_array($correct, ['A', 'B', 'C', 'D'], true) || !is_array($opts) || count($opts) !== 4) {
            return null;
        }
        $options = [];
        foreach (array_values($opts) as $i => $o) {
            $t = trim((string) ($o['text'] ?? ''));
            if ($t === '') {
                return null;
            }
            $options['ABCD'[$i]] = $t;
        }
        $difficulty = in_array($q['difficulty'] ?? '', ['Easy', 'Medium', 'Hard'], true) ? $q['difficulty'] : 'Medium';
        $explanation = trim((string) ($q['explanation'] ?? '')) ?: 'Step-by-step solution verified.';
        $hasPlaceholders = str_contains($text . $explanation . implode("\n", $options), '{{');
        $fig = self::drawFigure($q, $mode, $hasPlaceholders ? random_int(1, 9999) : 0);
        if ($hasPlaceholders) {
            // {{Part name}} -> the letter this particular picture shows (letters are shuffled per picture)
            if (!$fig || !$fig['letters']) {
                return null;
            }
            $text = self::fillLetters($text, $fig['letters']);
            $explanation = $text === null ? null : self::fillLetters($explanation, $fig['letters']);
            foreach ($options as $k => $o) {
                $options[$k] = $explanation === null ? null : self::fillLetters($o, $fig['letters']);
            }
            if ($text === null || $explanation === null || in_array(null, $options, true)) {
                return null;
            }
        }
        $scrub = fn (string $t) => preg_replace('/\b(?:catalog(?:ue)?|pre-?drawn)\s+(?=diagram|figure|picture|image)/i', '', $t) ?? $t;
        $text = $scrub($text);
        $explanation = $scrub($explanation);
        $options = array_map($scrub, $options);
        if (self::looksLikeScratchwork($text . "\n" . $explanation . "\n" . implode("\n", $options))) {
            return null; // the model was thinking out loud inside the question: never show that to students
        }
        $svg = $fig['svg'] ?? null;
        if ($svg === null && $figuresOn && self::refersToFigure($text)) {
            return null; // the question points at a figure we could not draw: unanswerable, so don't keep it
        }
        $spec = !empty($q['chart']['labels']) ? ['kind' => 'chart', 'chartType' => $q['chart']['chartType'] ?? '', 'title' => $q['chart']['title'] ?? '']
            : (!empty($q['pedigree']['people']) ? ['kind' => 'pedigree', 'people' => $q['pedigree']['people']]
            : (is_array($q['structure'] ?? null) ? $q['structure'] : null));

        return [
            'topic'       => mb_substr(trim((string) ($q['topic'] ?? '')) ?: $section, 0, 200),
            'difficulty'  => $difficulty,
            'text'        => $text,
            'svg'         => $svg,
            'key'         => $svg !== null ? Freshness::diagramKey($spec) : null,
            'explanation' => $explanation,
            'correct'     => $correct,
            'options'     => $options,
        ];
    }

    /** Subjects whose diagrams the server draws from a spec instead of trusting model-drawn SVG. */
    private static function diagramMode(array $b): ?string
    {
        if (!$b['include_diagrams']) {
            return null;
        }
        if (self::isChemistry($b)) {
            return 'chem';
        }
        return preg_match('/botany|zoology|biolog|genetic|ecolog|physiolog|anatomy|evolution|heredity|mitosis|meiosis|neuron|nephron|human heart|plant cell/i', $b['section_name'] . ' ' . $b['topics']) ? 'bio' : null;
    }

    private static function isChemistry(array $b): bool
    {
        return (bool) preg_match('/chem|organic|benzene|aromatic|alkane|alkene|alkyl|isomer|hydrocarbon|phenol|carbonyl|polymer/i', $b['section_name'] . ' ' . $b['topics']);
    }

    /** Numbers the questions in plan order and marks the exam ready. */
    public static function finalize(int $examId): int
    {
        $pdo = db();
        $st = $pdo->prepare("SELECT COUNT(*) FROM exam_batches WHERE exam_id = ? AND status <> 'completed'");
        $st->execute([$examId]);
        if ((int) $st->fetchColumn() > 0) {
            throw new AppError('Some parts of the test are still being created.', 409);
        }

        $pdo->beginTransaction();
        $ids = $pdo->prepare('SELECT id FROM questions WHERE exam_id = ? ORDER BY batch_id, id');
        $ids->execute([$examId]);
        $upd = $pdo->prepare('UPDATE questions SET position = ? WHERE id = ?');
        $n = 0;
        foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $qid) {
            $upd->execute([++$n, $qid]);
        }
        if ($n === 0) {
            $pdo->rollBack();
            throw new AppError('No questions could be created. Please try again.', 502);
        }
        $pdo->prepare("UPDATE exams SET status = 'ready' WHERE id = ?")->execute([$examId]);
        $pdo->commit();
        return $n;
    }
}
