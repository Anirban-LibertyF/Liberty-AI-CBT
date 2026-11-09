// Instructions page: language switch for the text, and the "ready to begin" button that starts the exam clock.
(() => {
  const $ = (s) => document.querySelector(s);
  let lang = 'en';
  try { lang = localStorage.getItem('cbt_lang') === 'bn' ? 'bn' : 'en'; } catch { /* ignore */ }

  function setLang(l) {
    lang = l;
    document.querySelectorAll('[data-lang]').forEach((el) => el.classList.toggle('hidden', el.dataset.lang !== l));
    document.querySelectorAll('[data-set-lang]').forEach((b) => {
      const on = b.dataset.setLang === l;
      b.className = 'px-2.5 py-1 rounded-md cursor-pointer ' + (on ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100');
    });
    $('#ins-begin span').textContent = l === 'bn' ? 'ঠিক আছে, আমি পরীক্ষা শুরু করতে প্রস্তুত' : 'Okay, I am ready to begin';
    try { localStorage.setItem('cbt_lang', l); } catch { /* ignore */ }
  }
  document.querySelectorAll('[data-set-lang]').forEach((b) => b.addEventListener('click', () => setLang(b.dataset.setLang)));
  setLang(lang);

  const agree = $('#ins-agree');
  const btn = $('#ins-begin');
  agree.addEventListener('change', () => (btn.disabled = !agree.checked));
  btn.addEventListener('click', async () => {
    if (!agree.checked) {
      notify({
        title: lang === 'bn' ? 'নির্দেশাবলি নিশ্চিত করুন' : 'Please confirm',
        message: lang === 'bn' ? 'শুরু করার আগে নির্দেশাবলি পড়েছেন বলে টিক দিন।' : 'Tick the box to confirm you have read the instructions.',
        type: 'warning',
      });
      return;
    }
    btn.disabled = true;
    try {
      await api('api/attempt_begin.php', { attempt_id: window.INS.attemptId });
      window.location.reload(); // the exam hall loads with the clock now running
    } catch (e) {
      btn.disabled = false;
      notify({ title: 'Could not start the exam', message: e.message, type: 'error' });
    }
  });
})();
