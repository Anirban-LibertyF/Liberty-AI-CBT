<?php
declare(strict_types=1);

/** Bengali versions of questions: written by Gemini on first request, then cached in question_translations. */
final class Translate
{
    /**
     * @param list<int> $questionIds questions that belong to the caller's exam
     * @return array<int, array{html:string, options: array<string,string>}> rendered Bengali HTML per question id
     */
    public static function bengali(array $questionIds): array
    {
        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));
        if (!$questionIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($questionIds), '?'));
        $pdo = db();

        $have = [];
        $st = $pdo->prepare("SELECT question_id, question_text, options_json FROM question_translations WHERE lang = 'bn' AND question_id IN ($in)");
        $st->execute($questionIds);
        foreach ($st->fetchAll() as $r) {
            $have[(int) $r['question_id']] = [$r['question_text'], json_decode($r['options_json'], true) ?: []];
        }

        $missing = array_values(array_diff($questionIds, array_keys($have)));
        if ($missing) {
            foreach (self::translate($missing) as $qid => $t) {
                $have[$qid] = $t;
            }
        }

        $out = [];
        foreach ($have as $qid => [$text, $opts]) {
            $out[$qid] = [
                'html'    => MathRenderer::render($text),
                'options' => array_map(fn ($o) => MathRenderer::render((string) $o), $opts),
            ];
        }
        return $out;
    }

    /** @param list<int> $ids @return array<int, array{0:string,1:array<string,string>}> */
    private static function translate(array $ids): array
    {
        $pdo = db();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id, section, topic, question_text, explanation FROM questions WHERE id IN ($in)");
        $st->execute($ids);
        $qs = [];
        foreach ($st->fetchAll() as $q) {
            $qs[(int) $q['id']] = $q;
        }
        $st = $pdo->prepare("SELECT question_id, label, option_text FROM question_options WHERE question_id IN ($in) ORDER BY label");
        $st->execute($ids);
        $opts = [];
        foreach ($st->fetchAll() as $o) {
            $opts[(int) $o['question_id']][$o['label']] = $o['option_text'];
        }

        $items = [];
        foreach ($qs as $id => $q) {
            $items[] = ['id' => $id, 'subject' => $q['section'], 'topic' => $q['topic'], 'question' => $q['question_text'], 'options' => $opts[$id] ?? []];
        }

        $system = "You are a senior Bengali (বাংলা) paper-setter who writes WBPSC / WBCS / West Bengal Board / entrance-exam question papers.\n"
            . "Translate each MCQ into natural, fluent Bengali exactly as a native paper-setter would WRITE it originally - NOT a word-for-word machine translation.\n"
            . "RULES:\n"
            . "1. Use standard modern Bengali (শুদ্ধ চলিত ভাষা): simple, clear sentences with correct Bengali grammar and sentence order (verb at the end). Avoid stiff, over-Sanskritised or awkward literal phrasing.\n"
            . "2. Use the standard terms Bengali textbooks and exams use (e.g. শতকরা, লাভ-ক্ষতি, সরল সুদ, ত্রিভুজ, বেগ, আয়ন, সালোকসংশ্লেষ). If a term has no common Bengali word, keep the English term, optionally in brackets after the Bengali.\n"
            . "3. Write ALL numbers, years and dates with Arabic digits 0-9 exactly as in the source - never Bengali digits (০-৯), because figures and options must match. Keep UNCHANGED: numbers, units, symbols, chemical formulas, equations, LaTeX such as \\frac{a}{b}, \\sqrt{x}, x^2, \\theta, Greek letters, abbreviations (UNESCO, DNA), and proper names of people/places/books (translate/transliterate them only when a well-known Bengali form exists, e.g. কলকাতা, রবীন্দ্রনাথ ঠাকুর).\n"
            . "4. If the question tests English language itself (vocabulary, grammar, spelling, idioms, one-word substitution, error spotting), keep the English words/sentences being tested exactly as they are in the question and options, and translate only the instruction around them into Bengali.\n"
            . "5. Do not add, remove or reorder options and do not change their meaning. Never reveal or hint at the answer. Output the same option keys (A, B, C, D).";
        $payload = [
            'contents'          => [['parts' => [['text' => "Translate these questions into Bengali:\n" . json_encode($items, JSON_UNESCAPED_UNICODE)]]]],
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'generationConfig'  => [
                'responseMimeType' => 'application/json',
                'temperature'      => 0.3,
                'responseSchema'   => [
                    'type'  => 'ARRAY',
                    'items' => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'id'       => ['type' => 'INTEGER'],
                            'question' => ['type' => 'STRING'],
                            'A' => ['type' => 'STRING'], 'B' => ['type' => 'STRING'],
                            'C' => ['type' => 'STRING'], 'D' => ['type' => 'STRING'],
                        ],
                        'required'         => ['id', 'question', 'A', 'B', 'C', 'D'],
                        'propertyOrdering' => ['id', 'question', 'A', 'B', 'C', 'D'],
                    ],
                ],
            ],
        ];
        $res = json_decode(Gemini::generate($payload), true);
        if (!is_array($res)) {
            throw new AppError('Bengali translation is not available right now. Please try again.', 502);
        }

        $ins = $pdo->prepare(
            "INSERT INTO question_translations (question_id, lang, question_text, options_json) VALUES (?, 'bn', ?, ?)
             ON DUPLICATE KEY UPDATE question_text = VALUES(question_text), options_json = VALUES(options_json)"
        );
        $out = [];
        foreach ($res as $r) {
            $id = (int) ($r['id'] ?? 0);
            if (!isset($qs[$id])) {
                continue;
            }
            $text = trim((string) ($r['question'] ?? ''));
            $o = [];
            foreach (array_keys($opts[$id] ?? []) as $label) {
                $o[$label] = trim((string) ($r[$label] ?? ''));
            }
            // a translation with an empty question or option is worse than showing the English one
            if ($text === '' || in_array('', $o, true) || count($o) !== 4) {
                continue;
            }
            $ins->execute([$id, $text, json_encode($o, JSON_UNESCAPED_UNICODE)]);
            $out[$id] = [$text, $o];
        }
        return $out;
    }
}
