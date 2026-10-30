// One-off: converts the dark slate/indigo Tailwind classes in the dark pages to a white/red theme.
// Usage: node tools/retheme.mjs <file...>   (idempotent only on the original dark classes)
import { readFileSync, writeFileSync } from 'node:fs';

const KEEP_WHITE = /\b(?:bg|from)-(?:indigo|red|emerald|rose|purple|blue|amber)-[5-9]00(?![\d/])/;
const TONE = { 200: 800, 300: 700, 400: 600 }; // light-on-dark text -> dark-on-light text
const slateText = { 100: 'slate-900', 200: 'slate-700', 300: 'slate-600', 400: 'slate-500' };

function mapToken(tok, seg) {
  const m = tok.match(/^((?:[a-z]+:)*)(bg|text|border|divide|from|via|to)-([a-z]+)-(\d+)(\/\d+)?$/);
  if (tok.match(/^((?:[a-z]+:)*)text-white$/)) {
    const prefix = tok.slice(0, tok.length - 'text-white'.length);
    const iconOnly = /^w-\d+ h-\d+ text-white$/.test(seg.trim());
    return KEEP_WHITE.test(seg) || iconOnly ? tok : prefix + 'text-slate-900';
  }
  if (!m) return tok;
  const [, pre, prop, color, shadeStr, alpha = ''] = m;
  const shade = Number(shadeStr);
  const overlay = /\bfixed\b/.test(seg) && /\binset-0\b/.test(seg);
  const hover = pre.includes('hover:');

  if (color === 'slate') {
    if (prop === 'bg') {
      if (shade === 950) return overlay ? tok : `${pre}bg-red-50`;
      if (shade === 900) return overlay ? tok : `${pre}bg-white`;
      if (shade === 800) return `${pre}bg-${hover ? 'red-50' : 'red-100'}`;
      if (shade === 700) return `${pre}bg-red-200`;
    }
    if (prop === 'border') {
      if (shade === 800) return `${pre}border-red-100`;
      if (shade === 700) return `${pre}border-red-200`;
    }
    if (prop === 'divide' && shade === 800) return `${pre}divide-red-100`;
    if (prop === 'via' && shade === 900) return `${pre}via-white`;
    if (prop === 'to' && shade === 900) return `${pre}to-white`;
    if (prop === 'text' && slateText[shade]) return `${pre}text-${slateText[shade]}`;
    return tok;
  }
  if (color === 'indigo') {
    if (prop === 'bg' && shade === 950) return `${pre}bg-red-50`;
    if (prop === 'from' && (shade === 950 || shade === 900)) return `${pre}from-red-${shade === 950 ? 50 : 100}`;
    if (prop === 'text' && TONE[shade]) return `${pre}text-red-${TONE[shade]}`;
    return tok;
  }
  if (['emerald', 'rose', 'amber', 'purple'].includes(color)) {
    if (prop === 'bg' && shade === 950) return `${pre}bg-${color}-50`;
    if (prop === 'text' && TONE[shade]) return `${pre}text-${color}-${TONE[shade]}`;
  }
  return tok;
}

for (const file of process.argv.slice(2)) {
  const src = readFileSync(file, 'utf8');
  // Every quoted run is treated as a class list; only tokens that look like Tailwind classes are touched.
  const out = src.replace(/"[^"\n]*"|'[^'\n]*'/g, (seg) =>
    seg.replace(/[^\s"'`{}$]+/g, (tok) => mapToken(tok, seg))
  );
  writeFileSync(file, out);
  console.log(file, out === src ? 'unchanged' : 'converted');
}
