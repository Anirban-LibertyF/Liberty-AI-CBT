// Result page: solution filters + AI coach card.
(() => {
  const R = window.RESULT;
  const $ = (s) => document.querySelector(s);

  // ---------------------------------------------------------------- solution filters
  let statusFilter = 'ALL';
  let sectionFilter = 'ALL';

  function applyFilters() {
    let shown = 0;
    document.querySelectorAll('.solution').forEach((el) => {
      const s = el.dataset.status;
      let ok = sectionFilter === 'ALL' || el.dataset.section === sectionFilter;
      if (ok) {
        if (statusFilter === 'DIAGRAM') ok = el.dataset.diagram === '1';
        else if (statusFilter === 'CORRECT') ok = s === 'correct';
        else if (statusFilter === 'INCORRECT') ok = s === 'incorrect';
        else if (statusFilter === 'UNATTEMPTED') ok = s === 'unattempted';
      }
      el.classList.toggle('hidden', !ok);
      if (ok) shown++;
    });
    $('#sol-count').textContent = shown;
    document.querySelectorAll('#sol-filters [data-filter]').forEach((b) => {
      const on = b.dataset.filter === statusFilter;
      b.classList.toggle('bg-indigo-600', on);
      b.classList.toggle('text-white', on);
      b.classList.toggle('bg-red-100', !on);
      b.classList.toggle('text-slate-600', !on);
    });
  }
  document.querySelectorAll('#sol-filters [data-filter]').forEach((b) =>
    b.addEventListener('click', () => {
      statusFilter = b.dataset.filter;
      applyFilters();
    })
  );
  $('#sol-section').addEventListener('change', (e) => {
    sectionFilter = e.target.value;
    applyFilters();
  });
  applyFilters();

  // ---------------------------------------------------------------- PDF download
  $('#download-pdf')?.addEventListener('click', () => window.print());
  if (R.autoPrint) setTimeout(() => window.print(), 300);

  // ---------------------------------------------------------------- AI coach
  let coach = R.coach; // {source, report} | null
  let loading = false;
  let failure = '';

  function renderCoach() {
    const body = $('#coach-body');
    $('#coach-refresh').classList.toggle('hidden', !coach);
    $('#coach-refresh').classList.toggle('flex', !!coach);

    if (!coach) {
      body.innerHTML = `
      <div class="space-y-4 py-4">
        <h4 class="text-lg font-bold text-slate-900">Get Deep AI Diagnosis of Your Speed, Accuracy &amp; Weak Sub-Topics</h4>
        <p class="text-xs text-slate-600 leading-relaxed">Our AI Coach analyzes your per-question time logs and negative marking patterns to generate a targeted revision plan for your next mock test.</p>
        
        ${failure ? `<p class="text-xs text-rose-700">${esc(failure)}</p>` : ''}
        <button id="coach-go" ${loading ? 'disabled' : ''} class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/25 cursor-pointer disabled:opacity-50">
          ${loading ? `${ico('refresh-cw', 'w-4 h-4 animate-spin')}<span>Analyzing Question Logs &amp; Time Spent...</span>` : `${ico('sparkles', 'w-4 h-4')}<span>Generate AI Coach Analysis</span>`}
        </button>
      </div>`;
      $('#coach-go').addEventListener('click', generate);
    } else {
      const r = coach.report;
      const weak = (r.weakTopicsToRevise || [])
        .map((t) => `<span class="px-2.5 py-1 rounded-md bg-rose-500/15 border border-rose-500/30 text-rose-800 text-xs">${esc(t)}</span>`)
        .join('');
      body.innerHTML = `
      <div class="space-y-3 text-xs sm:text-sm">
        ${coach.source === 'offline' ? '<div class="text-[11px] text-amber-700">Quick analysis based on your scorecard.</div>' : ''}
        <p class="text-slate-700 font-medium bg-red-50 p-3 rounded-xl border border-red-100">${esc(r.overallVerdict)}</p>
        <div class="p-3 rounded-xl bg-red-50 border border-indigo-500/20 text-red-800 text-xs"><span class="font-bold text-red-700">Time Management: </span>${esc(r.timeManagementAnalysis)}</div>
        ${weak ? `<div><div class="text-xs font-bold text-rose-600 uppercase mb-1">Priority Topics to Revise:</div><div class="flex flex-wrap gap-1.5">${weak}</div></div>` : ''}
        <div><div class="text-xs font-bold text-emerald-600 uppercase mb-1">Actionable Next Steps:</div>
          <ul class="space-y-1 text-xs text-slate-600 list-disc list-inside">${(r.actionableStudyPlan || []).map((s) => `<li>${esc(s)}</li>`).join('')}</ul></div>
      </div>`;
    }
    refreshIcons();
  }

  async function generate() {
    if (loading) return;
    loading = true;
    failure = '';
    renderCoach();
    try {
      coach = await api('api/coach.php', { attempt_id: R.attemptId });
    } catch (err) {
      failure = err.message;
    }
    loading = false;
    renderCoach();
  }

  $('#coach-refresh').addEventListener('click', async () => {
    coach = null;
    await generate();
  });
  renderCoach();
})();
