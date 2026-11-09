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
  <link rel="icon" type="image/svg+xml" href="<?= h(brand_favicon()) ?>">
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

    /* ---------- MasterjeeCBT.Ai: brand gradient, 3D hover & transition polish ---------- */
    .brand-gradient {
      background-image: linear-gradient(90deg, #dc2626, #f97316, #dc2626);
      background-size: 200% auto;
      -webkit-background-clip: text; background-clip: text; color: transparent;
      animation: brand-sheen 5s linear infinite;
    }
    @keyframes brand-sheen { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }

    .gradient-animated {
      background: linear-gradient(120deg, #fef2f2, #ffffff, #fee2e2, #ffffff, #fef2f2);
      background-size: 300% 300%;
      animation: gradient-flow 14s ease infinite;
    }
    @keyframes gradient-flow {
      0%   { background-position: 0% 50%; }
      50%  { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
    }

    .logo-3d {
      transform-style: preserve-3d;
      transition: transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s ease;
    }
    .logo-3d:hover { transform: rotateY(18deg) rotateX(8deg) translateZ(2px) scale(1.06); }

    .tilt-card {
      transform-style: preserve-3d;
      transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
      will-change: transform;
    }
    .tilt-card:hover {
      box-shadow: 0 18px 32px -14px rgba(220,38,38,.28), 0 4px 10px -4px rgba(0,0,0,.08);
    }
    @media (prefers-reduced-motion: reduce) {
      .tilt-card, .logo-3d, .brand-gradient, .gradient-animated { animation: none !important; transition: none !important; }
    }

    .fade-in-up { animation: fade-in-up .55s cubic-bezier(.2,.7,.3,1) both; }
    @keyframes fade-in-up { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
    .fade-in-up.d1 { animation-delay: .06s; }
    .fade-in-up.d2 { animation-delay: .12s; }
    .fade-in-up.d3 { animation-delay: .18s; }

    a, button { transition: background-color .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease, transform .15s ease; }
    button:active { transform: scale(0.97); }

    /* Mobile safety: never allow horizontal scroll from a stray wide element */
    html, body { overflow-x: hidden; }
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

/** The MasterjeeCBT.Ai mark: a bold "M" (peaks = mastery) with a tick-dot, on the brand gradient. */
function brand_mark(int $px = 40): string
{
    static $n = 0;
    $id = 'bm' . (++$n);
    return '<svg class="logo-3d shrink-0 rounded-xl shadow-lg shadow-red-600/25" width="' . $px . '" height="' . $px . '" viewBox="0 0 48 48" role="img" aria-label="MasterjeeCBT.Ai">'
        . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#dc2626"/><stop offset="1" stop-color="#f97316"/></linearGradient></defs>'
        . '<rect width="48" height="48" rx="12" fill="url(#' . $id . ')"/>'
        . '<path d="M12.5 33V15.5L24 27.5l11.5-12V33" fill="none" stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<circle cx="24" cy="36.5" r="2.1" fill="#fff" fill-opacity=".92"/></svg>';
}

function brand_favicon(): string
{
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 48'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='#dc2626'/><stop offset='1' stop-color='#f97316'/></linearGradient></defs><rect width='48' height='48' rx='12' fill='url(#g)'/><path d='M12.5 33V15.5L24 27.5l11.5-12V33' fill='none' stroke='white' stroke-width='4.2' stroke-linecap='round' stroke-linejoin='round'/><circle cx='24' cy='36.5' r='2.1' fill='white'/></svg>";
    return 'data:image/svg+xml,' . rawurlencode($svg);
}

/** Logo + wordmark (+ optional tagline) used in every page header. */
function brand_lockup(string $tagline = ''): string
{
    return '<a href="index.php" class="flex items-center gap-3 no-underline">' . brand_mark(40)
        . '<span><span class="block text-lg sm:text-xl font-extrabold tracking-tight leading-tight"><span class="brand-gradient">Masterjee</span><span class="text-slate-900">CBT.Ai</span></span>'
        . ($tagline !== '' ? '<span class="block text-xs text-slate-500">' . h($tagline) . '</span>' : '')
        . '</span></a>';
}

/** Header account area: Log in / Register buttons, or the signed-in user with a dropdown. */
function account_menu(): void
{
    $u = Auth::user();
    ?>
    <div class="relative" id="account-slot">
    <?php if (!$u): ?>
      <div class="flex items-center gap-2">
        <button type="button" data-auth-open="login" class="px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:bg-red-100 transition-colors cursor-pointer">Log in</button>
        <button type="button" data-auth-open="register" class="px-3.5 py-2 rounded-xl text-sm font-bold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors cursor-pointer">Register</button>
      </div>
    <?php else: ?>
      <button type="button" id="account-btn" class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 hover:bg-red-100 border border-red-100 text-sm font-semibold text-slate-700 transition-all cursor-pointer">
        <span class="w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold shrink-0"><?= h(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
        <span class="max-w-[9rem] truncate">Hi, <?= h(explode(' ', $u['name'])[0]) ?></span>
        <?= icon('chevron-down', 'w-3.5 h-3.5 text-slate-500') ?>
      </button>
      <div id="account-dropdown" class="hidden absolute right-0 mt-2 w-60 rounded-xl bg-white border border-red-100 shadow-xl py-1.5 z-40">
        <div class="px-3.5 py-2 border-b border-red-50">
          <div class="text-sm font-semibold text-slate-900 truncate"><?= h($u['name']) ?></div>
          <div class="text-xs text-slate-500 truncate"><?= h($u['email']) ?></div>
        </div>
        <button type="button" id="account-logout" class="w-full flex items-center gap-2 px-3.5 py-2 text-sm text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer">
          <?= icon('log-out', 'w-4 h-4') ?><span>Logout</span>
        </button>
      </div>
    <?php endif; ?>
    </div>
    <?php
}

/** Login / Register modal (minimal). Behaviour lives in assets/auth.js. */
function auth_modal(): void
{
    $field = 'w-full px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm placeholder-slate-400 focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 transition';
    $label = 'block text-xs font-medium text-slate-600 mb-1.5';
    ?>
<div id="auth-modal" class="hidden fixed inset-0 z-[60] items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="auth-title">
  <div class="relative w-full max-w-[400px] bg-white rounded-2xl shadow-2xl shadow-slate-900/20 border border-slate-100 p-6 sm:p-7 max-h-[94vh] overflow-y-auto">
    <button type="button" id="auth-close" aria-label="Close" class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center cursor-pointer"><?= icon('x', 'w-4 h-4') ?></button>
    <div class="flex flex-col items-center text-center mb-5">
      <?= brand_mark(44) ?>
      <h2 id="auth-title" class="mt-3 text-lg font-bold text-slate-900">Welcome to <span class="brand-gradient">Masterjee</span>CBT.Ai</h2>
      <p id="auth-sub" class="text-xs text-slate-500 mt-0.5">Log in to start your test</p>
    </div>

    <div class="grid grid-cols-2 p-1 mb-5 rounded-xl bg-slate-100 text-sm font-semibold" role="tablist">
      <button type="button" data-auth-tab="login" class="py-2 rounded-lg cursor-pointer">Log in</button>
      <button type="button" data-auth-tab="register" class="py-2 rounded-lg cursor-pointer">Register</button>
    </div>

    <p id="auth-error" class="hidden mb-4 px-3 py-2 rounded-lg bg-rose-50 border border-rose-100 text-rose-700 text-xs" role="alert"></p>

    <form id="auth-login" class="space-y-4" novalidate>
      <div><label class="<?= $label ?>" for="li-id">Email or mobile number</label>
        <input id="li-id" name="identifier" type="text" autocomplete="username" class="<?= $field ?>" placeholder="you@example.com"></div>
      <div><label class="<?= $label ?>" for="li-pw">Password</label>
        <div class="relative"><input id="li-pw" name="password" type="password" autocomplete="current-password" class="<?= $field ?> pr-10" placeholder="Your password">
          <button type="button" data-toggle-pw class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 cursor-pointer" aria-label="Show password"><?= icon('eye', 'w-4 h-4') ?></button></div></div>
      <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold shadow-md shadow-red-600/20 cursor-pointer disabled:opacity-60">Log in</button>
      <p class="text-center text-xs text-slate-500">New here? <button type="button" data-auth-tab="register" class="font-semibold text-red-600 hover:underline cursor-pointer">Create an account</button></p>
    </form>

    <form id="auth-register" class="hidden space-y-3.5" novalidate>
      <div><label class="<?= $label ?>" for="rg-name">Full name</label>
        <input id="rg-name" name="name" type="text" autocomplete="name" maxlength="80" class="<?= $field ?>" placeholder="Your name"></div>
      <div><label class="<?= $label ?>" for="rg-phone">Mobile number</label>
        <div class="flex gap-2">
          <select id="rg-cc" aria-label="Country code" class="shrink-0 w-[5.5rem] px-2.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-sm focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 transition">
            <option value="+91" selected>🇮🇳 +91</option>
          </select>
          <input id="rg-phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" maxlength="10" class="<?= $field ?>" placeholder="10-digit Indian mobile number">
        </div>
        <p class="mt-1 text-[11px] text-slate-400">Indian mobile numbers only (starts with 6, 7, 8 or 9).</p>
      </div>
      <div><label class="<?= $label ?>" for="rg-email">Email</label>
        <input id="rg-email" name="email" type="email" autocomplete="email" class="<?= $field ?>" placeholder="you@example.com"></div>
      <div><label class="<?= $label ?>" for="rg-pw">Create password</label>
        <div class="relative"><input id="rg-pw" name="password" type="password" autocomplete="new-password" maxlength="200" class="<?= $field ?> pr-10" placeholder="Min 8 characters">
          <button type="button" data-toggle-pw class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 cursor-pointer" aria-label="Show password"><?= icon('eye', 'w-4 h-4') ?></button></div>
        <div class="mt-2 flex items-center gap-2" aria-live="polite">
          <div class="flex-1 grid grid-cols-3 gap-1.5"><span class="pw-bar h-1.5 rounded-full bg-slate-200 transition-colors"></span><span class="pw-bar h-1.5 rounded-full bg-slate-200 transition-colors"></span><span class="pw-bar h-1.5 rounded-full bg-slate-200 transition-colors"></span></div>
          <span id="pw-label" class="text-[11px] font-semibold w-14 text-right text-slate-400">&nbsp;</span>
        </div>
        <ul id="pw-rules" class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-slate-500">
          <li data-rule="len">8+ characters</li><li data-rule="upper">1 capital letter</li>
          <li data-rule="digit">1 digit</li><li data-rule="special">1 special character</li>
        </ul>
      </div>
      <div><label class="<?= $label ?>" for="rg-pw2">Confirm password</label>
        <div class="relative"><input id="rg-pw2" name="confirm" type="password" autocomplete="new-password" maxlength="200" class="<?= $field ?> pr-10" placeholder="Re-enter your password">
          <button type="button" data-toggle-pw class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 cursor-pointer" aria-label="Show password"><?= icon('eye', 'w-4 h-4') ?></button></div>
        <p id="pw-match" class="mt-1 text-[11px] h-4"></p>
      </div>
      <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold shadow-md shadow-red-600/20 cursor-pointer disabled:opacity-60">Create account</button>
      <p class="text-center text-xs text-slate-500">Already registered? <button type="button" data-auth-tab="login" class="font-semibold text-red-600 hover:underline cursor-pointer">Log in</button></p>
    </form>
  </div>
</div>
    <?php
}

/** Closes the document, loading page scripts (paths relative to public/). */
function page_foot(array $scripts = []): void
{
    ?>
<script>window.CSRF = <?= js_json(csrf_token()) ?>; window.USER = <?= js_json(Auth::user() ? ['name' => Auth::user()['name']] : null) ?>;</script>
<?php if (!Auth::user()) { auth_modal(); } ?>
<script src="assets/common.js"></script>
<script src="assets/auth.js"></script>
<?php foreach ($scripts as $src): ?>
<script src="<?= h($src) ?>"></script>
<?php endforeach; ?>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
<?php
}
