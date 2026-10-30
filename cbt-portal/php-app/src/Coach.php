<?php
declare(strict_types=1);

/** Post-exam AI performance coach, with an offline fallback computed from the scorecard. */
final class Coach
{
    public static function stored(int $attemptId): ?array
    {
        $st = db()->prepare('SELECT source, report FROM coach_reports WHERE attempt_id = ?');
        $st->execute([$attemptId]);
        $row = $st->fetch();
        return $row ? ['source' => $row['source'], 'report' => json_decode($row['report'], true)] : null;
    }

    /** Generates (or regenerates) the report and stores it. @return array{source:string,report:array} */
    public static function generate(int $attemptId): array
    {
        $a = Attempts::load($attemptId);
        $stats = Attempts::analytics($attemptId);

        $source = 'offline';
        $report = null;
        if (Gemini::isConfigured()) {
            try {
                $report = self::askGemini($a['title'], $stats);
                $source = 'ai';
            } catch (Throwable $e) {
                error_log('Coach fell back to offline report: ' . $e->getMessage());
            }
        }
        $report ??= self::offlineReport($stats);

        db()->prepare(
            'REPLACE INTO coach_reports (attempt_id, source, report) VALUES (?, ?, ?)'
        )->execute([$attemptId, $source, json_encode($report, JSON_UNESCAPED_UNICODE)]);

        return ['source' => $source, 'report' => $report];
    }

    private static function askGemini(string $title, array $s): array
    {
        $prompt = "Analyze this student's CBT Competitive Exam attempt for \"$title\":\n"
            . "- Score: {$s['totalScore']} / {$s['maxScore']} (Accuracy: {$s['accuracy']}%, Attempted: {$s['attemptedCount']}/{$s['totalQuestions']})\n"
            . '- Total Time Spent: ' . intdiv($s['totalTimeSpent'], 60) . 'm ' . ($s['totalTimeSpent'] % 60) . "s\n"
            . '- Section-wise Performance: ' . json_encode($s['sectionBreakdown'], JSON_UNESCAPED_UNICODE) . "\n"
            . '- Topic-wise Performance: ' . json_encode(array_slice($s['topicBreakdown'], 0, 15), JSON_UNESCAPED_UNICODE) . "\n\n"
            . 'Provide a personalized, encouraging, and deeply analytical coaching report with specific time-management insights and high-yield revision steps.';

        $strings = ['type' => 'ARRAY', 'items' => ['type' => 'STRING']];
        $text = Gemini::generate([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'overallVerdict'          => ['type' => 'STRING'],
                        'timeManagementAnalysis'  => ['type' => 'STRING'],
                        'strongAreas'             => $strings,
                        'weakTopicsToRevise'      => $strings,
                        'actionableStudyPlan'     => $strings,
                    ],
                    'required' => ['overallVerdict', 'timeManagementAnalysis', 'strongAreas', 'weakTopicsToRevise', 'actionableStudyPlan'],
                ],
            ],
        ]);
        $r = json_decode($text, true);
        if (!is_array($r) || !isset($r['overallVerdict'])) {
            throw new RuntimeException('Malformed coach response');
        }
        return $r;
    }

    private static function offlineReport(array $s): array
    {
        $avg = (int) round($s['totalTimeSpent'] / max(1, $s['attemptedCount']));
        return [
            'overallVerdict' => "You scored {$s['totalScore']}/{$s['maxScore']} with {$s['accuracy']}% accuracy. Focusing on reducing negative marking in moderate-to-hard questions will boost your percentile above 95%.",
            'timeManagementAnalysis' => "You spent an average of $avg seconds per attempted question. Use the 2-pass \"Mark for Review\" strategy to clear easy questions in the first 25 minutes.",
            'strongAreas' => array_values(array_map(
                fn ($x) => "{$x['section']} ({$x['correct']}/{$x['total']} correct)",
                array_filter($s['sectionBreakdown'], fn ($x) => $x['correct'] >= $x['incorrect'])
            )),
            'weakTopicsToRevise' => array_slice(array_values(array_map(
                fn ($t) => "{$t['topic']} (Needs concept brush-up)",
                array_filter($s['topicBreakdown'], fn ($t) => $t['incorrect'] > 0 || $t['correct'] === 0)
            )), 0, 4),
            'actionableStudyPlan' => [
                'Review all Incorrect questions in the Solution Filter below to eliminate conceptual traps.',
                'Practice 15-question timed sectional drills on your lowest-scoring section.',
                'Avoid guessing when more than 2 options cannot be eliminated to protect against negative marking.',
            ],
        ];
    }
}
