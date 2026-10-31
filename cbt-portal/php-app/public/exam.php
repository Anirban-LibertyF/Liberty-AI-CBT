<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$attemptId = (int) ($_GET['attempt'] ?? 0);
$attempt = Attempts::loadOwned($attemptId);
if ($attempt['status'] === 'submitted') {
    redirect('result.php?attempt=' . $attemptId);
}

$questions = Attempts::examPayload($attemptId, (int) $attempt['exam_id']);
$boot = [
    'attemptId' => $attemptId,
    'title'     => $attempt['title'],
    'remaining' => $attempt['remaining'],
    'positive'  => (float) $attempt['positive_marks'],
    'negative'  => (float) $attempt['negative_marks'],
    'roll'      => sprintf('CBT-%s-%04d', date('Y'), $attemptId),
    'questions' => $questions,
];

page_head($attempt['title'] . ' | LibertyCBT.Ai', 'h-screen flex flex-col bg-red-50 text-slate-900 select-none overflow-hidden');
?>
<div id="app" class="h-screen flex flex-col overflow-hidden"></div>
<script>window.EXAM = <?= js_json($boot) ?>;</script>
<?php page_foot(['assets/exam.js']);
