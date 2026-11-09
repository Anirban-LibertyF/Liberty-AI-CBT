// Helpers shared by every page.
window.esc = (s) =>
  String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

/** POST JSON to an /api endpoint. Throws Error(message) with the server's message on failure. */
window.api = async function api(path, body, { keepalive = false } = {}) {
  const res = await fetch(path, {
    method: 'POST',
    keepalive,
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF },
    body: JSON.stringify(body ?? {}),
  });
  let data = {};
  try {
    data = await res.json();
  } catch {
    /* non-JSON error page */
  }
  if (!res.ok) throw new Error(data.error || `Request failed (${res.status})`);
  return data;
};

window.refreshIcons = () => window.lucide && window.lucide.createIcons();

window.formatClock = (seconds) => {
  seconds = Math.max(0, Math.floor(seconds));
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  const p = (n) => String(n).padStart(2, '0');
  return h > 0 ? `${p(h)}:${p(m)}:${p(s)}` : `${p(m)}:${p(s)}`;
};

/** lucide-style icon placeholder for JS-built markup; call refreshIcons() afterwards. */
window.ico = (name, cls = 'w-4 h-4') => `<i data-lucide="${name}" class="${cls}"></i>`;

// Subtle 3D tilt on hover for elements with class "tilt-card". Skipped on touch/coarse-pointer
// devices and when the user prefers reduced motion, since it's purely decorative.
(() => {
  const enabled = window.matchMedia('(pointer: fine)').matches
    && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!enabled) return;

  const onMove = (e) => {
    const el = e.currentTarget;
    const r = el.getBoundingClientRect();
    const px = (e.clientX - r.left) / r.width - 0.5;
    const py = (e.clientY - r.top) / r.height - 0.5;
    el.style.transform = `perspective(700px) rotateX(${(-py * 7).toFixed(2)}deg) rotateY(${(px * 7).toFixed(2)}deg) translateZ(4px)`;
  };
  const onLeave = (e) => { e.currentTarget.style.transform = ''; };

  const bind = (el) => {
    if (el.dataset.tiltBound) return;
    el.dataset.tiltBound = '1';
    el.addEventListener('mousemove', onMove);
    el.addEventListener('mouseleave', onLeave);
  };

  new MutationObserver(() => document.querySelectorAll('.tilt-card').forEach(bind))
    .observe(document.body, { childList: true, subtree: true });
  document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.tilt-card').forEach(bind));
})();

/**
 * App-wide popup for alerts and errors (replaces inline banners and browser alert()).
 *   notify({ title, message, type: 'error' | 'warning' | 'info' | 'success', button: 'OK' })
 * Returns a promise that resolves when the popup is dismissed.
 */
window.notify = function notify({ title = 'Notice', message = '', type = 'error', button = 'OK' } = {}) {
  const look = {
    error: ['circle-alert', 'bg-rose-50 text-rose-600 ring-rose-100'],
    warning: ['triangle-alert', 'bg-amber-50 text-amber-600 ring-amber-100'],
    info: ['info', 'bg-sky-50 text-sky-600 ring-sky-100'],
    success: ['circle-check', 'bg-emerald-50 text-emerald-600 ring-emerald-100'],
  }[type] || ['circle-alert', 'bg-rose-50 text-rose-600 ring-rose-100'];
  return new Promise((resolve) => {
    const prev = document.activeElement;
    const wrap = document.createElement('div');
    wrap.className = 'fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/45 backdrop-blur-sm';
    wrap.setAttribute('role', 'alertdialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.innerHTML = `
      <div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl shadow-slate-900/25 border border-slate-100 p-6 text-center" style="animation: fade-in-up .25s ease both">
        <div class="mx-auto w-12 h-12 rounded-full ring-8 flex items-center justify-center ${look[1]}">${ico(look[0], 'w-6 h-6')}</div>
        <h3 class="mt-4 text-base font-bold text-slate-900">${esc(title)}</h3>
        <p class="mt-1.5 text-sm text-slate-600 leading-relaxed whitespace-pre-line">${esc(message)}</p>
        <button type="button" class="mt-5 w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-bold shadow-md shadow-red-600/20 cursor-pointer">${esc(button)}</button>
      </div>`;
    const close = () => {
      document.removeEventListener('keydown', onKey, true);
      wrap.remove();
      prev && prev.focus && prev.focus();
      resolve();
    };
    const onKey = (e) => {
      if (e.key === 'Escape' || e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); close(); }
    };
    document.addEventListener('keydown', onKey, true);
    wrap.addEventListener('mousedown', (e) => { if (e.target === wrap) close(); });
    wrap.querySelector('button').addEventListener('click', close);
    document.body.appendChild(wrap);
    window.refreshIcons();
    wrap.querySelector('button').focus();
  });
};


/** "Download PDF" language chooser: opens the printable paper (all questions + answers + explanations) in the chosen language. */
window.pickPdfLanguage = function pickPdfLanguage(attemptId) {
  document.getElementById('pdf-lang-modal')?.remove();
  const wrap = document.createElement('div');
  wrap.id = 'pdf-lang-modal';
  wrap.className = 'fixed inset-0 z-[80] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm';
  wrap.innerHTML = `
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl text-center" role="dialog" aria-modal="true">
      <div class="text-lg font-extrabold text-slate-900">Download PDF</div>
      <p class="text-sm text-slate-500 mt-1">All questions, correct answers and explanations. Choose the language / ভাষা বেছে নিন</p>
      <div class="grid grid-cols-2 gap-3 mt-5">
        <button data-l="en" class="py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold cursor-pointer">English</button>
        <button data-l="bn" class="py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold cursor-pointer">বাংলা</button>
      </div>
      <button data-l="" class="mt-4 text-xs font-semibold text-slate-500 hover:text-slate-700 cursor-pointer">Cancel</button>
    </div>`;
  const close = () => { wrap.remove(); document.removeEventListener('keydown', onKey); };
  const onKey = (e) => { if (e.key === 'Escape') close(); };
  wrap.addEventListener('click', (e) => {
    if (e.target === wrap) return close();
    const b = e.target.closest('[data-l]');
    if (!b) return;
    close();
    if (b.dataset.l) window.location.href = `paper.php?attempt=${attemptId}&lang=${b.dataset.l}&pdf=1`;
  });
  document.addEventListener('keydown', onKey);
  document.body.appendChild(wrap);
};
