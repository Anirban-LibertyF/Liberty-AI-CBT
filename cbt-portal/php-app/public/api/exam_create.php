<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post();
if (!Gemini::isConfigured()) {
    throw new AppError('AI test creation is not available right now. You can still take a ready-made mock test.', 503);
}
$cfg = Exams::validateConfig($in);
$created = Exams::createAi($cfg);
grant('exam', $created['exam_id']);
json_out($created);
