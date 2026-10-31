<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$presets = Exams::presets();
$demo = Exams::demo();
$demoCount = (int) ($demo['question_count'] ?? 0);
$aiReady = Gemini::isConfigured();
$first = $presets[0] ?? null;

page_head('LibertyCBT.Ai — AI Mock Tests for Competitive Exams', 'min-h-screen bg-red-50 text-slate-900');
?>
<div class="min-h-screen flex flex-col">
  <header class="border-b border-red-100 bg-white backdrop-blur-md sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="logo-3d w-10 h-10 rounded-xl bg-gradient-to-br from-red-600 to-orange-500 flex items-center justify-center shadow-lg shadow-red-600/20 text-white font-extrabold tracking-tight">LC</div>
        <div>
          <h1 class="text-lg sm:text-xl font-bold tracking-tight"><span class="brand-gradient">LibertyCBT</span><span class="text-slate-900">.Ai</span></h1>
          <p class="text-xs text-slate-500">AI mock tests for JEE, NEET, SSC, Banking, CAT and more</p>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <?php site_nav('<a href="history.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 border border-red-200 text-sm font-semibold transition-all"><i data-lucide="history" class="w-4 h-4 text-red-600"></i><span>My Test</span></a>'); ?>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto w-full px-4 sm:px-6 py-8 flex-1">
    <div class="gradient-animated fade-in-up mb-8 rounded-2xl border border-indigo-500/30 p-5 sm:p-6 shadow-xl">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
        <div class="lg:col-span-2 space-y-2">
          <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-red-600">
            <?= icon('sparkles', 'w-4 h-4') ?> Your personal test series
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-slate-900">A fresh mock test for your exam, every time you practise</h2>
          <p class="text-sm text-slate-600 leading-relaxed">
            Pick your exam, choose how many questions you want, and get a full-length timed paper with
            <span class="text-red-700 font-medium">diagrams and charts</span>,
            <span class="text-emerald-700 font-medium">step-by-step solutions</span> and a detailed performance report.
          </p>
        </div>
        <div class="flex flex-col sm:flex-row lg:flex-col gap-3">
          <button type="button" data-launch <?= $aiReady ? '' : 'disabled title="AI test creation is not available right now"' ?>
            class="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2.5 transition-all cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            <?= icon('sparkles', 'w-5 h-5') ?><span>Create AI Mock Test (<span data-total-label><?= (int) ($first['defaultCount'] ?? 40) ?></span> questions)</span>
          </button>
          <?php if ($demo): ?>
          <form method="post" action="start.php">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="exam" value="demo">
            <button class="w-full py-2.5 px-4 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 font-medium text-sm border border-red-200 flex items-center justify-center gap-2 transition-all cursor-pointer">
              <?= icon('play', 'w-4 h-4 text-emerald-600') ?><span>Start the Free Mock Test (<?= $demoCount ?> questions)</span>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if (!$aiReady): ?>
    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-500/30 flex items-start gap-3 text-amber-800 text-sm">
      <?= icon('circle-alert', 'w-5 h-5 text-amber-600 mt-0.5') ?>
      <p>Creating new AI tests is not available right now. You can still take the ready-made mock tests below.</p>
    </div>
    <?php endif; ?>
    <?php if (!$demo): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-500/40 flex items-start gap-3 text-rose-800 text-sm">
      <?= icon('circle-alert', 'w-5 h-5 text-rose-600 mt-0.5') ?>
      <p>The ready-made mock tests are not available right now. Please try again shortly.</p>
    </div>
    <?php endif; ?>

    <div id="gen-error" class="hidden mb-6 p-4 rounded-xl bg-rose-50 border border-rose-500/40 text-rose-800 text-sm"></div>

    <?php $readyMade = array_values(array_filter(Exams::builtins(), fn ($b) => $b['slug'] !== 'demo')); ?>
    <?php if ($readyMade): ?>
    <div class="mb-8 fade-in-up">
      <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
          <?= icon('book-open-check', 'w-4 h-4 text-red-600') ?> Ready-made Practice Papers
        </span>
        <span class="text-xs text-slate-500">Offline question banks, no AI needed — attempt instantly</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($readyMade as $b): ?>
        <form method="post" action="start.php">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="exam" value="<?= h($b['slug']) ?>">
          <button class="tilt-card w-full h-full p-4 rounded-xl border border-red-100 bg-white hover:border-red-300 text-left transition-all cursor-pointer flex items-start gap-3">
            <span class="w-10 h-10 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center shrink-0">
              <?= icon(['rrb-ntpc' => 'train-front', 'wbcs-prelims' => 'landmark', 'wbjee' => 'calculator', 'jenpas-ug' => 'stethoscope', 'smfwbee' => 'heart-pulse'][$b['slug']] ?? 'book-open', 'w-5 h-5 text-red-600') ?>
            </span>
            <span>
              <span class="block font-bold text-slate-900 text-sm leading-snug"><?= h($b['title']) ?></span>
              <span class="block text-xs text-slate-500 mt-1"><?= (int) $b['question_count'] ?> questions</span>
            </span>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="mb-8">
      <div class="flex items-center justify-between mb-3">
        <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
          <?= icon('layers', 'w-4 h-4 text-red-600') ?> 1. Choose your exam
        </span>
        <span class="text-xs text-slate-500">Tap an exam to load its syllabus and marking scheme</span>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3" id="preset-grid">
        <?php foreach ($presets as $p): ?>
        <button type="button" data-preset="<?= h($p['id']) ?>" class="preset-card tilt-card p-4 rounded-xl border text-left transition-all cursor-pointer bg-white border-red-100 hover:border-red-200">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-red-100 text-red-700"><?= h($p['badge']) ?></span>
            <span class="preset-check hidden"><?= icon('circle-check', 'w-4 h-4 text-red-600') ?></span>
          </div>
          <div class="font-bold text-slate-900 text-base"><?= h($p['name']) ?></div>
          <div class="text-xs text-slate-500 mt-1"><?= count($p['sections']) ?> subjects • +<?= h($p['positiveMarks'] + 0) ?> / -<?= h($p['negativeMarks'] + 0) ?> Marks</div>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fade-in-up d1 mb-8 rounded-2xl bg-white border border-indigo-500/30 p-5 sm:p-6">
      <div class="flex items-center justify-between mb-1">
        <span class="text-sm font-semibold uppercase tracking-wider text-slate-800 flex items-center gap-2">
          <?= icon('target', 'w-4 h-4 text-red-600') ?> Or: practice ONE subject &amp; topic only
        </span>
      </div>
      <p class="text-xs text-slate-700 mb-4">Pick a subject and a specific topic (or type your own) for a focused, single-topic test — skips the full multi-subject blueprint below.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
          <label for="qt-subject" class="block text-xs font-medium text-slate-500 mb-1.5">Subject</label>
          <input id="qt-subject" type="text" list="subject-catalog" placeholder="e.g. Physics, or type your own"
            class="w-full px-3.5 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label for="qt-topic" class="block text-xs font-medium text-slate-500 mb-1.5">Specific topic</label>
          <input id="qt-topic" type="text" list="qt-topic-options" placeholder="e.g. Rotational Mechanics, or type your own"
            class="w-full px-3.5 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
          <datalist id="qt-topic-options"></datalist>
        </div>
        <div>
          <label for="qt-count" class="block text-xs font-medium text-slate-500 mb-1.5">Questions</label>
          <select id="qt-count" class="w-full px-3.5 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
            <option value="10">10</option><option value="20" selected>20</option><option value="30">30</option><option value="50">50</option>
          </select>
        </div>
        <div>
          <label for="qt-duration" class="block text-xs font-medium text-slate-500 mb-1.5">Duration (minutes)</label>
          <input id="qt-duration" type="number" min="5" max="180" value="30" placeholder="e.g. 30"
            class="w-full px-3 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <button type="button" id="qt-launch" <?= $aiReady ? '' : 'disabled title="AI test creation is not available right now"' ?>
          class="w-full py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
          <?= icon('sparkles', 'w-4 h-4') ?><span>Create Topic-wise Test</span>
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <div class="fade-in-up d2 lg:col-span-5 bg-white border border-red-100 rounded-2xl p-5 sm:p-6 space-y-5 h-fit">
        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-red-100 pb-3">
          <?= icon('sliders-vertical', 'w-4 h-4 text-red-600') ?> 2. Test settings &amp; marking
        </h3>

        <div>
          <label for="f-title" class="block text-xs font-medium text-slate-500 mb-1.5">Test name</label>
          <input id="f-title" type="text" maxlength="200" class="w-full px-3.5 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <span class="block text-xs font-medium text-slate-500 mb-1.5">Number of questions</span>
          <div class="grid grid-cols-4 gap-2" id="count-grid">
            <?php foreach ([[20, '20 questions', 'Quick test'], [40, '40 questions', 'Standard'], [60, '60 questions', 'Extended'], [100, '100 questions', 'Full length']] as [$n, $label, $sub]): ?>
            <button type="button" data-count="<?= $n ?>" class="count-btn py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer bg-red-50 border-red-100 text-slate-500 hover:border-red-200">
              <div class="text-sm"><?= $label ?></div>
              <div class="text-[10px] text-red-700"><?= $sub ?></div>
            </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label for="f-duration" class="block text-xs font-medium text-slate-500 mb-1.5">Time (minutes)</label>
            <input id="f-duration" type="number" min="10" max="180" class="w-full px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm">
          </div>
          <div>
            <label for="f-positive" class="block text-xs font-medium text-emerald-600 mb-1.5">Marks for correct (+)</label>
            <input id="f-positive" type="number" step="0.5" min="0.5" class="w-full px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-emerald-700 font-semibold text-sm">
          </div>
          <div>
            <label for="f-negative" class="block text-xs font-medium text-rose-600 mb-1.5">Negative marking (-)</label>
            <input id="f-negative" type="number" step="0.25" min="0" class="w-full px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-rose-700 font-semibold text-sm">
          </div>
        </div>

        <div>
          <label for="f-difficulty" class="block text-xs font-medium text-slate-500 mb-1.5">Difficulty level</label>
          <select id="f-difficulty" class="w-full px-3.5 py-2.5 rounded-xl bg-red-50 border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
            <option value="<?= h(Exams::DIFFICULTY_MIXES[0]) ?>">Standard (30% Easy, 50% Medium, 20% Hard)</option>
            <option value="<?= h(Exams::DIFFICULTY_MIXES[1]) ?>">Advanced / Mains (10% Easy, 40% Medium, 50% Hard)</option>
            <option value="<?= h(Exams::DIFFICULTY_MIXES[2]) ?>">Foundation / Prelims (50% Easy, 40% Medium, 10% Hard)</option>
          </select>
        </div>

        <div class="p-3.5 rounded-xl bg-red-50 border border-indigo-500/30 space-y-2">
          <label class="flex items-center justify-between cursor-pointer">
            <span class="flex items-center gap-2"><?= icon('image', 'w-4 h-4 text-red-600') ?><span class="text-xs font-bold text-slate-900">Include diagrams &amp; charts</span></span>
            <input id="f-diagrams" type="checkbox" checked class="w-4 h-4 accent-indigo-500 rounded cursor-pointer">
          </label>
          <p class="text-[11px] text-slate-500 leading-relaxed">
            Adds figures, graphs and data charts wherever a question needs them, and shows maths symbols such as
            <span class="text-emerald-700 font-serif">∫, λ, θ, √x, a/b</span> properly.
          </p>
        </div>
      </div>

      <div class="fade-in-up d3 lg:col-span-7 bg-white border border-red-100 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between border-b border-red-100 pb-3 mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
              <?= icon('book-open', 'w-4 h-4 text-red-600') ?> 3. Subjects &amp; topics (<span id="section-count">0</span>)
            </h3>
            <button type="button" id="add-section" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 text-red-700 border border-indigo-500/30 text-xs font-semibold cursor-pointer">
              <?= icon('plus', 'w-3.5 h-3.5') ?> Add Subject
            </button>
          </div>
          <div id="section-list" class="space-y-3 max-h-[380px] overflow-y-auto pr-1"></div>
        </div>
        <div class="mt-6 pt-4 border-t border-red-100 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="text-xs text-slate-500 flex items-center gap-2">
            <?= icon('shield-check', 'w-4 h-4 text-emerald-600') ?><span>Every question comes with a step-by-step solution</span>
          </div>
          <button type="button" data-launch <?= $aiReady ? '' : 'disabled title="AI test creation is not available right now"' ?>
            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            <?= icon('sparkles', 'w-4 h-4') ?><span>Create My Test</span>
          </button>
        </div>
      </div>
    </div>
  </main>

  <!-- Test creation progress -->
  <div id="gen-modal" class="hidden fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md items-center justify-center p-4">
    <div class="bg-white border border-indigo-500/40 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center shrink-0">
          <span id="gen-spinner"><?= icon('refresh-cw', 'w-6 h-6 text-red-600 animate-spin') ?></span>
        </div>
        <div>
          <h3 id="gen-title" class="text-lg font-bold text-slate-900">Creating your test…</h3>
          <p id="gen-sub" class="text-xs text-slate-500"></p>
        </div>
      </div>
      <div id="gen-batches" class="space-y-2.5 max-h-72 overflow-y-auto pr-1"></div>
      <div id="gen-fail" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-500/40 text-rose-800 text-sm"></div>
      <div class="pt-2 border-t border-red-100 flex items-center justify-between gap-3 text-xs text-slate-500">
        <span id="gen-progress"></span>
        <div class="flex items-center gap-3">
          <button type="button" id="gen-retry" class="hidden px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold cursor-pointer">Try again</button>
          <button type="button" id="gen-close" class="hidden px-3 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-slate-700 font-semibold cursor-pointer">Close</button>
        </div>
      </div>
    </div>
  </div>

  <form id="start-form" method="post" action="start.php" class="hidden">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="exam" id="start-exam" value="">
  </form>
</div>

<script>window.CFG = <?= js_json(['presets' => $presets]) ?>;</script>
<?php page_foot(['assets/config.js']);
