# MasterjeeCBT.Ai

AI-powered mock tests for Indian competitive exams — a server-rendered **PHP + MySQL** web app with an authentic
TCS iON-style exam hall. Pick an exam (SSC, UPSC, WB PSC, WB Police, banking, railways, JEE, NEET, CAT …), get a
fresh full-length timed paper written by Gemini, sit it in a real CBT interface, and read a detailed scorecard.

## What you can do

**Take tests**
- **20 exam presets** with realistic default question count, time and marking: SSC CGL, CHSL, MTS, GD, CPO · UPSC Prelims ·
  WB PSC Clerkship · WB Police · WB Primary TET · IBPS PO · SBI Clerk · RRB NTPC · RRB Group D · JEE Main · CAT · NEET UG ·
  WBCS Prelims · WBJEE · JENPAS-UG · GNM & ANM. Every default can be edited before you start.
- **Custom blueprint** — change the number of questions (5–200), time (10–240 min), marks per correct answer, negative
  marking, difficulty mix, diagrams on/off, and add/remove/reweight your own subjects and topics.
- **Single subject & topic test** — practise just one topic (e.g. *Physics → Rotational Mechanics*) with 10–50 questions.
- **Ready-made practice papers** (no AI needed): RRB NTPC, WBCS Prelims, WBJEE, JENPAS-UG, GNM & ANM, WB PSC Clerkship, and
  a free demo paper.
- **Free demo without an account.** Everything else needs a (free) account.

**Exam experience**
- **Instructions page** before every exam (time, questions, marking, section/topic table, how Save & Next / Mark for
  Review / Clear work, palette colour legend) in **English and বাংলা**. The clock starts only when you click
  *"Okay, I am ready to begin"*.
- **TCS iON-style exam hall**: countdown timer, section tabs, question palette (Not Visited / Not Answered / Answered /
  Marked / Answered & Marked), Save & Next, Mark for Review, Clear Response, submit summary.
- **English ⇄ বাংলা on every question** ("View in" toggle). Bengali is written in natural exam-paper Bengali (not a
  word-for-word translation), cached after the first use.
- **Candidate panel** with your name, email and which attempt number of this paper it is.
- Diagrams, charts, chemistry structures, biology figures and LaTeX-style maths render inline; click a figure to enlarge.
- Everything is **autosaved**; reload and resume where you left off. The server owns the clock and the answer key.

**After the exam**
- **Scorecard** with score, accuracy, sectional analytics, per-question time, full solutions, and PDF download.
- **AI performance coach** (offline analysis if no Gemini key).
- **My Test** history linked to your account, searchable and sortable, available on any device after login; one-click re-attempt.

**Accounts**
- Register with name, **Indian mobile number (+91)**, email and password (8+ characters, a capital letter, a digit and a
  special character; Weak / Medium / Strong meter; confirm-password). Log in with email *or* mobile. Passwords are stored hashed;
  repeated wrong logins are throttled.
- All alerts and errors appear as a popup.

**Question quality (AI tests)**
- Every question is **re-solved by a second, independent AI pass** (without seeing the answer key); any question whose
  answer disagrees, or where no option is right, is discarded and regenerated.
- Fresh papers every time (near-duplicates of earlier papers are dropped), varied diagrams, no "thinking-out-loud" text,
  and questions that reference a missing figure are rejected. See *How the AI paper is built* below.

## Quick start

Requirements: PHP 8.1+ with `pdo_mysql`, `curl`, `mbstring`; MySQL or MariaDB. Tailwind and Lucide load from CDNs, so the
browser needs internet access.

### macOS / Linux
```bash
cd php-app
cp config.sample.php config.php          # edit DB credentials / Gemini key if needed
database/reset_db.sh                     # creates and fills the cbt_portal database
php -S localhost:8000 -t public          # open http://localhost:8000
```

### Windows (XAMPP)
1. Copy this folder into `C:\xampp\htdocs\` (e.g. as `masterjeecbt`), start Apache and MySQL.
2. Run `database\reset_db.bat` (or import the SQL files by hand, in the order listed in that script).
3. Copy `config.sample.php` to `config.php`, then open <http://localhost/masterjeecbt/public/>.

### Gemini API key (for AI tests, Bengali and the coach)
Put the key in `config.php` → `gemini.api_key`, or set the `GEMINI_API_KEY` environment variable (free key:
<https://aistudio.google.com/app/apikey>). Without a key the AI buttons are disabled; ready-made papers and the demo still work.
`config.php` is git-ignored — never commit a real key.

### Existing database? Run the migrations
Fresh installs get everything from `schema.sql`. If you already have a `cbt_portal` database, run these once, in order:
`database/migrations/002_diagram_key.sql`, `003_users.sql`, `004_question_translations.sql`, `005_begun_at.sql`, then import
`presets_extra.sql`, `wbpsc_clerkship.sql`, `presets_extra2.sql`.

**Keeping machines in sync:** the SQL files are the source of truth. After a `git pull`, re-run `reset_db` to rebuild your
local database exactly (this wipes local users and attempts).

## Project layout

```
public/            web root: index.php (exam picker/blueprint), exam.php (+ instructions.inc.php), result.php, history.php,
                   start.php, api/*.php (JSON endpoints), assets/*.js (config, exam, result, auth, instructions, common)
src/               Auth, Exams, Attempts, Translate, Coach, Gemini, Freshness, MathRenderer, Svg/Bio/Chart/MoleculeSvg,
                   bootstrap (config, session, CSRF, errors), layout (header, brand logo, nav, login modal)
database/          schema.sql, seed.sql, paper/preset SQL files, migrations/, reset_db.bat / reset_db.sh
tools/             build-*.php generators for the ready-made papers
config.php         DB + Gemini settings (git-ignored; copy from config.sample.php)
```

### Data model
`users` · `presets` / `preset_sections` (exam templates) · `exams` → `exam_batches`, `questions` → `question_options`,
`question_translations` (cached Bengali) · `attempts` (linked to `users`; `begun_at` marks when the clock started) →
`attempt_answers` (palette state, chosen option, seconds per question) · `coach_reports`.

## How it works

- **Server-owned clock and answer key.** Correct answers and explanations never reach the browser during an exam. The
  deadline is `started_at + duration` (`started_at` is reset when you begin); saves after it (plus 15 s grace) are rejected
  and the attempt is auto-submitted. Scores are computed in PHP.
- **Access control.** Only the demo paper is open to guests. Starting any other test, or creating an AI test, requires login
  (enforced on the server, not just in the UI). Attempts belong to the account that started them.
- **Autosave.** Each Save/Mark/Clear and a 15-second heartbeat write to MySQL.
- **Gemini key stays on the server.**

### How the AI paper is built
1. The blueprint is split into stored **batches** (one per section/part). The browser requests one batch at a time; a failed
   batch can be retried alone and the paper is finalized only when all are done.
2. For each batch Gemini writes questions with a step-by-step explanation first, then the answer key. Malformed questions
   (not exactly 4 options, empty text, bad key) are skipped.
3. **Verification:** a second call solves each question blind; mismatches are dropped and re-requested. If the checker itself
   is unavailable, questions pass unchanged so test creation never blocks.
4. **Freshness** (`src/Freshness.php`): earlier questions for the same section are shown to the model as "avoid" hints, and
   near-copies are discarded (at most a handful of near-repeats per paper, never copies within a paper). Diagram choice is
   tracked in `questions.diagram_key` so pictures vary between papers.
5. **Figures are drawn by the server**, not the AI, from structured data the AI supplies — bar/line/pie charts, Punnett
   squares, pedigrees, energy pyramids, flow/cycle diagrams, DNA→mRNA→protein strips, 16 labelled biology diagrams
   (letters shuffled per picture), and organic structures (benzene, rings, chains, isomer comparisons). Other model-drawn SVG
   is sanitized (scripts, `foreignObject`, event handlers, `javascript:` links removed).

## Customising

**Add or edit an exam preset** — add a row to `presets` and its sections to `preset_sections` (see `database/presets_extra.sql`
for the pattern: id, name, badge, minutes, +/- marks, default question count, sort order, and weighted sections with topics).

**Add a ready-made paper** — copy a `tools/build-<name>.php` script (e.g. `build-wbpsc-clerkship.php`), replace the question
list, run `php tools/build-<name>.php` to produce `database/<name>.sql`, import it, and add it to `reset_db.bat` and
`reset_db.sh`. It appears automatically under *Ready-made Practice Papers*.

**Rebrand** — the logo and wordmark live in `src/layout.php` (`brand_mark()`, `brand_lockup()`, `brand_favicon()`).

## Known limits
- The mobile number is format-checked (Indian 10-digit, starts 6–9) but **not OTP-verified**; there is no email verification
  or password-reset flow yet.
- The Bengali toggle covers the exam hall and instructions; the result/review page is English only.
- "Estimated percentile" is an illustrative curve, not real ranking data.
- Gemini free-tier quotas can slow large (100–200 question) papers.
- The built-in demo paper contains a few near-duplicate questions inherited from the original source.
