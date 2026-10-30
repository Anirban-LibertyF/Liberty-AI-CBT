// Configuration screen: blueprint editor + parallel AI batch runner.
(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const presets = window.CFG.presets;

  // Subject -> default topics, built from every preset so "select or type" has real suggestions
  // for whichever exam the user is building (a single subject + a specific topic is fine too).
  const SUBJECT_TOPICS = {};
  presets.forEach((p) => p.sections.forEach((s) => { SUBJECT_TOPICS[s.name] ??= s.topics; }));
  const subjectNames = Object.keys(SUBJECT_TOPICS);
  const topicTokens = (name) => (SUBJECT_TOPICS[name] || '').split(',').map((t) => t.trim()).filter(Boolean);

  $('#subject-catalog')?.remove();
  const subjectDatalist = document.createElement('datalist');
  subjectDatalist.id = 'subject-catalog';
  subjectDatalist.innerHTML = subjectNames.map((n) => `<option value="${esc(n)}">`).join('');
  document.body.appendChild(subjectDatalist);

  const state = {
    presetId: null,
    title: '',
    total: 40,
    duration: 60,
    positive: 2,
    negative: 0.5,
    difficulty: $('#f-difficulty').options[0].value,
    diagrams: true,
    sections: [],
  };

  // ---------------------------------------------------------------- form <-> state

  function applyPreset(p) {
    state.presetId = p.id;
    state.title = `${p.name} Mock Test`;
    state.duration = p.durationMinutes;
    state.positive = p.positiveMarks;
    state.negative = p.negativeMarks;
    state.total = p.defaultCount;
    state.sections = p.sections.map((s) => ({ ...s }));
    syncForm();
    renderSections();
  }

  function syncForm() {
    $('#f-title').value = state.title;
    $('#f-duration').value = state.duration;
    $('#f-positive').value = state.positive;
    $('#f-negative').value = state.negative;
    $('#f-difficulty').value = state.difficulty;
    $('#f-diagrams').checked = state.diagrams;

    document.querySelectorAll('.preset-card').forEach((el) => {
      const on = el.dataset.preset === state.presetId;
      el.classList.toggle('bg-indigo-600/15', on);
      el.classList.toggle('border-indigo-500', on);
      el.classList.toggle('shadow-lg', on);
      el.classList.toggle('bg-white', !on);
      el.classList.toggle('border-red-100', !on);
      $('.preset-check', el).classList.toggle('hidden', !on);
    });
    document.querySelectorAll('.count-btn').forEach((el) => {
      const on = Number(el.dataset.count) === state.total;
      el.classList.toggle('bg-indigo-600/20', on);
      el.classList.toggle('border-indigo-500', on);
      el.classList.toggle('text-slate-900', on);
      el.classList.toggle('font-bold', on);
      el.classList.toggle('bg-red-50', !on);
      el.classList.toggle('border-red-100', !on);
      el.classList.toggle('text-slate-500', !on);
    });
    document.querySelectorAll('[data-total-label]').forEach((el) => (el.textContent = state.total));
    refreshIcons();
  }

  function renderSections() {
    $('#section-count').textContent = state.sections.length;
    $('#section-list').innerHTML = state.sections
      .map(
        (s, i) => `
      <div class="p-3.5 rounded-xl bg-red-50 border border-red-100 space-y-2.5" data-sid="${esc(s.id)}">
        <div class="flex items-center gap-2">
          <span class="w-6 h-6 rounded-md bg-indigo-500/20 text-red-700 text-xs font-bold flex items-center justify-center shrink-0">${i + 1}</span>
          <input type="text" data-field="name" maxlength="120" value="${esc(s.name)}" placeholder="Subject / Section Name"
            list="subject-catalog"
            class="flex-1 bg-transparent text-sm font-semibold text-slate-900 focus:outline-none border-b border-transparent focus:border-indigo-500 pb-0.5">
          ${state.sections.length > 1 ? `<button type="button" data-remove class="p-1.5 text-slate-500 hover:text-rose-600 transition-colors cursor-pointer" title="Remove subject">${ico('trash-2', 'w-4 h-4')}</button>` : ''}
        </div>
        <div>
          <label class="block text-[11px] text-slate-500 mb-1">Topics to cover (pick one specific topic, or type your own list):</label>
          <input type="text" data-field="topics" maxlength="500" value="${esc(s.topics)}"
            list="topics-${esc(s.id)}"
            class="w-full px-3 py-1.5 rounded-lg bg-white border border-red-100 text-xs text-slate-700 focus:outline-none focus:border-indigo-500">
          <datalist id="topics-${esc(s.id)}">${topicTokens(s.name).map((t) => `<option value="${esc(t)}">`).join('')}</datalist>
        </div>
      </div>`
      )
      .join('');
    refreshIcons();
  }

  document.querySelectorAll('.preset-card').forEach((el) =>
    el.addEventListener('click', () => applyPreset(presets.find((p) => p.id === el.dataset.preset)))
  );
  document.querySelectorAll('.count-btn').forEach((el) =>
    el.addEventListener('click', () => {
      state.total = Number(el.dataset.count);
      syncForm();
    })
  );
  $('#f-title').addEventListener('input', (e) => (state.title = e.target.value));
  $('#f-duration').addEventListener('input', (e) => (state.duration = Number(e.target.value) || 60));
  $('#f-positive').addEventListener('input', (e) => (state.positive = Number(e.target.value) || 2));
  $('#f-negative').addEventListener('input', (e) => (state.negative = Number(e.target.value) || 0));
  $('#f-difficulty').addEventListener('change', (e) => (state.difficulty = e.target.value));
  $('#f-diagrams').addEventListener('change', (e) => (state.diagrams = e.target.checked));

  $('#add-section').addEventListener('click', () => {
    state.sections.push({
      id: `sec_${Date.now()}`,
      name: 'New Subject',
      topics: 'Core concepts, problem solving, applications',
      weight: 25,
    });
    renderSections();
  });
  $('#section-list').addEventListener('input', (e) => {
    const card = e.target.closest('[data-sid]');
    if (!card || !e.target.dataset.field) return;
    const sec = state.sections.find((s) => s.id === card.dataset.sid);
    sec[e.target.dataset.field] = e.target.value;
    // Picking a known subject from the list refreshes its topic suggestions below.
    if (e.target.dataset.field === 'name' && SUBJECT_TOPICS[e.target.value] && !sec.topics) {
      sec.topics = SUBJECT_TOPICS[e.target.value];
      renderSections();
    }
  });
  $('#section-list').addEventListener('click', (e) => {
    if (!e.target.closest('[data-remove]') || state.sections.length <= 1) return;
    const sid = e.target.closest('[data-sid]').dataset.sid;
    state.sections = state.sections.filter((s) => s.id !== sid);
    renderSections();
  });

  // ---------------------------------------------------------------- parallel generation

  let examId = null;
  let batches = [];
  let running = false;

  const modal = $('#gen-modal');
  const showModal = (on) => {
    modal.classList.toggle('hidden', !on);
    modal.classList.toggle('flex', on);
  };

  function renderBatches() {
    const done = batches.filter((b) => b.status === 'completed').length;
    $('#gen-batches').innerHTML = batches
      .map((b, i) => {
        const cls =
          b.status === 'completed'
            ? 'bg-emerald-50 border-emerald-500/40 text-emerald-800'
            : b.status === 'failed'
            ? 'bg-rose-50 border-rose-500/40 text-rose-800'
            : 'bg-red-50 border-red-100 text-slate-600';
        const marker =
          b.status === 'completed'
            ? ico('circle-check', 'w-5 h-5 text-emerald-600')
            : b.status === 'failed'
            ? ico('circle-x', 'w-5 h-5 text-rose-600')
            : '<div class="w-4 h-4 rounded-full border-2 border-indigo-400 border-t-transparent animate-spin"></div>';
        return `
        <div class="p-3 rounded-xl border flex items-center justify-between transition-all ${cls}">
          <div class="flex items-center gap-3 min-w-0">
            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-red-100 text-slate-600">${i + 1}</span>
            <div class="truncate">
              <div class="text-sm font-semibold truncate">${esc(b.section)} <span class="text-xs opacity-75">(${esc(b.part)})</span></div>
              <div class="text-[11px] text-slate-500 truncate">${esc(b.topics)}</div>
            </div>
          </div>
          <div class="shrink-0 ml-3 flex items-center gap-2">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100">${b.count} questions</span>${marker}
          </div>
        </div>`;
      })
      .join('');
    $('#gen-progress').textContent = `${done} of ${batches.length} ready`;
    $('#gen-sub').textContent = 'Writing your questions and step-by-step solutions. This usually takes under a minute.';
    refreshIcons();
  }

  function setFailure(message) {
    const fail = $('#gen-fail');
    fail.textContent = message || '';
    fail.classList.toggle('hidden', !message);
    const failed = batches.some((b) => b.status === 'failed');
    $('#gen-retry').classList.toggle('hidden', !failed);
    $('#gen-close').classList.toggle('hidden', !failed);
    $('#gen-spinner').classList.toggle('hidden', failed);
  }

  async function runPending() {
    if (running) return;
    running = true;
    setFailure('');
    const todo = batches.filter((b) => b.status !== 'completed');
    todo.forEach((b) => (b.status = 'pending'));
    renderBatches();

    let firstError = '';
    await Promise.all(
      todo.map(async (b) => {
        try {
          await api('api/exam_batch.php', { exam_id: examId, batch_id: b.id });
          b.status = 'completed';
        } catch (err) {
          b.status = 'failed';
          firstError ||= err.message;
        }
        renderBatches();
      })
    );

    if (batches.every((b) => b.status === 'completed')) {
      try {
        await api('api/exam_finalize.php', { exam_id: examId });
        $('#start-exam').value = examId;
        $('#start-form').submit();
        return; // page navigates away
      } catch (err) {
        firstError = err.message;
      }
    }
    running = false;
    setFailure(
      `${firstError || 'We could not finish creating your test.'} Please try again, or close this and take a ready-made mock test.`
    );
  }

  async function launch() {
    const err = $('#gen-error');
    err.classList.add('hidden');
    try {
      const created = await api('api/exam_create.php', {
        title: state.title,
        total: state.total,
        duration: state.duration,
        positive: state.positive,
        negative: state.negative,
        difficulty: state.difficulty,
        include_diagrams: state.diagrams,
        sections: state.sections.map(({ name, topics, weight }) => ({ name, topics, weight })),
      });
      examId = created.exam_id;
      batches = created.batches;
      showModal(true);
      renderBatches();
      runPending();
    } catch (e) {
      err.innerHTML = `<p class="font-semibold">We could not create your test</p><p class="mt-0.5 text-rose-700">${esc(e.message)}</p>`;
      err.classList.remove('hidden');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  document.querySelectorAll('[data-launch]').forEach((b) => b.addEventListener('click', launch));
  $('#gen-retry').addEventListener('click', runPending);
  $('#gen-close').addEventListener('click', () => showModal(false));

  // ---------------------------------------------------------------- single subject + topic test
  $('#qt-subject')?.addEventListener('input', (e) => {
    $('#qt-topic-options').innerHTML = topicTokens(e.target.value)
      .map((t) => `<option value="${esc(t)}">`)
      .join('');
  });

  $('#qt-launch')?.addEventListener('click', () => {
    const err = $('#gen-error');
    const subject = $('#qt-subject').value.trim();
    const topic = $('#qt-topic').value.trim();
    if (!subject || !topic) {
      err.innerHTML = `<p class="font-semibold">Pick a subject and a topic</p><p class="mt-0.5 text-rose-700">Both fields are required for a topic-wise test — select one from the list or type your own.</p>`;
      err.classList.remove('hidden');
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
    state.presetId = null;
    state.title = `${subject} — ${topic} Practice`;
    state.total = Number($('#qt-count').value) || 20;
    state.duration = Number($('#qt-duration').value) || 30;
    state.sections = [{ id: `sec_${Date.now()}`, name: subject, topics: topic, weight: 100 }];
    syncForm();
    renderSections();
    launch();
  });

  applyPreset(presets[0]);
})();
