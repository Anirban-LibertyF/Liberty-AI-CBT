<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

start_session();
$ids = array_keys($_SESSION['own']['attempt'] ?? []);
$history = Attempts::historyForSession($ids);

page_head('My Test — MasterJee.AI', 'min-h-screen bg-red-50 text-slate-900 pb-16');
?>
<header class="border-b border-red-100 bg-white backdrop-blur-md sticky top-0 z-30">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center shadow-lg shadow-red-600/20 text-white font-extrabold tracking-tight">MJ</div>
      <h1 class="text-lg sm:text-xl font-bold tracking-tight text-slate-900">MasterJee<span class="text-red-600">.AI</span></h1>
    </div>
    <div class="flex items-center gap-3">
      <?php site_nav(); ?>
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <?php if (!$history): ?>
  <div class="p-8 rounded-2xl bg-white border border-red-100 text-center text-slate-500">
    <?= icon('inbox', 'w-8 h-8 text-red-300 mx-auto mb-3') ?>
    <p class="text-sm">No submitted tests yet on this device.</p>
    <a href="index.php" class="inline-block mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold">Take a test</a>
  </div>
  <?php else: ?>
  <div class="mb-4 flex flex-wrap items-center gap-3">
    <div class="relative flex-1 min-w-[220px]">
      <?= icon('search', 'w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2') ?>
      <input id="test-search" type="text" placeholder="Search by exam name..."
        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-white border border-red-100 text-slate-900 text-sm focus:outline-none focus:border-indigo-500">
    </div>
    <select id="test-sort" class="px-3.5 py-2.5 rounded-xl bg-white border border-red-100 text-slate-700 text-sm font-semibold focus:outline-none focus:border-indigo-500">
      <option value="newest">Newest first</option>
      <option value="oldest">Oldest first</option>
    </select>
  </div>
  <div class="space-y-3" id="test-list">
    <?php foreach ($history as $r): ?>
    <div class="test-row p-4 sm:p-5 rounded-2xl bg-white border border-red-100 flex flex-wrap items-center justify-between gap-4"
         data-ts="<?= (int) strtotime((string) $r['submitted_at']) ?>" data-title="<?= h(mb_strtolower($r['title'])) ?>">
      <div class="min-w-0 flex items-start gap-3">
        <span class="row-num w-7 h-7 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5"></span>
        <div class="min-w-0">
          <div class="font-bold text-slate-900 text-base truncate"><?= h($r['title']) ?></div>
          <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs">
            <span class="text-slate-500"><?= h(local_time((string) $r['submitted_at'])) ?> IST</span>
            <span class="font-bold text-red-700">Score: <?= h(round((float) $r['total_score'], 2) + 0) ?> / <?= h(round((float) $r['max_score'], 2) + 0) ?></span>
            <span class="font-semibold text-emerald-600"><?= (int) $r['correct_count'] ?> Correct</span>
            <span class="font-semibold text-rose-600"><?= (int) $r['incorrect_count'] ?> Wrong</span>
            <span class="text-slate-500"><?= (int) $r['unattempted_count'] ?> Skipped</span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <a href="result.php?attempt=<?= (int) $r['id'] ?>" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold flex items-center gap-1.5">
          <?= icon('eye', 'w-4 h-4') ?><span>View Response</span>
        </a>
        <a href="result.php?attempt=<?= (int) $r['id'] ?>&pdf=1" class="px-4 py-2 rounded-xl bg-red-100 hover:bg-red-200 text-slate-700 text-xs sm:text-sm font-semibold border border-red-200 flex items-center gap-1.5">
          <?= icon('download', 'w-4 h-4') ?><span>Download PDF</span>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p id="test-empty" class="hidden p-6 text-center text-sm text-slate-500">No tests match your search.</p>
  <script>
    (() => {
      const list = document.getElementById('test-list');
      const rows = Array.from(list.children);
      const search = document.getElementById('test-search');
      const sort = document.getElementById('test-sort');

      function render() {
        const q = search.value.trim().toLowerCase();
        const dir = sort.value === 'oldest' ? 1 : -1;
        const sorted = rows.slice().sort((a, b) => dir * (Number(a.dataset.ts) - Number(b.dataset.ts)));
        let shown = 0;
        sorted.forEach((row) => {
          const match = !q || row.dataset.title.includes(q);
          row.classList.toggle('hidden', !match);
          list.appendChild(row);
          if (match) {
            shown++;
            row.querySelector('.row-num').textContent = shown;
          }
        });
        document.getElementById('test-empty').classList.toggle('hidden', shown > 0);
      }
      search.addEventListener('input', render);
      sort.addEventListener('change', render);
      render();
    })();
  </script>
  <?php endif; ?>
</main>
<?php page_foot();
