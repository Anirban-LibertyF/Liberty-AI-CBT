<?php
declare(strict_types=1);

/** Opens the HTML document. $bodyClass carries the page background/text colours. */
function page_head(string $title, string $bodyClass): void
{
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'><rect width='64' height='64' rx='14' fill='%23dc2626'/><text x='32' y='42' font-family='Arial,Helvetica,sans-serif' font-size='26' font-weight='bold' fill='white' text-anchor='middle'>MJ</text></svg>">
  <script src="https://cdn.tailwindcss.com/3.4.4"></script>
  <script>
    // Red & white theme: the accent colours used in the markup (indigo, blue) are remapped to Tailwind's red scale.
    const red = { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d', 950: '#450a0a' };
    tailwind.config = { theme: { extend: { colors: { indigo: red, blue: red } } } };
  </script>
  <script src="https://unpkg.com/lucide@0.400.0/dist/umd/lucide.min.js"></script>
  <style>
    html, body { height: 100%; margin: 0; }
    body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
    .math-rendered { font-family: "Cambria Math", "STIX Two Math", Georgia, serif; }
    svg[data-lucide], .lucide { flex-shrink: 0; }
  </style>
</head>
<body class="<?= h($bodyClass) ?>">
<?php
}

/**
 * Right-hand nav cluster, same on every page: any page-specific buttons first,
 * then always "Create New Test" and the profile icon, in that fixed order.
 *
 * @param string $extraHtml pre-rendered HTML for page-specific buttons (My Test, Download PDF, Re-Attempt, ...)
 */
function site_nav(string $extraHtml = ''): void
{
    echo $extraHtml;
    ?>
    <a href="index.php" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg shadow-indigo-600/20">
      <?= icon('sparkles', 'w-4 h-4') ?><span>Create New Test</span>
    </a>
    <?php
    account_menu();
}

/** Account avatar + name with a dropdown (dummy demo user — no real auth in this app). */
function account_menu(): void
{
    ?>
    <div class="relative" id="account-menu">
      <button type="button" id="account-btn" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 border border-red-100 text-sm font-semibold text-slate-700 transition-all cursor-pointer">
        <span class="w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold shrink-0"><?= icon('user', 'w-4 h-4') ?></span>
        <span>Hi, Prasenjit</span>
        <?= icon('chevron-down', 'w-3.5 h-3.5 text-slate-500') ?>
      </button>
      <div id="account-dropdown" class="hidden absolute right-0 mt-2 w-44 rounded-xl bg-white border border-red-100 shadow-xl py-1.5 z-40">
        <button type="button" id="account-logout" class="w-full flex items-center gap-2 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer">
          <?= icon('log-out', 'w-4 h-4') ?><span>Logout</span>
        </button>
      </div>
    </div>
    <script>
      (() => {
        const btn = document.getElementById('account-btn');
        const dd = document.getElementById('account-dropdown');
        btn.addEventListener('click', (e) => { e.stopPropagation(); dd.classList.toggle('hidden'); });
        document.addEventListener('click', () => dd.classList.add('hidden'));
        document.getElementById('account-logout').addEventListener('click', () => alert('This is a demo account — logout is not wired up yet.'));
      })();
    </script>
    <?php
}

/** Closes the document, loading page scripts (paths relative to public/). */
function page_foot(array $scripts = []): void
{
    ?>
<script>window.CSRF = <?= js_json(csrf_token()) ?>;</script>
<script src="assets/common.js"></script>
<?php foreach ($scripts as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
<?php
}
