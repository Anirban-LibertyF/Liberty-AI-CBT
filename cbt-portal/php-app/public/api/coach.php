<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post(releaseSession: true);
$attemptId = (int) ($in['attempt_id'] ?? 0);
if (!owns('attempt', $attemptId)) {
    throw new AppError('Attempt not found.', 404);
}
if (Attempts::load($attemptId)['status'] !== 'submitted') {
    throw new AppError('Submit the exam first.', 409);
}
set_time_limit(300);
json_out(Coach::generate($attemptId));
