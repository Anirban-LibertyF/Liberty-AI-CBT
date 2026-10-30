<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post(releaseSession: true);
$attemptId = (int) ($in['attempt_id'] ?? 0);
if (!owns('attempt', $attemptId)) {
    throw new AppError('Attempt not found.', 404);
}
json_out(['remaining' => Attempts::save($attemptId, $in)]);
