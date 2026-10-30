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
