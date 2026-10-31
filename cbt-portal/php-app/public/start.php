<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

// POST-only: begins a new attempt on an exam ("demo", or the id of an AI paper this browser generated).
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('index.php');
}
check_csrf($_POST['csrf'] ?? null);

$which = (string) ($_POST['exam'] ?? '');
if ($which !== '' && !ctype_digit($which)) {
    // a built-in paper, by slug ("demo", "rrb-ntpc", ...)
    $exam = Exams::builtin($which) ?? throw new AppError('That test is not available right now.', 404);
    $examId = (int) $exam['id'];
} else {
    $examId = (int) $which;
    if (!owns('exam', $examId)) {
        throw new AppError('Exam not found.', 404);
    }
}

$attemptId = Attempts::start($examId);
grant('attempt', $attemptId);
redirect('exam.php?attempt=' . $attemptId);
