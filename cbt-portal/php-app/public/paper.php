<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

// Printable question paper with the full answer key and explanations (English or Bengali) -> "Download PDF".
$attemptId = (int) ($_GET['attempt'] ?? 0);
$attempt = Attempts::loadOwned($attemptId);
if ($attempt['status'] !== 'submitted') {
    redirect('exam.php?attempt=' . $attemptId);
}
$lang = ($_GET['lang'] ?? 'en') === 'bn' ? 'bn' : 'en';
$bn = $lang === 'bn';

$stats = Attempts::analytics($attemptId);
$rows = Attempts::reviewRows($attemptId);
$user = Auth::user();
$pos = (float) $attempt['positive_marks'];
$neg = (float) $attempt['negative_marks'];
$n = fn ($v) => h(round((float) $v, 2) + 0);

$T = $bn ? [
    'paper' => 'প্রশ্নপত্র ও উত্তরমালা', 'student' => 'পরীক্ষার্থী', 'date' => 'তারিখ', 'score' => 'প্রাপ্ত নম্বর',
    'marking' => 'নম্বর বিভাজন', 'correct' => 'সঠিক', 'wrong' => 'ভুল', 'skipped' => 'বাদ', 'key' => 'সঠিক উত্তর',
    'yours' => 'আপনার উত্তর', 'none' => 'উত্তর দেওয়া হয়নি', 'expl' => 'ব্যাখ্যা', 'anskey' => 'উত্তরসূচি',
    'download' => 'PDF ডাউনলোড', 'back' => 'ফিরে যান', 'lang' => 'ভাষা', 'translating' => 'বাংলায় অনুবাদ হচ্ছে…',
] : [
    'paper' => 'Question Paper with Answer Key', 'student' => 'Candidate', 'date' => 'Date', 'score' => 'Score',
    'marking' => 'Marking', 'correct' => 'Correct', 'wrong' => 'Wrong', 'skipped' => 'Skipped', 'key' => 'Correct answer',
    'yours' => 'Your answer', 'none' => 'Not attempted', 'expl' => 'Explanation', 'anskey' => 'Answer Key',
    'download' => 'Download PDF', 'back' => 'Back', 'lang' => 'Language', 'translating' => 'Translating to Bengali…',
];

page_head($attempt['title'] . ' — ' . $T['paper'] . ' | MasterjeeCBT.Ai', 'min-h-screen bg-slate-100 text-slate-900 pb-12');
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700&display=swap">
<style>
  body { font-family: "Noto Sans Bengali", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  .sheet { max-width: 820px; }
  .q { break-inside: avoid; page-break-inside: avoid; }
  @page { size: A4; margin: 14mm 12mm; }
  @media print {
    body { background: #fff !important; padding: 0 !important; height: auto !important; }
    .sheet { max-width: none !important; box-shadow: none !important; border: 0 !important; padding: 0 !important; }
    .no-print { display: none !important; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>

<div class="no-print sticky top-0 z-30 bg-white border-b border-red-100">
  <div class="max-w-[820px] mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
    <a href="history.php" class="text-sm font-semibold text-slate-600 hover:text-red-700 flex items-center gap-1.5"><?= icon('arrow-left', 'w-4 h-4') ?><?= h($T['back']) ?></a>
    <div class="flex items-center gap-3">
      <div class="inline-flex rounded-xl border border-red-200 overflow-hidden text-sm font-bold" role="group" aria-label="<?= h($T['lang']) ?>">
        <a href="paper.php?attempt=<?= $attemptId ?>&lang=en" class="px-3.5 py-1.5 <?= $bn ? 'bg-white text-slate-600 hover:bg-red-50' : 'bg-red-600 text-white' ?>">English</a>
        <a href="paper.php?attempt=<?= $attemptId ?>&lang=bn" class="px-3.5 py-1.5 <?= $bn ? 'bg-red-600 text-white' : 'bg-white text-slate-600 hover:bg-red-50' ?>">বাংলা</a>
      </div>
      <button id="print-btn" type="button" class="px-4 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-wait">
        <?= icon('download', 'w-4 h-4') ?><span><?= h($T['download']) ?></span>
      </button>
    </div>
  </div>
  <div id="bn-progress" class="hidden border-t border-red-100 bg-red-50">
    <div class="max-w-[820px] mx-auto px-4 py-2 text-xs font-semibold text-red-700 flex items-center gap-3">
      <span><?= h($T['translating']) ?></span>
      <div class="flex-1 h-1.5 rounded-full bg-red-100 overflow-hidden"><div id="bn-bar" class="h-full bg-red-600 transition-all" style="width:0%"></div></div>
      <span id="bn-pct">0%</span>
    </div>
  </div>
</div>

<main class="sheet mx-auto my-6 bg-white border border-slate-200 shadow-sm rounded-xl p-6 sm:p-9" lang="<?= $lang ?>">
  <div class="flex items-start justify-between gap-4 border-b-2 border-red-600 pb-4 mb-5">
    <div>
      <div class="text-xs font-bold tracking-widest text-red-600 uppercase">MasterjeeCBT.Ai</div>
      <h1 class="text-xl font-extrabold text-slate-900 mt-1"><?= h($attempt['title']) ?></h1>
      <div class="text-sm text-slate-500 mt-0.5"><?= h($T['paper']) ?></div>
    </div>
    <div class="text-right text-sm text-slate-600 shrink-0">
      <?php if ($user): ?><div><span class="text-slate-400"><?= h($T['student']) ?>:</span> <b class="text-slate-900"><?= h($user['name']) ?></b></div><?php endif; ?>
      <div><span class="text-slate-400"><?= h($T['date']) ?>:</span> <?= h(local_time((string) $attempt['submitted_at'])) ?> IST</div>
    </div>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 text-center">
    <div class="rounded-lg border border-red-100 bg-red-50 py-2.5"><div class="text-[11px] uppercase font-bold text-slate-500"><?= h($T['score']) ?></div><div class="text-lg font-extrabold text-red-700"><?= $n($stats['totalScore']) ?> / <?= $n($stats['maxScore']) ?></div></div>
    <div class="rounded-lg border border-emerald-100 bg-emerald-50 py-2.5"><div class="text-[11px] uppercase font-bold text-slate-500"><?= h($T['correct']) ?></div><div class="text-lg font-extrabold text-emerald-600"><?= (int) $stats['correctCount'] ?></div></div>
    <div class="rounded-lg border border-rose-100 bg-rose-50 py-2.5"><div class="text-[11px] uppercase font-bold text-slate-500"><?= h($T['wrong']) ?></div><div class="text-lg font-extrabold text-rose-600"><?= (int) $stats['incorrectCount'] ?></div></div>
    <div class="rounded-lg border border-slate-200 bg-slate-50 py-2.5"><div class="text-[11px] uppercase font-bold text-slate-500"><?= h($T['skipped']) ?></div><div class="text-lg font-extrabold text-slate-600"><?= (int) $stats['unattemptedCount'] ?></div></div>
  </div>
  <div class="text-xs text-slate-500 -mt-3 mb-6"><?= h($T['marking']) ?>: +<?= $n($pos) ?> / −<?= $n($neg) ?></div>

  <div class="space-y-5">
    <?php foreach ($rows as $q):
        $svg = $q['has_diagram'] ? Svg::sanitize($q['svg_diagram']) : null;
        $status = !$q['attempted'] ? 'none' : ($q['correct'] ? 'correct' : 'wrong');
    ?>
    <section class="q border border-slate-200 rounded-lg p-4" data-qid="<?= (int) $q['id'] ?>">
      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <div class="text-xs text-slate-500"><b class="text-slate-900 text-sm">Q.<?= (int) $q['position'] ?></b> &nbsp;<?= h($q['section']) ?> • <?= h($q['topic']) ?></div>
        <?php if ($status === 'correct'): ?><span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2 py-0.5">✓ <?= h($T['correct']) ?> (+<?= $n($pos) ?>)</span>
        <?php elseif ($status === 'wrong'): ?><span class="text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-full px-2 py-0.5">✗ <?= h($T['wrong']) ?> (−<?= $n($neg) ?>)</span>
        <?php else: ?><span class="text-[11px] font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5"><?= h($T['none']) ?></span><?php endif; ?>
      </div>

      <div class="q-text text-[15px] font-semibold text-slate-900 whitespace-pre-line"><?= MathRenderer::render($q['question_text']) ?></div>
      <?php if ($svg !== null): ?>
      <div class="my-3 flex justify-center [&>svg]:max-w-full [&>svg]:h-auto [&>svg]:max-h-[220px]"><?= $svg ?></div>
      <?php endif; ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3">
        <?php foreach ($q['options'] as $o):
            $right = $o['label'] === $q['correct_option'];
            $mine = $q['attempted'] && $o['label'] === $q['selected_option'];
            $box = $right ? 'bg-emerald-50 border-emerald-500 text-emerald-900 font-semibold' : ($mine ? 'bg-rose-50 border-rose-400 text-rose-900' : 'border-slate-200 text-slate-700');
        ?>
        <div class="border rounded-md px-2.5 py-1.5 text-[13px] flex items-start gap-2 <?= $box ?>">
          <b class="shrink-0"><?= h($o['label']) ?>.</b>
          <span class="opt-text" data-label="<?= h($o['label']) ?>"><?= MathRenderer::render($o['option_text']) ?></span>
          <?php if ($right): ?><span class="ml-auto shrink-0 text-emerald-700 font-bold">✓</span><?php elseif ($mine): ?><span class="ml-auto shrink-0 text-rose-700 font-bold">✗</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-3 text-[13px] text-slate-700">
        <b class="text-emerald-700"><?= h($T['key']) ?>: <?= h($q['correct_option']) ?></b>
        <?php if ($q['attempted']): ?> &nbsp;•&nbsp; <?= h($T['yours']) ?>: <b class="<?= $q['correct'] ? 'text-emerald-700' : 'text-rose-700' ?>"><?= h($q['selected_option']) ?></b><?php endif; ?>
      </div>
      <div class="mt-2 rounded-md bg-red-50 border border-red-100 p-2.5 text-[13px] text-slate-700 leading-relaxed">
        <b class="text-red-700"><?= h($T['expl']) ?>:</b> <span class="expl"><?= MathRenderer::render($q['explanation']) ?></span>
      </div>
    </section>
    <?php endforeach; ?>
  </div>

  <h2 class="text-base font-extrabold text-slate-900 mt-8 mb-3 border-b border-slate-200 pb-1.5"><?= h($T['anskey']) ?></h2>
  <div class="q grid grid-cols-5 sm:grid-cols-10 gap-1.5 text-center text-xs">
    <?php foreach ($rows as $q): ?>
    <div class="border border-slate-200 rounded py-1"><span class="text-slate-400"><?= (int) $q['position'] ?>.</span> <b><?= h($q['correct_option']) ?></b></div>
    <?php endforeach; ?>
  </div>
</main>

<script>window.PAPER = <?= js_json(['attemptId' => $attemptId, 'lang' => $lang, 'autoPrint' => isset($_GET['pdf'])]) ?>;</script>
<?php page_foot(['assets/paper.js']);
