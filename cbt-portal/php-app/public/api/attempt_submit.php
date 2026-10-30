<?php
declare(strict_types=1);
define('IS_API', true);
require __DIR__ . '/../../src/bootstrap.php';

$in = api_post(releaseSession: true);
$attemptId = (int) ($in['attempt_id'] ?? 0);
if (!owns('attempt', $attemptId)) {
    throw new AppError('Attempt not found.', 404);
}

// Credit the last question's time before locking the paper. If the attempt was already
// closed (timer ran out elsewhere), that's fine: we still go to the result page.
if (!empty($in['question_id'])) {
    try {
        Attempts::save($attemptId, $in);
    } catch (AppError $e) {
        if ($e->status !== 409) {
            throw $e;
        }
    }
}
Attempts::submit($attemptId);
json_out(['redirect' => 'result.php?attempt=' . $attemptId]);
