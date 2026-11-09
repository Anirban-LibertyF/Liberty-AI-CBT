// Login / Register modal, password-strength meter and the "log in before you start a test" gate.
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const modal = $('#auth-modal');
  let waiting = []; // resolvers of requireAuth() promises
  let pendingForm = null; // a start.php form to submit after login

  // ------------------------------------------------ account menu (signed-in)
  $('#account-btn')?.addEventListener('click', (e) => {
    e.stopPropagation();
    $('#account-dropdown').classList.toggle('hidden');
  });
  document.addEventListener('click', () => $('#account-dropdown')?.classList.add('hidden'));
  $('#account-logout')?.addEventListener('click', async () => {
    try { await api('api/auth_logout.php', {}); } finally { location.href = 'index.php'; }
  });

  // ------------------------------------------------ gate
  window.requireAuth = () =>
    window.USER ? Promise.resolve(true) : new Promise((resolve) => { waiting.push(resolve); openAuth('login'); });

  // any form that starts an attempt needs an account
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (window.USER || !(f instanceof HTMLFormElement) || !/start\.php$/.test(f.getAttribute('action') || '')) return;
    if (f.elements.exam && f.elements.exam.value === 'demo') return; // the free demo needs no account
    e.preventDefault();
    pendingForm = f;
    openAuth('login');
  }, true);

  if (!modal) return;

  // ------------------------------------------------ modal
  const err = $('#auth-error');
  const forms = { login: $('#auth-login'), register: $('#auth-register') };
  const subs = { login: 'Log in to start your test', register: 'Create your free account to start practising' };
  let tab = 'login';

  function setTab(t) {
    tab = t;
    err.classList.add('hidden');
    Object.entries(forms).forEach(([k, f]) => f.classList.toggle('hidden', k !== t));
    $$('[data-auth-tab]', modal).forEach((b) => {
      if (!b.closest('[role=tablist]')) return;
      const on = b.dataset.authTab === t;
      b.className = 'py-2 rounded-lg cursor-pointer ' + (on ? 'bg-white text-red-700 shadow-sm' : 'text-slate-500 hover:text-slate-800');
    });
    $('#auth-sub').textContent = subs[t];
    setTimeout(() => $(t === 'login' ? '#li-id' : '#rg-name').focus(), 30);
  }
  function openAuth(t = 'login') {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTab(t);
    window.refreshIcons?.();
  }
  function closeAuth() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    waiting.forEach((r) => r(false));
    waiting = [];
    pendingForm = null;
  }
  window.openAuth = openAuth;
  $$('[data-auth-open]').forEach((b) => b.addEventListener('click', () => openAuth(b.dataset.authOpen)));
  $$('[data-auth-tab]', modal).forEach((b) => b.addEventListener('click', () => setTab(b.dataset.authTab)));
  $('#auth-close').addEventListener('click', closeAuth);
  modal.addEventListener('mousedown', (e) => { if (e.target === modal) closeAuth(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeAuth(); });

  $$('[data-toggle-pw]', modal).forEach((b) =>
    b.addEventListener('click', () => {
      const i = b.parentElement.querySelector('input');
      const show = i.type === 'password';
      i.type = show ? 'text' : 'password';
      b.innerHTML = window.ico(show ? 'eye-off' : 'eye', 'w-4 h-4');
      window.refreshIcons?.();
    })
  );

  // ------------------------------------------------ password strength
  const checks = (pw) => ({
    len: pw.length >= 8,
    upper: /[A-Z]/.test(pw),
    digit: /[0-9]/.test(pw),
    special: /[^A-Za-z0-9]/.test(pw),
  });
  const bars = $$('.pw-bar', modal);
  function strength(pw) {
    const c = checks(pw);
    const ok = Object.values(c).every(Boolean);
    if (!pw) return { level: 0, c, ok };
    if (!ok) return { level: 1, c, ok };
    return { level: pw.length >= 12 && /[a-z]/.test(pw) ? 3 : 2, c, ok };
  }
  $('#rg-pw').addEventListener('input', (e) => {
    const { level, c } = strength(e.target.value);
    const colors = ['bg-slate-200', 'bg-rose-500', 'bg-amber-400', 'bg-emerald-500'];
    bars.forEach((b, i) => {
      b.className = 'pw-bar h-1.5 rounded-full transition-colors ' + (i < level ? colors[level] : 'bg-slate-200');
    });
    const lab = $('#pw-label');
    lab.textContent = [' ', 'Weak', 'Medium', 'Strong'][level];
    lab.className = 'text-[11px] font-semibold w-14 text-right ' + ['text-slate-400', 'text-rose-600', 'text-amber-600', 'text-emerald-600'][level];
    $$('#pw-rules [data-rule]').forEach((li) => {
      const met = c[li.dataset.rule];
      li.className = met ? 'text-emerald-600' : 'text-slate-500';
      li.textContent = (met ? '✓ ' : '• ') + li.textContent.replace(/^[✓•] /, '');
    });
  });

  // digits only in the mobile field; live match hint for confirm password
  $('#rg-phone').addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10); });
  const matchHint = () => {
    const a = $('#rg-pw').value, b = $('#rg-pw2').value, m = $('#pw-match');
    m.textContent = !b ? '' : a === b ? '\u2713 Passwords match' : 'Passwords do not match';
    m.className = 'mt-1 text-[11px] h-4 ' + (!b ? '' : a === b ? 'text-emerald-600' : 'text-rose-600');
  };
  $('#rg-pw').addEventListener('input', matchHint);
  $('#rg-pw2').addEventListener('input', matchHint);

  // ------------------------------------------------ submit
  const fail = (m) => notify({ title: tab === 'login' ? 'Could not log in' : 'Please check your details', message: m, type: 'warning' });
  async function submit(form, url, body, btnText) {
    err.classList.add('hidden');
    const btn = $('button[type=submit]', form);
    btn.disabled = true;
    btn.textContent = 'Please wait…';
    try {
      const { user } = await api(url, body);
      window.USER = user;
      const resolvers = waiting; waiting = [];
      const f = pendingForm; pendingForm = null;
      modal.classList.add('hidden'); modal.classList.remove('flex');
      if (resolvers.length) {
        resolvers.forEach((r) => r(true));
        renderAccount(user);
      } else if (f) {
        f.submit();
      } else {
        location.reload();
      }
    } catch (e) {
      fail(e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = btnText;
    }
  }

  function renderAccount(user) {
    const slot = $('#account-slot');
    if (!slot) return;
    const first = String(user.name).split(' ')[0];
    slot.innerHTML = `<div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-sm font-semibold text-slate-700">
      <span class="w-7 h-7 rounded-full bg-red-600 text-white flex items-center justify-center text-xs font-bold">${esc(String(user.name).charAt(0).toUpperCase())}</span>
      <span class="max-w-[9rem] truncate">Hi, ${esc(first)}</span></div>`;
  }

  forms.login.addEventListener('submit', (e) => {
    e.preventDefault();
    const id = $('#li-id').value.trim(), pw = $('#li-pw').value;
    if (!id || !pw) return fail('Enter your email/mobile and password.');
    submit(forms.login, 'api/auth_login.php', { identifier: id, password: pw }, 'Log in');
  });

  forms.register.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = $('#rg-name').value.trim(), phone = $('#rg-phone').value.trim();
    const email = $('#rg-email').value.trim(), pw = $('#rg-pw').value;
    if (name.length < 2) return fail('Please enter your full name.');
    if (!/^[6-9]\d{9}$/.test(phone)) return fail('Enter a valid 10-digit Indian mobile number (starting with 6, 7, 8 or 9).');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return fail('Enter a valid email address.');
    if (!strength(pw).ok) return fail('Password needs 8+ characters, a capital letter, a digit and a special character.');
    if (pw !== $('#rg-pw2').value) return fail('The two passwords do not match.');
    submit(forms.register, 'api/auth_register.php', { name, phone, email, password: pw, country_code: $('#rg-cc').value }, 'Create account');
  });
})();
