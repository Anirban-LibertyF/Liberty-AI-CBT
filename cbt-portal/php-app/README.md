# MasterJee.AI — PHP + MySQL edition

The React/Vite app in `../src` rebuilt as a server-rendered PHP site backed by MySQL/MariaDB. Same three screens (blueprint builder → exam hall → scorecard), same TCS iON-style palette, same math/SVG rendering, same Gemini integration. The React project is untouched.

## Setup (XAMPP)

1. **Copy this folder** into `C:\xampp\htdocs\` under a new name, e.g. `nexuscbt`.
   (`htdocs\cbt-portal` already exists with an older, smaller PHP version — don't overwrite it.)
2. **Start Apache and MySQL** in the XAMPP control panel.
3. **Import the database.** Easiest: run `database\reset_db.bat` (Windows/XAMPP) — it drops and
   rebuilds `cbt_portal` from `schema.sql` + `seed.sql` in one go, so every machine ends up with
   the exact same presets and demo paper (no duplicate-key errors from re-importing over old data).
   Or by hand, in order: `database/schema.sql`, then `database/seed.sql`
   (in phpMyAdmin → *Import*, or `C:\xampp\mysql\bin\mysql.exe -u root cbt_portal < database\schema.sql`
   and the same for `seed.sql` — only works on a fresh/empty database).
   This creates the `cbt_portal` database, the 5 exam presets (SSC CGL, IBPS PO, JEE Main, CAT, NEET UG)
   and the 57-question demo paper.
4. **Open** <http://localhost/nexuscbt/>. The "Instant demo exam" works immediately.

**Keeping every machine in sync:** `schema.sql`/`seed.sql` are the single source of truth for the
database — they're committed to git and kept in lockstep with the live DB used for development.
If your local site ever looks different from what's on GitHub (missing/extra presets, old badges),
it almost always means your local `cbt_portal` has leftover data from an earlier setup. Re-run
`database\reset_db.bat` after a `git pull` to wipe it and rebuild from the current files exactly.

`config.php` already holds XAMPP's defaults (`root`, empty password, port 3306). Edit it if yours differ.

### Organic chemistry paper with structure diagrams
Import `database/organic_chemistry.sql` (after `schema.sql` and `seed.sql`). It adds:
- a built-in **"Organic Chemistry: Structures & Mechanisms"** paper (12 MCQs) whose diagrams are skeletal formulas: benzene with Kekulé bonds, toluene, nitrobenzene, phenol, aspirin, naphthalene, cinnamaldehyde, zig-zag chain models (3-methylhexane), butane/isobutane, cis/trans but-2-ene, phenol vs ethanol;
- a **NEET Organic Chemistry** exam preset for the AI generator.

Re-importing the file replaces that paper (and deletes attempts made on it). To change the questions, edit `tools/build-organic.mjs` and run `node tools/build-organic.mjs` (add `--svg some\folder` to dump the diagrams as .svg files).

**How AI chemistry diagrams work:** language models draw molecules badly (in testing, a requested hex-3-ene came back as a branched alkane and a naphthalene lost two double bonds). So for chemistry sections the AI only *describes* the molecule (benzene / naphthalene with substituents at numbered positions, or a chain with bond orders and branches) and `src/MoleculeSvg.php` draws it. Specs are validated; anything invalid simply gets no diagram. Model-drawn SVG is ignored for chemistry batches. The drawing always matches the spec, but the question text and answer key still come from the model, so spot-check chemistry papers. Rings other than benzene/naphthalene (e.g. cyclohexane chairs) aren't supported yet.

### NEET Biology paper (Botany, Zoology, Genetics, Ecology)
Import `database/neet_biology.sql` too. It adds a built-in **"NEET Biology: Botany, Zoology & Genetics"** paper (40 MCQs, 31 with diagrams) and a **NEET Biology** preset. Diagrams (all drawn by `src/BioSvg.php`, none by the AI): animal and plant cell, DNA segment (antiparallel, hydrogen bonds), the four stages of mitosis, human heart, myelinated neuron, flower (longitudinal section), nephron, Punnett squares (monohybrid and dihybrid, computed), a pedigree chart, and an energy pyramid.

Answer keys are spread evenly over A/B/C/D (the builders shuffle option positions with a fixed seed). To change the questions edit `tools/build-bio.php` and run `C:\xampp\php\php.exe tools\build-bio.php` (add `--svg some\folder` to dump the diagrams). Re-importing replaces the paper.

**AI biology diagrams:** for sections about botany/zoology/genetics/ecology the AI picks from the catalog of pre-drawn labelled diagrams (the prompt lists each letter and the part it marks), or specifies a Punnett cross (`RrYy` × `RrYy`) or an energy pyramid, and the server draws it. Punnett grids are computed, so they are always right. The question text and key still come from the model, so spot-check generated papers. Pedigrees are available to hand-written questions only.

### Fresh questions and fresh diagrams every time
Every AI paper is built to differ from the ones before it (`src/Freshness.php`):
- **Questions.** The server looks up earlier questions for the same section (built-in papers and earlier AI papers) and tells the AI to avoid them, with a random "set code", a shuffled topic order and a slightly random temperature. Whatever comes back is compared word-by-word with the earlier questions; a near-copy is dropped and the AI is asked again for the shortfall. Only if the AI still can't fill a batch are a few near-repeats allowed, and never more than **10 per paper** (about a quarter of a small paper). Copies inside the same paper are never kept. A batch that can only produce copies completes short instead of failing.
- **Diagrams.** The server remembers which diagram each question used (`questions.diagram_key`, added by `database/migrations/002_diagram_key.sql`; fresh installs get it from `schema.sql`). The AI is told which pictures were used recently, and the server enforces variety: a picture appears at most twice in a paper and at most 40% of a batch's pictures can be recent ones. The AI also has far more kinds of figure to choose from now: data **charts** (bar/line/pie) for any subject, **pedigrees** it designs itself, any **Punnett cross**, **energy pyramids**, **cycloalkane rings**, **side-by-side isomer comparisons**, and three more labelled biology diagrams (synapse, lac operon, replication fork). All are drawn by the server from the numbers/structures the AI supplies, so they always match the question.
- **Biology pictures never look the same twice.** There are now 16 labelled diagrams (added: mitochondrion, chloroplast, T4 phage, eye, sarcomere, synapse, lac operon, replication fork). Each use gets its own look: flowers vary in petal colour, extra petals, stamens and ovules; most other diagrams get a new colour scheme; and **the letters are shuffled per picture** (A is no longer always the nucleus). The AI writes `{{Part name}}` and the server fills in the letter shown, so question, options, explanation and picture always agree. Each batch is offered only a random *menu* of diagrams (preferring ones not used recently, enforced in the response schema), and a diagram can appear once per paper. On top of that the AI can ask for figures that are generated fresh from data every time: Punnett crosses with any genes, pedigrees, bar/line/pie charts, energy pyramids, **flow diagrams** (food chains, nitrogen/carbon cycles, pathways, with "?" for the missing step) and **DNA to mRNA to protein strips** (the code computes the strands and amino acids).
- **No thinking-out-loud.** Questions whose text contains self-corrections ("let's adjust…", "wait,…") or internal jargon are discarded and replaced.
- **The built-in NEET Biology paper has 29 different pictures for 29 diagram questions** (rebuild with \`C:\\xampp\\php\\php.exe tools\\build-bio.php\`).
- **Safety net.** If a question says "refer to the chart/figure/pedigree" but no valid figure could be drawn, the question is rejected and replaced; a question that points at a missing picture is never kept.

### Optional: live AI generation + AI coach
Put a Gemini key in `config.php` → `gemini.api_key` (free at <https://aistudio.google.com/app/apikey>) or set the `GEMINI_API_KEY` environment variable. Without a key the AI buttons are disabled and the coach falls back to an offline analysis built from your scorecard.

### Without Apache
```
C:\xampp\php\php.exe -S localhost:8000 -t public
```
Use XAMPP's PHP: it has `pdo_mysql` and `curl`; a stand-alone PHP often doesn't. PHP's built-in server is single-threaded, so the "parallel" AI batches run one after another there. Under Apache they truly run concurrently.

Needs PHP 8.1+ with `pdo_mysql`, `curl`, `mbstring`. Tailwind and Lucide icons load from CDNs, so the browser needs internet access.

## Layout

```
public/            web root: index.php (blueprint), exam.php, result.php, start.php, api/*.php, assets/*.js
src/               PHP classes — Exams, Attempts, Coach, Gemini, MathRenderer, Svg, bootstrap
database/          schema.sql, seed.sql (generated)
tools/build-seed.mjs   regenerates seed.sql from ../src/App.jsx (node tools/build-seed.mjs)
config.php         DB + Gemini settings
```

### Data model
`presets` / `preset_sections` (templates) · `exams` → `exam_batches`, `questions` → `question_options` · `attempts` → `attempt_answers` (palette state, chosen option, seconds per question) · `coach_reports`.

## What changed vs. the React version (on purpose)

- **The server owns the clock and the answer key.** Correct answers and explanations are never sent to the browser during an exam (in the React app they sat in client memory). The deadline is `started_at + duration`; saves after it (plus 15 s grace) are rejected and the attempt is auto-submitted.
- **Everything is autosaved.** Each Save/Mark/Clear and a 15-second heartbeat write to MySQL, so a reload resumes the attempt with palette state and per-question times intact. Scores are computed in PHP and stored on submit.
- **The Gemini key stays on the server** (it was embedded in the JS bundle via `VITE_GEMINI_API_KEY`).
- **AI generation is split into stored batches.** The browser fires one request per batch; a failed batch can be retried alone without regenerating the rest, and the paper is only finalized when every batch is done.
- **Malformed AI questions are skipped** (not exactly 4 options, empty text, or an invalid answer key) instead of being padded with "Option A" placeholders and a default key of A.
- **AI-supplied SVG is sanitized server-side** (scripts, `foreignObject`, event handlers, `javascript:` links) and all question text is HTML-escaped before the math renderer emits markup.
- Small fixes: `\text{...}` in math now shows its contents without literal braces; the demo button shows the real question count (57, not "50"); roll number derives from the attempt id; the coach card says when it's the offline fallback.
- Access is scoped per browser session (an attempt/AI paper can only be opened by the session that created it). There are no user accounts, as in the original.

## Known limits
- The demo paper is copied verbatim from the React source, including its near-duplicate questions (e.g. Q5/Q9, Q16/Q19) and one explanation with leftover "wait" text (Q23). Fix them in the database or in `App.jsx` and re-run `tools/build-seed.mjs`.
- "Estimated percentile" is the original's made-up curve, not real ranking data.
