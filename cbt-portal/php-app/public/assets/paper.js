// Printable paper: in Bengali mode, fetch the Bengali question/options/explanation in batches, then offer the PDF.
(() => {
  const P = window.PAPER;
  const btn = document.getElementById('print-btn');
  const print = () => window.print();
  btn.addEventListener('click', print);

  if (P.lang !== 'bn') {
    if (P.autoPrint) setTimeout(print, 400);
    return;
  }

  const sections = Array.from(document.querySelectorAll('section[data-qid]'));
  const ids = sections.map((s) => Number(s.dataset.qid));
  const BATCH = 6;
  const PARALLEL = 3;
  const batches = [];
  for (let i = 0; i < ids.length; i += BATCH) batches.push(ids.slice(i, i + BATCH));

  const bar = document.getElementById('bn-bar');
  const pct = document.getElementById('bn-pct');
  document.getElementById('bn-progress').classList.remove('hidden');
  btn.disabled = true;

  let done = 0;
  let failed = 0;
  let firstError = '';

  function apply(map) {
    sections.forEach((sec) => {
      const t = map[sec.dataset.qid];
      if (!t) return;
      sec.querySelector('.q-text').innerHTML = t.html;
      sec.querySelectorAll('.opt-text').forEach((el) => {
        if (t.options && t.options[el.dataset.label] != null) el.innerHTML = t.options[el.dataset.label];
      });
      if (t.explanation) sec.querySelector('.expl').innerHTML = t.explanation;
    });
  }

  async function run(batch) {
    try {
      const res = await fetch('api/translate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF },
        body: JSON.stringify({ attempt_id: P.attemptId, question_ids: batch, explanations: true, csrf: window.CSRF }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Translation failed.');
      apply(data.translations || {});
    } catch (e) {
      failed += batch.length;
      firstError = firstError || e.message;
    }
    done += batch.length;
    const p = Math.round((done / ids.length) * 100);
    bar.style.width = p + '%';
    pct.textContent = p + '%';
  }

  (async () => {
    const queue = batches.slice();
    await Promise.all(
      Array.from({ length: PARALLEL }, async () => {
        while (queue.length) await run(queue.shift());
      })
    );
    document.getElementById('bn-progress').classList.add('hidden');
    btn.disabled = false;
    if (failed) {
      await window.notify({
        title: 'Some questions are still in English',
        message: `${failed} question(s) could not be translated right now (${firstError}). Reload this page to retry; already translated questions are saved.`,
        type: 'warning',
      });
    } else if (P.autoPrint) {
      setTimeout(print, 300);
    }
  })();
})();
