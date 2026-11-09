<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post(releaseSession: true);
$attemptId = (int) ($in['attempt_id'] ?? 0);
if (!owns('attempt', $attemptId)) {
    throw new AppError('Attempt not found.', 404);
}
$ids = array_slice(array_map('intval', (array) ($in['question_ids'] ?? [])), 0, 8);

// only questions that belong to this attempt's exam
$st = db()->prepare('SELECT exam_id FROM attempts WHERE id = ?');
$st->execute([$attemptId]);
$examId = (int) $st->fetchColumn();
$ids = $ids ? array_map('intval', array_column(
    (function () use ($ids, $examId) {
        $q = db()->prepare('SELECT id FROM questions WHERE exam_id = ? AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $q->execute([$examId, ...$ids]);
        return $q->fetchAll();
    })(),
    'id'
)) : [];

json_out(['translations' => (object) Translate::bengali($ids)]);
