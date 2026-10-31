// Exam hall. State lives in the browser for snappy navigation, but every action is
// autosaved to the server, which also owns the clock and the answer key.
(() => {
  const E = window.EXAM;
  const app = document.getElementById('app');

  const qs = E.questions;
  let idx = 0;
  let draft = null; // option picked on the current question but not yet saved
  let paletteFilter = 'ALL';
  let mobilePaletteOpen = false;
  let showSubmit = false;
  let zoom = null;
  let submitting = false;

  const deadline = Date.now() + E.remaining * 1000;
  let lastTick = Date.now();
  let unsent = 0; // seconds on the current question not yet reported to the server
  let timeLeft = E.remaining;

  const state = (q) => q.state;
  const isAnswered = (q) => q.state === 'ANSWERED' || q.state === 'ANSWERED_AND_MARKED';

  // The first question is visited on entry. (Server already marked it NOT_ANSWERED at start.)
  draft = qs[0]?.selected ?? null;

  // ---------------------------------------------------------------- server sync

  const queue = []; // serialise requests so saves land in order
  let flushing = false;
  async function pump() {
    if (flushing) return;
    flushing = true;
    while (queue.length) {
      const job = queue.shift();
      try {
        await api('api/attempt_save.php', job);
      } catch (err) {
        if (/submitted|Time is up/i.test(err.message)) {
          window.location.href = `result.php?attempt=${E.attemptId}`;
          return;
        }
        console.error('autosave failed', err);
      }
    }
    flushing = false;
  }
  function send(payload) {
    queue.push({ attempt_id: E.attemptId, ...payload });
    pump();
  }
  /** Report time spent on the current question, optionally with an action. */
  function report(extra = {}) {
    const q = qs[idx];
    const delta = unsent;
    unsent = 0;
    send({ question_id: q.id, time_delta: delta, ...extra });
  }

  // ---------------------------------------------------------------- navigation / actions

  function goTo(target, { fromAction = false } = {}) {
    const next = Math.max(0, Math.min(qs.length - 1, target));
    // Actions (save/review) already reported the time and the visit; plain navigation reports both here.
    if (!fromAction) report({ action: 'tick', visit_id: qs[next].id });
    if (qs[next].state === 'NOT_VISITED') qs[next].state = 'NOT_ANSWERED';
    idx = next;
    draft = qs[idx].selected;
    unsent = 0;
    render();
  }

  function saveAndNext() {
    const q = qs[idx];
    q.selected = draft;
    q.state = draft ? 'ANSWERED' : 'NOT_ANSWERED';
    const nextIdx = idx < qs.length - 1 ? idx + 1 : idx;
    report({ action: 'save', selected: draft, visit_id: qs[nextIdx].id });
    goTo(nextIdx, { fromAction: true });
  }

  function markReviewAndNext() {
    const q = qs[idx];
    q.selected = draft;
    q.state = draft ? 'ANSWERED_AND_MARKED' : 'MARKED_FOR_REVIEW';
    const nextIdx = idx < qs.length - 1 ? idx + 1 : idx;
    report({ action: 'review', selected: draft, visit_id: qs[nextIdx].id });
    goTo(nextIdx, { fromAction: true });
  }

  function clearResponse() {
    const q = qs[idx];
    draft = null;
    q.selected = null;
    q.state = 'NOT_ANSWERED';
    report({ action: 'clear' });
    render();
  }

  async function submitExam() {
    if (submitting) return;
    submitting = true;
    showSubmit = false;
    render();
    const q = qs[idx];
    try {
      await new Promise((r) => {
        const t = setInterval(() => !flushing && (clearInterval(t), r()), 50); // let queued saves land first
      });
      const res = await api('api/attempt_submit.php', {
        attempt_id: E.attemptId,
        question_id: q.id,
        action: 'tick',
        time_delta: unsent,
      });
      unsent = 0;
      window.location.href = res.redirect;
    } catch (err) {
      submitting = false;
      alert(err.message);
      render();
    }
  }

  // ---------------------------------------------------------------- clock

  setInterval(() => {
    const now = Date.now();
    const elapsed = Math.round((now - lastTick) / 1000);
    if (elapsed >= 1) {
      lastTick = now;
      unsent += elapsed;
      qs[idx].time += elapsed;
    }
    timeLeft = Math.max(0, Math.ceil((deadline - now) / 1000));
    updateClock();
    if (timeLeft === 0) submitExam();
  }, 1000);

  // Heartbeat: persist time even if the tab is closed abruptly.
  setInterval(() => unsent >= 15 && report({ action: 'tick' }), 15000);
  window.addEventListener('pagehide', () => {
    if (!unsent || submitting) return;
    const body = JSON.stringify({ attempt_id: E.attemptId, question_id: qs[idx].id, action: 'tick', time_delta: unsent, _csrf: window.CSRF });
    navigator.sendBeacon('api/attempt_save.php', new Blob([body], { type: 'text/plain' }));
  });

  function updateClock() {
    const el = document.getElementById('clock');
    if (!el) return;
    const low = timeLeft < 300;
    el.className = `flex items-center gap-2 px-3.5 py-1.5 rounded-lg border font-mono text-sm sm:text-base font-bold ${
      low ? 'bg-white border-white text-red-700 animate-pulse' : 'bg-red-800 border-red-500 text-white'
    }`;
    document.getElementById('clock-text').textContent = `Time Left: ${formatClock(timeLeft)}`;
    const qt = document.getElementById('q-time');
    if (qt) qt.textContent = `Time on this Q: ${qs[idx].time}s`;
    const rem = document.getElementById('submit-remaining');
    if (rem) rem.textContent = formatClock(timeLeft);
  }

  // ---------------------------------------------------------------- rendering

  const counts = () => {
    const c = { ANSWERED: 0, NOT_ANSWERED: 0, NOT_VISITED: 0, MARKED_FOR_REVIEW: 0, ANSWERED_AND_MARKED: 0 };
    qs.forEach((q) => c[q.state]++);
    return c;
  };

  function paletteStyle(st, current) {
    const ring = current ? 'ring-2 ring-offset-2 ring-blue-600 scale-105' : '';
    switch (st) {
      case 'ANSWERED':
        return `bg-emerald-600 text-white border-emerald-700 rounded-t-xl rounded-b-sm font-bold shadow-sm ${ring}`;
      case 'NOT_ANSWERED':
        return `bg-rose-600 text-white border-rose-700 rounded-b-xl rounded-t-sm font-bold shadow-sm ${ring}`;
      case 'MARKED_FOR_REVIEW':
        return `bg-purple-600 text-white border-purple-700 rounded-full font-bold shadow-sm ${ring}`;
      case 'ANSWERED_AND_MARKED':
        return `bg-purple-600 text-white border-purple-700 rounded-full font-bold shadow-sm relative ${ring}`;
      default:
        return `bg-slate-100 text-slate-700 border border-slate-300 rounded-md font-medium hover:bg-slate-200 ${ring}`;
    }
  }

  const sections = [...new Set(qs.map((q) => q.section))];

  function diagramCard(q, withZoom) {
    if (!q.hasDiagram || !q.svg) return '';
    const title = `Figure for Q.${q.number} (${q.topic})`;
    return `
    <div class="my-4 rounded-2xl bg-white border-2 border-indigo-100 shadow-sm overflow-hidden">
      <div class="px-4 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs text-slate-600">
        <span class="font-bold text-indigo-700 flex items-center gap-1.5">${ico('image', 'w-3.5 h-3.5 text-indigo-600')}${esc(title)}</span>
        ${withZoom ? `<button type="button" data-act="zoom" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-colors cursor-pointer">${ico('maximize-2', 'w-3 h-3')}<span>Enlarge Figure</span></button>` : ''}
      </div>
      <div class="p-4 flex items-center justify-center bg-white max-h-[300px] overflow-auto [&>svg]:max-w-full [&>svg]:h-auto [&>svg]:max-h-[260px]">${q.svg}</div>
    </div>`;
  }

  let renderedIdx = -1;
  function render() {
    // Keep scroll positions when only an option / modal changed (not the question).
    const qScroll = renderedIdx === idx ? document.getElementById('q-scroll')?.scrollTop ?? 0 : 0;
    const palScroll = document.getElementById('pal-scroll')?.scrollTop ?? 0;
    const q = qs[idx];
    const c = counts();
    const filtered = paletteFilter === 'ALL' ? qs : qs.filter((x) => x.section === paletteFilter);
    const diffCls =
      q.difficulty === 'Hard'
        ? 'bg-rose-50 text-rose-700 border border-rose-200'
        : q.difficulty === 'Easy'
        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
        : 'bg-amber-50 text-amber-700 border border-amber-200';

    app.innerHTML = `
    <header class="bg-red-700 text-white px-4 py-2.5 flex items-center justify-between shadow-md shrink-0 z-20">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-white text-red-700 flex items-center justify-center font-bold text-sm shrink-0">LC</div>
        <div class="truncate">
          <h1 class="text-sm sm:text-base font-bold truncate">${esc(E.title)}</h1>
          <p class="text-[11px] text-red-100 hidden sm:block">Total questions: ${qs.length}</p>
        </div>
      </div>
      <div class="flex items-center gap-3 sm:gap-5">
        <div id="clock" class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg border font-mono text-sm sm:text-base font-bold bg-red-800 border-red-500 text-white">
          ${ico('clock', 'w-4 h-4')}<span id="clock-text">Time Left: ${formatClock(timeLeft)}</span>
        </div>
        <button data-act="open-submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow transition-all cursor-pointer">Submit Exam</button>
        <button data-act="toggle-palette" class="lg:hidden p-2 rounded-lg bg-red-800 text-white" aria-label="Toggle Question Palette">${ico(mobilePaletteOpen ? 'x' : 'menu', 'w-5 h-5')}</button>
      </div>
    </header>

    <div class="bg-white border-b border-slate-200 px-4 py-2 flex items-center justify-between gap-2 overflow-x-auto shrink-0">
      <div class="flex items-center gap-1.5">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2 hidden sm:inline">Sections:</span>
        ${sections
          .map((name) => {
            const inSec = qs.filter((x) => x.section === name);
            const done = inSec.filter(isAnswered).length;
            const cur = q.section === name;
            return `<button data-act="section" data-section="${esc(name)}" class="px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer ${cur ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}">
              <span>${esc(name)}</span>
              <span class="px-1.5 py-0.5 rounded text-[11px] ${cur ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-200 text-slate-600'}">${done}/${inSec.length}</span></button>`;
          })
          .join('')}
      </div>
      <div class="hidden md:flex items-center gap-2 text-xs font-semibold shrink-0">
        <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Correct: +${E.positive}</span>
        <span class="px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200">Negative: -${E.negative}</span>
      </div>
    </div>

    <div class="flex-1 flex overflow-hidden relative">
      <div class="flex-1 flex flex-col bg-white overflow-hidden">
        <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2.5">
            <span class="text-base font-extrabold text-slate-900">Question No. ${q.number}</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">${esc(q.topic)}</span>
            ${q.hasDiagram ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1">${ico('image', 'w-3 h-3')} Diagram Question</span>` : ''}
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold ${diffCls}">${esc(q.difficulty)}</span>
          </div>
          <div class="flex items-center gap-3 text-xs text-slate-500">
            <span class="flex items-center gap-1 font-mono">${ico('timer', 'w-3.5 h-3.5 text-slate-400')}<span id="q-time">Time on this Q: ${q.time}s</span></span>
          </div>
        </div>

        <div id="q-scroll" class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-5 max-w-4xl">
          <div class="text-base sm:text-lg font-medium text-slate-900 leading-relaxed whitespace-pre-line">${q.html}</div>
          ${diagramCard(q, true)}
          <div class="space-y-3 pt-2">
            ${q.options
              .map((o) => {
                const on = draft === o.id;
                return `<div data-act="pick" data-opt="${o.id}" class="p-4 rounded-xl border-2 transition-all flex items-start gap-3.5 cursor-pointer ${on ? 'border-indigo-600 bg-indigo-50/70 shadow-sm' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'}">
                  <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5 border ${on ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 border-slate-300'}">${o.id}</div>
                  <div class="text-sm sm:text-base text-slate-800 font-medium leading-snug pt-0.5">${o.html}</div></div>`;
              })
              .join('')}
          </div>
        </div>

        <div class="px-6 py-3.5 bg-slate-100 border-t border-slate-300 flex flex-wrap items-center justify-between gap-3 shrink-0">
          <div class="flex flex-wrap items-center gap-2.5">
            <button type="button" data-act="review" class="px-4 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs sm:text-sm shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer">${ico('flag', 'w-4 h-4')}<span>Mark for Review &amp; Next</span></button>
            <button type="button" data-act="clear" class="px-4 py-2.5 rounded-lg bg-white hover:bg-slate-200 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm flex items-center gap-1.5 transition-colors cursor-pointer">${ico('rotate-ccw', 'w-4 h-4')}<span>Clear Response</span></button>
          </div>
          <div class="flex items-center gap-2.5">
            <button type="button" data-act="prev" ${idx === 0 ? 'disabled' : ''} class="px-4 py-2.5 rounded-lg bg-white hover:bg-slate-200 disabled:opacity-40 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm flex items-center gap-1 transition-colors cursor-pointer">${ico('chevron-left', 'w-4 h-4')}<span>Previous</span></button>
            <button type="button" data-act="save" class="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"><span>Save &amp; Next</span>${ico('chevron-right', 'w-4 h-4')}</button>
          </div>
        </div>
      </div>

      <aside class="${mobilePaletteOpen ? 'fixed inset-y-0 right-0 z-40 flex' : 'hidden lg:flex'} w-80 bg-slate-50 border-l border-slate-300 flex-col shrink-0">
        <div class="p-3.5 bg-white border-b border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">ST</div>
            <div><div class="text-xs font-bold text-slate-800">Candidate Attempt</div><div class="text-[11px] text-slate-500">Roll No: ${esc(E.roll)}</div></div>
          </div>
          ${mobilePaletteOpen ? `<button data-act="toggle-palette" class="lg:hidden p-1.5 rounded-lg bg-slate-100 text-slate-600">${ico('x', 'w-4 h-4')}</button>` : ''}
        </div>
        <div class="p-3.5 bg-slate-100 border-b border-slate-200 grid grid-cols-2 gap-2 text-xs">
          <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-t-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-[11px]">${c.ANSWERED}</span><span class="text-slate-700 font-medium">Answered</span></div>
          <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-b-lg bg-rose-600 text-white font-bold flex items-center justify-center text-[11px]">${c.NOT_ANSWERED}</span><span class="text-slate-700 font-medium">Not Answered</span></div>
          <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-md bg-white border border-slate-300 text-slate-700 font-bold flex items-center justify-center text-[11px]">${c.NOT_VISITED}</span><span class="text-slate-700 font-medium">Not Visited</span></div>
          <div class="flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-[11px]">${c.MARKED_FOR_REVIEW}</span><span class="text-slate-700 font-medium">Marked Review</span></div>
          <div class="col-span-2 flex items-center gap-2 pt-1">
            <span class="w-6 h-6 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-[11px] relative shrink-0">${c.ANSWERED_AND_MARKED}<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 border border-white absolute -bottom-0.5 -right-0.5"></span></span>
            <span class="text-slate-700 font-medium text-[11px]">Answered &amp; Marked for Review (Will be evaluated)</span>
          </div>
        </div>
        <div class="px-3.5 py-2.5 bg-red-700 text-white flex items-center justify-between text-xs">
          <span class="font-semibold">Question Palette</span>
          <select data-act="palette-filter" class="bg-indigo-900 text-white text-xs rounded px-2 py-1 border border-indigo-700 focus:outline-none">
            <option value="ALL">All Sections (${qs.length})</option>
            ${sections.map((s) => `<option value="${esc(s)}" ${paletteFilter === s ? 'selected' : ''}>${esc(s)}</option>`).join('')}
          </select>
        </div>
        <div id="pal-scroll" class="flex-1 overflow-y-auto p-4">
          <div class="grid grid-cols-5 gap-2.5">
            ${filtered
              .map((x) => {
                const i = qs.indexOf(x);
                return `<button data-act="jump" data-i="${i}" title="Q.${x.number}${x.hasDiagram ? ' (Contains Diagram)' : ''}" class="h-10 w-full flex items-center justify-center text-xs transition-transform relative cursor-pointer ${paletteStyle(x.state, i === idx)}">
                  <span>${x.number}</span>
                  ${x.hasDiagram ? '<span class="w-2 h-2 rounded-full bg-amber-400 border border-slate-900 absolute -top-0.5 -left-0.5"></span>' : ''}
                  ${x.state === 'ANSWERED_AND_MARKED' ? '<span class="w-2.5 h-2.5 rounded-full bg-emerald-400 border border-white absolute -bottom-0.5 -right-0.5"></span>' : ''}</button>`;
              })
              .join('')}
          </div>
        </div>
        <div class="p-3.5 bg-white border-t border-slate-200">
          <button data-act="open-submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow transition-colors cursor-pointer">Submit Test &amp; View Scorecard</button>
        </div>
      </aside>
    </div>

    ${showSubmit ? submitModal(c) : ''}
    ${zoom ? zoomModal() : ''}`;

    refreshIcons();
    updateClock();
    document.getElementById('q-scroll').scrollTop = qScroll;
    document.getElementById('pal-scroll').scrollTop = palScroll;
    renderedIdx = idx;
  }

  function submitModal(c) {
    const tile = (n, label, box, num, txt) =>
      `<div class="p-3 rounded-xl ${box}"><div class="text-xl font-extrabold ${num}">${n}</div><div class="text-xs ${txt} font-medium">${label}</div></div>`;
    return `
    <div class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 text-slate-900">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <h3 class="text-lg font-bold">Exam Submission Summary</h3>
          <button data-act="close-submit" class="p-1 text-slate-400 hover:text-slate-600">${ico('x', 'w-5 h-5')}</button>
        </div>
        <p class="text-sm text-slate-600">Please verify your attempt status before final submission. Note that questions marked <span class="font-semibold text-purple-700">Answered &amp; Marked for Review</span> will be counted toward your final score.</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-center">
          ${tile(c.ANSWERED, 'Answered', 'bg-emerald-50 border border-emerald-200', 'text-emerald-700', 'text-emerald-800')}
          ${tile(c.ANSWERED_AND_MARKED, 'Ans &amp; Marked', 'bg-purple-50 border border-purple-200', 'text-purple-700', 'text-purple-800')}
          ${tile(c.NOT_ANSWERED, 'Not Answered', 'bg-rose-50 border border-rose-200', 'text-rose-700', 'text-rose-800')}
          ${tile(c.NOT_VISITED, 'Not Visited', 'bg-slate-100 border border-slate-200', 'text-slate-700', 'text-slate-600')}
          ${tile(c.MARKED_FOR_REVIEW, 'Review (Unans)', 'bg-purple-50/60 border border-purple-200', 'text-purple-600', 'text-purple-700')}
          ${tile(`<span id="submit-remaining">${formatClock(timeLeft)}</span>`, 'Time Remaining', 'bg-indigo-50 border border-indigo-200', 'text-indigo-700', 'text-indigo-800')}
        </div>
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
          <button data-act="close-submit" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm cursor-pointer">Resume Exam</button>
          <button data-act="confirm-submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 cursor-pointer">Confirm &amp; Submit Exam</button>
        </div>
      </div>
    </div>`;
  }

  function zoomModal() {
    return `
    <div data-act="close-zoom" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
      <div data-stop class="bg-white rounded-2xl max-w-3xl w-full p-5 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <h4 class="font-bold text-slate-900 flex items-center gap-2">${ico('image', 'w-4 h-4 text-indigo-600')}${esc(zoom.title)}</h4>
          <button data-act="close-zoom" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer">${ico('x', 'w-4 h-4')}</button>
        </div>
        <div class="p-6 bg-slate-50 rounded-xl flex items-center justify-center [&>svg]:w-full [&>svg]:max-h-[65vh] [&>svg]:h-auto">${zoom.svg}</div>
      </div>
    </div>`;
  }

  // ---------------------------------------------------------------- events (delegated)

  app.addEventListener('click', (e) => {
    const el = e.target.closest('[data-act]');
    if (!el) return;
    if (e.target.closest('[data-stop]') && el.dataset.act === 'close-zoom' && !el.matches('button')) return;
    switch (el.dataset.act) {
      case 'pick':
        draft = el.dataset.opt;
        render();
        break;
      case 'save':
        saveAndNext();
        break;
      case 'review':
        markReviewAndNext();
        break;
      case 'clear':
        clearResponse();
        break;
      case 'prev':
        goTo(idx - 1);
        break;
      case 'jump':
        mobilePaletteOpen = false;
        goTo(Number(el.dataset.i));
        break;
      case 'section': {
        const first = qs.findIndex((x) => x.section === el.dataset.section);
        if (first !== -1) goTo(first);
        break;
      }
      case 'toggle-palette':
        mobilePaletteOpen = !mobilePaletteOpen;
        render();
        break;
      case 'open-submit':
        showSubmit = true;
        render();
        break;
      case 'close-submit':
        showSubmit = false;
        render();
        break;
      case 'confirm-submit':
        submitExam();
        break;
      case 'zoom': {
        const q = qs[idx];
        zoom = { svg: q.svg, title: `Figure for Q.${q.number} (${q.topic})` };
        render();
        break;
      }
      case 'close-zoom':
        zoom = null;
        render();
        break;
    }
  });
  app.addEventListener('change', (e) => {
    if (e.target.dataset.act === 'palette-filter') {
      paletteFilter = e.target.value;
      render();
    }
  });

  render();
})();
