<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$attemptId = (int) ($_GET['attempt'] ?? 0);
$attempt = Attempts::loadOwned($attemptId);
if ($attempt['status'] === 'submitted') {
    redirect('result.php?attempt=' . $attemptId);
}

// Before the exam: instructions page. The clock does not start until the candidate clicks "ready".
if (!empty($attempt['not_begun'])) {
    $outline = Attempts::outline((int) $attempt['exam_id']);
    $totalQ = array_sum(array_map(fn ($o) => (int) $o['n'], $outline));
    $pos = (float) $attempt['positive_marks'];
    $neg = (float) $attempt['negative_marks'];
    $fmt = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    $u = Auth::user();
    page_head($attempt['title'] . ' — Instructions | MasterjeeCBT.Ai', 'min-h-screen bg-red-50 text-slate-900');
    require __DIR__ . '/instructions.inc.php';
    page_foot();
    exit;
}

// Who is sitting this exam, and which attempt of this paper it is for them.
$user = Auth::user();
if ($user) {
    $st = db()->prepare('SELECT COUNT(*) FROM attempts WHERE user_id = ? AND exam_id = ? AND id <= ?');
    $st->execute([$user['id'], $attempt['exam_id'], $attemptId]);
} else {
    $own = array_map('intval', array_keys($_SESSION['own']['attempt'] ?? []));
    $st = db()->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = ? AND id <= ? AND id IN (' . implode(',', $own ?: [0]) . ')');
    $st->execute([$attempt['exam_id'], $attemptId]);
}
$attemptNo = max(1, (int) $st->fetchColumn());

$questions = Attempts::examPayload($attemptId, (int) $attempt['exam_id']);
$boot = [
    'attemptId' => $attemptId,
    'title'     => $attempt['title'],
    'remaining' => $attempt['remaining'],
    'positive'  => (float) $attempt['positive_marks'],
    'negative'  => (float) $attempt['negative_marks'],
    'candidate' => ['name' => $user['name'] ?? 'Guest Candidate', 'email' => $user['email'] ?? 'Free demo (not signed in)', 'attemptNo' => $attemptNo],
    'roll'      => sprintf('CBT-%s-%04d', date('Y'), $attemptId),
    'questions' => $questions,
];

page_head($attempt['title'] . ' | MasterjeeCBT.Ai', 'h-screen flex flex-col bg-red-50 text-slate-900 select-none overflow-hidden');
?>
<div id="app" class="h-screen flex flex-col overflow-hidden"></div>
<script>window.EXAM = <?= js_json($boot) ?>;</script>
<?php page_foot(['assets/exam.js']);
