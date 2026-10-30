<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

// Release the session lock so the browser's parallel batch calls really run in parallel.
$in = api_post(releaseSession: true);
$examId = (int) ($in['exam_id'] ?? 0);
if (!owns('exam', $examId)) {
    throw new AppError('Exam not found.', 404);
}
json_out(Exams::runBatch((int) ($in['batch_id'] ?? 0), $examId));
