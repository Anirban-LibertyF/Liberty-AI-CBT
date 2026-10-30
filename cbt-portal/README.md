# NexusCBT — AI Competitive Exam Portal (Local Clone)

A local, fully-working copy of the AI-powered CBT (Computer-Based Test) portal: exam blueprint builder, authentic TCS iON-style exam hall UI (timer, question palette, mark-for-review), SVG diagram rendering, inline math/LaTeX rendering, scorecard with sectional analytics, and an AI performance coach.

## 1. Install dependencies

You need [Node.js](https://nodejs.org) 18+ installed. Then, in this folder:

```bash
npm install
```

## 2. (Optional) Add a Gemini API key

The **"Instant 50-MCQ Demo Exam"** button works completely offline — no key needed. It loads a pre-built 57-question paper with diagrams and full CBT functionality.

The **"Generate Live AI CBT"** button calls the Gemini API to generate fresh questions on demand. For that to work:

1. Get a free API key at https://aistudio.google.com/app/apikey
2. Copy `.env.example` to `.env`
3. Paste your key into `VITE_GEMINI_API_KEY=`

```bash
cp .env.example .env
```

## 3. Run it

```bash
npm run dev
```

Open the URL it prints (usually http://localhost:5173).

## 4. Build for production (optional)

```bash
npm run build
npm run preview
```

## What's inside

- `src/App.jsx` — the entire application (exam config screen, live CBT exam hall, results/scorecard screen), ported from the original single-file component.
- Three view modes managed by React state: `CONFIG` → `EXAM` → `RESULT`.
- A custom `MathText` renderer that converts LaTeX-style notation (`\frac{}{}`, `\sqrt{}`, `\int`, Greek letters, etc.) into readable inline math without external libraries.
- A `DiagramRenderer` that safely renders inline SVG figures (geometry diagrams, bar charts, Venn diagrams, binary trees, pulley systems) generated either by the AI or from the built-in demo paper.

## Notes on changes from the original

- The original was built to run inside Google AI Studio's sandbox, which injects the Gemini API key automatically (the source had `const apiKey = ""`). I wired it to read from `VITE_GEMINI_API_KEY` in a `.env` file instead, with a clear error message if it's missing, so it fails gracefully rather than silently.
- Updated the model name from `gemini-3-flash-preview` to `gemini-2.5-flash` (the preview alias may not be publicly available outside AI Studio — swap it for whichever model your API key has access to if needed).
- The original's built-in 50-question demo data had several duplicate `id` values (and a few literally duplicated question blocks) from how it was generated — this caused React key-collision warnings and broken palette navigation. I renumbered all IDs sequentially so every question is uniquely addressable; no question text/content was altered. The demo paper now has 57 questions (not exactly 50 as the label suggests) since the duplicates were duplicate *entries*, not a round count.

## Troubleshooting

- **Blank page / console errors about `lucide-react`:** make sure `npm install` completed without errors.
- **"No Gemini API key configured" error:** either add a key to `.env` (see above) or just use the Instant Demo Exam button.
- **Port already in use:** change the `port` in `vite.config.js`.
