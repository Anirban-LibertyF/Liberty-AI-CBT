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
