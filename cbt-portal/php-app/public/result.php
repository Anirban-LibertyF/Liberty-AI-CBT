<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$attemptId = (int) ($_GET['attempt'] ?? 0);
$attempt = Attempts::loadOwned($attemptId);
if ($attempt['status'] !== 'submitted') {
    redirect('exam.php?attempt=' . $attemptId);
}

$exam = Exams::find((int) $attempt['exam_id']);
$stats = Attempts::analytics($attemptId);
$rows = Attempts::reviewRows($attemptId);
$coach = Coach::stored($attemptId);
$pos = (float) $attempt['positive_marks'];
$neg = (float) $attempt['negative_marks'];
$sections = array_values(array_unique(array_column($rows, 'section')));
$reExam = ($exam['source'] ?? '') === 'demo' && $exam['slug'] ? $exam['slug'] : (string) $attempt['exam_id'];
$diagramCount = count(array_filter($rows, fn ($r) => $r['has_diagram'] && Svg::sanitize($r['svg_diagram']) !== null));

/** 80.0 -> "80", 12.5 -> "12.5" */
$n = fn ($v) => h(round((float) $v, 2) + 0);
$pct = fn ($part) => $stats['totalQuestions'] > 0 ? $part / $stats['totalQuestions'] * 100 : 0;
$avgSeconds = (int) round($stats['totalTimeSpent'] / max(1, $stats['attemptedCount']));

page_head($attempt['title'] . ' — Scorecard | LibertyCBT.Ai', 'min-h-screen bg-red-50 text-slate-900 pb-16');
?>
<header class="border-b border-red-100 bg-white sticky top-0 z-30 print:hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="logo-3d w-10 h-10 rounded-xl bg-gradient-to-br from-red-600 to-orange-500 flex items-center justify-center shadow-lg shadow-red-600/20 text-white font-extrabold tracking-tight">LC</div>
      <h1 class="text-lg sm:text-xl font-bold tracking-tight"><span class="brand-gradient">LibertyCBT</span><span class="text-slate-900">.Ai</span></h1>
    </div>
    <div class="flex items-center gap-3">
      <a href="history.php" class="px-4 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 text-xs sm:text-sm font-semibold border border-red-200 flex items-center gap-2">
        <?= icon('history', 'w-4 h-4') ?><span>My Test</span>
      </a>
      <?php site_nav(); ?>
    </div>
  </div>
</header>

<header class="border-b border-red-100 bg-white backdrop-blur-md sticky top-[65px] z-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center"><?= icon('award', 'w-6 h-6 text-emerald-600') ?></div>
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-slate-900"><?= h($attempt['title']) ?> — Scorecard</h1>
        <p class="text-xs text-slate-500">Section-wise scores, time spent on each question and step-by-step solutions</p>
      </div>
    </div>
    <div class="flex items-center gap-3 print:hidden">
      <button type="button" id="download-pdf" class="px-4 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 text-xs sm:text-sm font-semibold border border-red-200 flex items-center gap-2 cursor-pointer">
        <?= icon('download', 'w-4 h-4') ?><span>Download PDF</span>
      </button>
      <form method="post" action="start.php">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="exam" value="<?= h($reExam) ?>">
        <button class="px-4 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 text-xs sm:text-sm font-semibold border border-red-200 flex items-center gap-2 cursor-pointer">
          <?= icon('rotate-ccw', 'w-4 h-4') ?><span>Re-Attempt Same Paper</span>
        </button>
      </form>
    </div>
  </div>
</header>
<style>
  @media print {
    #coach-refresh, #sol-filters, #sol-section { display: none !important; }
    .solution.hidden { display: block !important; }
    .solution { break-inside: avoid; }
    body { background: #fff !important; }
    .gradient-animated, .brand-gradient { animation: none !important; background: #fef2f2 !important; -webkit-text-fill-color: #dc2626 !important; }
    .tilt-card { transform: none !important; box-shadow: none !important; }
  }
</style>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8">
  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="tilt-card fade-in-up gradient-animated p-5 rounded-2xl border border-indigo-500/40">
      <div class="text-xs font-semibold uppercase tracking-wider text-red-700">Net Total Score</div>
      <div class="text-3xl font-extrabold text-slate-900 mt-1"><?= $n($stats['totalScore']) ?> <span class="text-base font-medium text-slate-500">/ <?= $n($stats['maxScore']) ?></span></div>
      <div class="text-xs text-red-700 mt-2">Marking: +<?= $n($pos) ?> / -<?= $n($neg) ?></div>
    </div>
    <div class="tilt-card fade-in-up d1 p-5 rounded-2xl bg-white border border-red-100">
      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Accuracy</div>
      <div class="text-3xl font-extrabold text-emerald-600 mt-1"><?= $stats['accuracy'] ?>%</div>
      <div class="text-xs text-slate-500 mt-2"><?= $stats['correctCount'] ?> Correct of <?= $stats['attemptedCount'] ?> Attempted</div>
    </div>
    <div class="tilt-card fade-in-up d1 p-5 rounded-2xl bg-white border border-red-100">
      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Est. Percentile</div>
      <div class="text-3xl font-extrabold text-amber-600 mt-1"><?= $n($stats['estimatedPercentile']) ?>%ile</div>
      <div class="text-xs text-slate-500 mt-2">All-India Normalized Estimate</div>
    </div>
    <div class="tilt-card fade-in-up d2 p-5 rounded-2xl bg-white border border-red-100">
      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Attempt Breakdown</div>
      <div class="flex items-center gap-3 mt-2">
        <span class="text-sm font-bold text-emerald-600"><?= $stats['correctCount'] ?> Right</span>
        <span class="text-sm font-bold text-rose-600"><?= $stats['incorrectCount'] ?> Wrong</span>
        <span class="text-sm font-bold text-slate-500"><?= $stats['unattemptedCount'] ?> Skipped</span>
      </div>
      <div class="w-full h-2 rounded-full bg-red-100 mt-3 overflow-hidden flex">
        <div style="width:<?= round($pct($stats['correctCount']), 2) ?>%" class="bg-emerald-500 transition-all duration-700"></div>
        <div style="width:<?= round($pct($stats['incorrectCount']), 2) ?>%" class="bg-rose-500 transition-all duration-700"></div>
      </div>
    </div>
    <div class="tilt-card fade-in-up d3 p-5 rounded-2xl bg-white border border-red-100 col-span-2 lg:col-span-1">
      <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Time Taken</div>
      <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono"><?= format_clock($stats['totalTimeSpent']) ?></div>
      <div class="text-xs text-slate-500 mt-2">Avg <?= $avgSeconds ?>s per attempted Q</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-7 bg-white border border-red-100 rounded-2xl p-5 sm:p-6">
      <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 mb-4"><?= icon('bar-chart-3', 'w-5 h-5 text-red-600') ?> Section-Wise Score &amp; Time Breakdown</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-red-100 text-xs uppercase text-slate-500">
              <th class="py-3 pr-4">Section</th><th class="py-3 px-2 text-center">Attempted</th>
              <th class="py-3 px-2 text-center text-emerald-600">Correct</th><th class="py-3 px-2 text-center text-rose-600">Wrong</th>
              <th class="py-3 px-2 text-center">Time</th><th class="py-3 pl-3 text-right">Net Score</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-red-100">
            <?php foreach ($stats['sectionBreakdown'] as $s): ?>
            <tr class="hover:bg-red-50">
              <td class="py-3.5 pr-4 font-semibold text-slate-900"><?= h($s['section']) ?></td>
              <td class="py-3.5 px-2 text-center text-slate-600"><?= $s['attempted'] ?> / <?= $s['total'] ?></td>
              <td class="py-3.5 px-2 text-center font-semibold text-emerald-600"><?= $s['correct'] ?></td>
              <td class="py-3.5 px-2 text-center font-semibold text-rose-600"><?= $s['incorrect'] ?></td>
              <td class="py-3.5 px-2 text-center font-mono text-xs text-slate-500"><?= format_clock($s['timeSpent']) ?></td>
              <td class="py-3.5 pl-3 text-right font-bold text-red-700"><?= $n($s['score']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="lg:col-span-5 bg-gradient-to-br from-red-50 via-white to-white border border-indigo-500/30 rounded-2xl p-5 sm:p-6">
      <div class="flex items-center justify-between mb-3">
        <div class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-red-600"><?= icon('brain', 'w-4 h-4') ?> AI Personal Performance Coach</div>
        <button id="coach-refresh" class="hidden text-xs text-red-700 hover:underline items-center gap-1 cursor-pointer"><?= icon('refresh-cw', 'w-3 h-3') ?> Refresh</button>
      </div>
      <div id="coach-body"></div>
    </div>
  </div>

  <div class="bg-white border border-red-100 rounded-2xl p-5 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-red-100 pb-4">
      <div>
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2"><?= icon('file-text', 'w-5 h-5 text-red-600') ?> Verified Answer Key &amp; Step-by-Step Explanations (<span id="sol-count"><?= count($rows) ?></span>)</h3>
        <p class="text-xs text-slate-500">Filter by status or subject section to review your mistakes and shortcuts</p>
      </div>
      <div class="flex flex-wrap items-center gap-2" id="sol-filters">
        <?php foreach ([['ALL', 'All (' . count($rows) . ')'], ['DIAGRAM', "Diagrams ($diagramCount)"], ['INCORRECT', "Wrong ({$stats['incorrectCount']})"], ['UNATTEMPTED', "Unattempted ({$stats['unattemptedCount']})"], ['CORRECT', "Correct ({$stats['correctCount']})"]] as [$id, $label]): ?>
        <button data-filter="<?= $id ?>" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer bg-red-100 text-slate-600 hover:bg-red-200"><?= h($label) ?></button>
        <?php endforeach; ?>
        <select id="sol-section" class="px-3 py-1.5 rounded-lg bg-red-50 border border-red-200 text-xs text-slate-700">
          <option value="ALL">All Sections</option>
          <?php foreach ($sections as $s): ?><option value="<?= h($s) ?>"><?= h($s) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="space-y-4" id="sol-list">
      <?php foreach ($rows as $q):
        $svg = $q['has_diagram'] ? Svg::sanitize($q['svg_diagram']) : null;
        $status = !$q['attempted'] ? 'unattempted' : ($q['correct'] ? 'correct' : 'incorrect');
      ?>
      <div class="solution p-5 rounded-xl bg-red-50 border border-red-100 space-y-4"
           data-status="<?= $status ?>" data-diagram="<?= $svg !== null ? 1 : 0 ?>" data-section="<?= h($q['section']) ?>">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-1 rounded-md bg-red-100 text-slate-900 font-bold text-xs">Q.<?= (int) $q['position'] ?></span>
            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/15 text-red-700 border border-indigo-500/30 text-xs"><?= h($q['section']) ?> • <?= h($q['topic']) ?></span>
            <?php if ($svg !== null): ?>
            <span class="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-700 border border-purple-500/30 text-xs font-semibold flex items-center gap-1"><?= icon('image', 'w-3 h-3') ?> Diagram</span>
            <?php endif; ?>
            <span class="text-xs text-slate-500 font-mono flex items-center gap-1"><?= icon('clock', 'w-3.5 h-3.5') ?> <?= (int) $q['time_spent_seconds'] ?>s spent</span>
          </div>
          <div>
            <?php if ($status === 'unattempted'): ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-slate-600">Unattempted (0 Marks)</span>
            <?php elseif ($status === 'correct'): ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-700 border border-emerald-500/30"><?= icon('circle-check', 'w-3.5 h-3.5') ?> Correct (+<?= $n($pos) ?>)</span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-700 border border-rose-500/30"><?= icon('circle-x', 'w-3.5 h-3.5') ?> Incorrect (-<?= $n($neg) ?>)</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="text-sm sm:text-base font-medium text-slate-900 whitespace-pre-line"><?= MathRenderer::render($q['question_text']) ?></div>

        <?php if ($svg !== null): ?>
        <div class="my-4 rounded-2xl bg-white border-2 border-indigo-100 shadow-sm overflow-hidden">
          <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 text-xs text-slate-600 font-bold text-indigo-700 flex items-center gap-1.5">
            <?= icon('image', 'w-3.5 h-3.5 text-indigo-600') ?> Figure for Q.<?= (int) $q['position'] ?> (<?= h($q['topic']) ?>)
          </div>
          <div class="p-4 flex items-center justify-center bg-white max-h-[300px] overflow-auto [&>svg]:max-w-full [&>svg]:h-auto [&>svg]:max-h-[260px]"><?= $svg ?></div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
          <?php foreach ($q['options'] as $o):
            $right = $o['label'] === $q['correct_option'];
            $mine = $q['attempted'] && $o['label'] === $q['selected_option'];
            $box = $right ? 'bg-emerald-50 border-emerald-500/60 text-emerald-800 font-semibold'
                 : ($mine ? 'bg-rose-50 border-rose-500/60 text-rose-800' : 'bg-white border-red-100 text-slate-600');
          ?>
          <div class="p-3 rounded-xl border text-xs sm:text-sm flex items-center justify-between gap-2 <?= $box ?>">
            <div class="flex items-center gap-2.5">
              <span class="w-6 h-6 rounded-full bg-red-100 text-slate-900 font-bold flex items-center justify-center text-xs shrink-0"><?= h($o['label']) ?></span>
              <?= MathRenderer::render($o['option_text']) ?>
            </div>
            <?php if ($right): ?><span class="text-[11px] font-bold text-emerald-600 shrink-0">✓ Correct Key</span>
            <?php elseif ($mine): ?><span class="text-[11px] font-bold text-rose-600 shrink-0">✗ Your Choice</span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="p-3.5 rounded-xl bg-red-50 border border-indigo-500/25 text-xs sm:text-sm text-slate-700 space-y-1">
          <div class="font-bold text-red-700 flex items-center gap-1.5"><?= icon('sparkles', 'w-4 h-4') ?> Step-by-Step Solution &amp; Concept Explanation:</div>
          <p class="text-slate-600 leading-relaxed"><?= MathRenderer::render($q['explanation']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<script>window.RESULT = <?= js_json(['attemptId' => $attemptId, 'coach' => $coach, 'aiReady' => Gemini::isConfigured(), 'autoPrint' => isset($_GET['pdf'])]) ?>;</script>
<?php page_foot(['assets/result.js']);
