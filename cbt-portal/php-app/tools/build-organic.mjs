// Builds database/organic_chemistry.sql: an "Organic Chemistry" practice paper whose questions carry
// skeletal-structure diagrams (benzene rings, fused rings, zig-zag chains, cis/trans alkenes), plus a
// NEET Organic Chemistry exam preset.
//
//   node tools/build-organic.mjs            -> writes database/organic_chemistry.sql
//   node tools/build-organic.mjs --svg DIR  -> also writes each diagram as DIR/qN.svg for eyeballing
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));

// ------------------------------------------------------------------ tiny skeletal-formula drawer
const B = 44; // bond length (px)
const INK = '#0f172a';
const rad = (d) => (d * Math.PI) / 180;
const pt = (x, y) => ({ x, y });
const polar = (p, deg, len = B) => pt(p.x + len * Math.cos(rad(deg)), p.y + len * Math.sin(rad(deg)));
const f = (n) => Number(n.toFixed(1));

class Canvas {
  constructor() {
    this.parts = [];
    this.pts = [];
  }
  track(...ps) {
    this.pts.push(...ps);
  }
  line(a, b, color = INK, w = 2.4) {
    this.track(a, b);
    this.parts.push(`<line x1="${f(a.x)}" y1="${f(a.y)}" x2="${f(b.x)}" y2="${f(b.y)}" stroke="${color}" stroke-width="${w}" stroke-linecap="round"/>`);
  }
  /** order 1|2. `inward` (ring centre) puts the 2nd line of a ring double bond inside the ring. */
  bond(a, b, { order = 1, inward = null, cutA = 0, cutB = 0, color = INK } = {}) {
    const L = Math.hypot(b.x - a.x, b.y - a.y);
    const u = pt((b.x - a.x) / L, (b.y - a.y) / L);
    const n = pt(-u.y, u.x);
    const A = pt(a.x + u.x * cutA, a.y + u.y * cutA);
    const Bp = pt(b.x - u.x * cutB, b.y - u.y * cutB);
    if (order === 1) return this.line(A, Bp, color);
    if (inward) {
      const mid = pt((a.x + b.x) / 2, (a.y + b.y) / 2);
      const s = Math.sign((inward.x - mid.x) * n.x + (inward.y - mid.y) * n.y) || 1;
      this.line(A, Bp, color);
      this.line(
        pt(a.x + u.x * (cutA + 8) + n.x * 5.5 * s, a.y + u.y * (cutA + 8) + n.y * 5.5 * s),
        pt(b.x - u.x * (cutB + 8) + n.x * 5.5 * s, b.y - u.y * (cutB + 8) + n.y * 5.5 * s),
        color
      );
    } else {
      for (const s of [-1, 1]) this.line(pt(A.x + n.x * 2.8 * s, A.y + n.y * 2.8 * s), pt(Bp.x + n.x * 2.8 * s, Bp.y + n.y * 2.8 * s), color);
    }
  }
  /** Atom label with _sub / ^sup markup, e.g. CH_3, NO_2, O^-. The first letter sits on the atom position. */
  label(p, text, { color = INK, size = 17, anchor = 'start', weight = 600 } = {}) {
    let out = '';
    let plain = 0;
    for (const m of text.matchAll(/([_^])([0-9+\-−]+)|([^_^]+)/g)) {
      if (m[1]) {
        const up = m[1] === '^';
        out += `<tspan dy="${up ? -6 : 4}" font-size="${Math.round(size * 0.68)}">${m[2].replace('-', '−')}</tspan><tspan dy="${up ? 6 : -4}">​</tspan>`;
        plain += 0.6;
      } else {
        out += m[3];
        plain += m[3].length;
      }
    }
    const x = anchor === 'start' ? p.x - size * 0.34 : p.x;
    this.track(pt(p.x - size * 0.6, p.y - size * 0.7), pt(p.x + plain * size * 0.62, p.y + size * 0.7));
    this.parts.push(
      `<text x="${f(x)}" y="${f(p.y)}" dominant-baseline="central" text-anchor="${anchor}" font-family="Arial, sans-serif" font-size="${size}" font-weight="${weight}" fill="${color}" stroke="#f8fafc" stroke-width="4" paint-order="stroke" stroke-linejoin="round">${out}</text>`
    );
  }
  /** Hexagon; `start` = angle of vertex 0. Kekulé doubles on edges 0,2,4. */
  ring(c, start = -90, { kekule = true, skip = [] } = {}) {
    const v = [0, 1, 2, 3, 4, 5].map((k) => polar(c, start + 60 * k, B));
    for (let k = 0; k < 6; k++) {
      if (skip.includes(k)) continue;
      this.bond(v[k], v[(k + 1) % 6], { order: kekule && k % 2 === 0 ? 2 : 1, inward: c });
    }
    return v;
  }
  svg() {
    const xs = this.pts.map((p) => p.x);
    const ys = this.pts.map((p) => p.y);
    const pad = 30;
    let minX = Math.min(...xs) - pad, maxX = Math.max(...xs) + pad;
    let minY = Math.min(...ys) - pad, maxY = Math.max(...ys) + pad;
    const grow = (lo, hi, min) => (hi - lo >= min ? [lo, hi] : [(lo + hi) / 2 - min / 2, (lo + hi) / 2 + min / 2]);
    [minX, maxX] = grow(minX, maxX, 300);
    [minY, maxY] = grow(minY, maxY, 170);
    const w = f(maxX - minX), h = f(maxY - minY);
    return `<svg viewBox="${f(minX)} ${f(minY)} ${w} ${h}" xmlns="http://www.w3.org/2000/svg">\n<rect x="${f(minX)}" y="${f(minY)}" width="${w}" height="${h}" fill="#f8fafc" rx="12"/>\n${this.parts.join('\n')}\n</svg>`;
  }
}

const O_COL = '#b91c1c';
const N_COL = '#1d4ed8';
const tag = (cv, p, text) => cv.label(p, text, { color: '#b91c1c', size: 18, anchor: 'middle', weight: 700 });

// ------------------------------------------------------------------ molecules
const O0 = pt(0, 0);

function benzene() {
  const cv = new Canvas();
  cv.ring(O0, -90);
  return cv.svg();
}

function toluene() {
  const cv = new Canvas();
  const v = cv.ring(O0, -90);
  const m = polar(v[0], -90);
  cv.bond(v[0], m, { cutB: 10 });
  cv.label(m, 'CH_3');
  return cv.svg();
}

function phenol() {
  const cv = new Canvas();
  const v = cv.ring(O0, -90);
  const o = polar(v[0], -90);
  cv.bond(v[0], o, { cutB: 11 });
  cv.label(o, 'OH', { color: O_COL });
  return cv.svg();
}

function nitrobenzene() {
  const cv = new Canvas();
  const v = cv.ring(O0, -90);
  const n = polar(v[0], -90);
  cv.bond(v[0], n, { cutB: 11 });
  cv.label(n, 'N^+', { color: N_COL });
  const oR = polar(n, -30), oL = polar(n, -150);
  cv.bond(n, oR, { order: 2, cutA: 11, cutB: 10 });
  cv.label(oR, 'O', { color: O_COL });
  cv.bond(n, oL, { cutA: 11, cutB: 10 });
  cv.label(pt(oL.x - 4, oL.y), 'O^-', { color: O_COL });
  return cv.svg();
}

function methylhexane() {
  const cv = new Canvas();
  const c = [0, 1, 2, 3, 4, 5].map((i) => pt(38.1 * i, i % 2 === 0 ? 22 : 0));
  for (let i = 0; i < 5; i++) cv.bond(c[i], c[i + 1]);
  cv.bond(c[2], polar(c[2], 90));
  return cv.svg();
}

function cinnamaldehyde() {
  const cv = new Canvas();
  const v = cv.ring(O0, -90);
  const a = polar(v[1], -30), b = polar(a, 30), c = polar(b, -30), o = polar(c, -90);
  cv.bond(v[1], a);
  cv.bond(a, b, { order: 2 });
  cv.bond(b, c);
  cv.bond(c, o, { order: 2, cutB: 10 });
  cv.label(o, 'O', { color: O_COL });
  return cv.svg();
}

function aspirin() {
  const cv = new Canvas();
  const v = cv.ring(O0, -90);
  // carboxylic acid on vertex 1
  const c1 = polar(v[1], -30), oDouble = polar(c1, -90), oh = polar(c1, 30);
  cv.bond(v[1], c1);
  cv.bond(c1, oDouble, { order: 2, cutB: 10 });
  cv.label(oDouble, 'O', { color: O_COL });
  cv.bond(c1, oh, { cutB: 11 });
  cv.label(oh, 'OH', { color: O_COL });
  // acetate ester on the neighbouring vertex 2
  const oe = polar(v[2], 30), c2 = polar(oe, 90), o2 = polar(c2, 150), me = polar(c2, 30);
  cv.bond(v[2], oe, { cutB: 11 });
  cv.label(oe, 'O', { color: O_COL });
  cv.bond(oe, c2, { cutA: 11 });
  cv.bond(c2, o2, { order: 2, cutB: 10 });
  cv.label(o2, 'O', { color: O_COL });
  cv.bond(c2, me, { cutB: 10 });
  cv.label(me, 'CH_3');
  return cv.svg();
}

function butaneAndIsobutane() {
  const cv = new Canvas();
  const bu = [0, 1, 2, 3].map((i) => pt(-230 + 38.1 * i, i % 2 === 0 ? 22 : 0));
  for (let i = 0; i < 3; i++) cv.bond(bu[i], bu[i + 1]);
  const c0 = pt(60, 0);
  for (const a of [-90, 30, 150]) cv.bond(c0, polar(c0, a));
  tag(cv, pt(-173, 78), 'I');
  tag(cv, pt(60, 78), 'II');
  return cv.svg();
}

function cisTransButene() {
  const cv = new Canvas();
  const draw = (dx, trans) => {
    const c2 = pt(dx, 0), c3 = pt(dx + B, 0);
    cv.bond(c2, c3, { order: 2 });
    cv.bond(c2, polar(c2, -120));
    cv.bond(c3, polar(c3, trans ? 60 : -60));
  };
  draw(-170, false);
  draw(80, true);
  tag(cv, pt(-148, 50), 'P');
  tag(cv, pt(102, 50), 'Q');
  return cv.svg();
}

function naphthalene() {
  const cv = new Canvas();
  const c1 = pt(0, 0), c2 = pt(2 * B * Math.cos(rad(30)), 0);
  const L = [0, 1, 2, 3, 4, 5].map((k) => polar(c1, -90 + 60 * k));
  const R = [0, 1, 2, 3, 4, 5].map((k) => polar(c2, -90 + 60 * k));
  for (let k = 0; k < 6; k++) cv.bond(L[k], L[(k + 1) % 6], { order: k % 2 === 0 ? 2 : 1, inward: c1 });
  for (let k = 0; k < 6; k++) {
    if (k === 4) continue; // shared edge is already drawn as part of the left ring
    cv.bond(R[k], R[(k + 1) % 6], { order: k % 2 === 0 && k !== 4 ? 2 : 1, inward: c2 });
  }
  return cv.svg();
}

// ethanol drawn properly: CH3-CH2-OH as skeleton (methyl is implicit at the chain end)
function phenolAndEthanolClean() {
  const cv = new Canvas();
  const c = pt(-110, 0);
  const v = cv.ring(c, -90);
  const o = polar(v[0], -90);
  cv.bond(v[0], o, { cutB: 11 });
  cv.label(o, 'OH', { color: O_COL });
  const e = [0, 1, 2].map((i) => pt(70 + 38.1 * i, i % 2 === 0 ? 22 : 0));
  cv.bond(e[0], e[1]);
  cv.bond(e[1], e[2], { cutB: 11 });
  cv.label(e[2], 'OH', { color: O_COL });
  tag(cv, pt(-110, 76), 'A');
  tag(cv, pt(108, 76), 'B');
  return cv.svg();
}

// ------------------------------------------------------------------ questions
const S = 'Organic Chemistry';
const Q = [
  {
    topic: 'Benzene Structure & Bonding (Diagram)', difficulty: 'Easy', svg: benzene(),
    text: 'The Kekulé structure of benzene (C_6H_6) is shown. Counting every bond in the molecule (the delocalised π system counted as 3 π bonds), how many σ and π bonds does one benzene molecule contain?',
    options: ['12 σ and 3 π', '9 σ and 6 π', '15 σ and 3 π', '12 σ and 6 π'], correct: 'A',
    explanation: 'σ bonds: 6 C–C (ring) + 6 C–H = 12. Each ring carbon is sp² hybridised and keeps one unhybridised p orbital; these six p orbitals overlap sideways to give the delocalised π cloud, equivalent to 3 π bonds in the Kekulé picture. Total: 12 σ and 3 π.',
  },
  {
    topic: 'Directive Influence in Aromatic Substitution (Diagram)', difficulty: 'Medium', svg: toluene(),
    text: 'The compound shown (toluene) is mono-nitrated with conc. HNO_3 / conc. H_2SO_4. Which product(s) form mainly?',
    options: ['o-Nitrotoluene and p-nitrotoluene', 'm-Nitrotoluene only', 'p-Nitrotoluene exclusively', 'o-Nitrotoluene exclusively'], correct: 'A',
    explanation: 'The –CH_3 group is electron-donating (+I and hyperconjugation), so it activates the ring and directs the incoming NO_2^+ to the ortho and para positions, where the arenium ion has a resonance form with the positive charge on the carbon bearing CH_3 (a stable tertiary-like cation). Both o- and p-isomers form, with p usually major for steric reasons.',
  },
  {
    topic: 'Meta Directing Groups (Diagram)', difficulty: 'Medium', svg: nitrobenzene(),
    text: 'Nitrobenzene (shown) is heated with fuming HNO_3 / conc. H_2SO_4. What is the main product?',
    options: ['1,2-Dinitrobenzene', '1,3-Dinitrobenzene', '1,4-Dinitrobenzene', '1,3,5-Trinitrobenzene'], correct: 'B',
    explanation: 'The nitro group is strongly electron-withdrawing (–M, –I): it deactivates the ring and destabilises the arenium ions from ortho/para attack (positive charge lands next to the N^+). Meta attack avoids this, so the second NO_2 enters at the meta position, giving 1,3-dinitrobenzene (m-dinitrobenzene).',
  },
  {
    topic: 'Reactions of Phenol (Diagram)', difficulty: 'Medium', svg: phenol(),
    text: 'The compound shown is treated with excess bromine water at room temperature. The product is a white precipitate. Identify it.',
    options: ['2,4,6-Tribromophenol', '4-Bromophenol', '2-Bromophenol', '3,5-Dibromophenol'], correct: 'A',
    explanation: 'The –OH group is a strong activator and ortho/para director (lone pair donation). In polar bromine water no Lewis acid is needed, and substitution continues at all three activated positions (2, 4 and 6), giving the white precipitate of 2,4,6-tribromophenol.',
  },
  {
    topic: 'IUPAC Nomenclature of Alkanes (Chain Diagram)', difficulty: 'Medium', svg: methylhexane(),
    text: 'In this skeletal (line-angle) formula each vertex and each end of a line is a carbon atom, with hydrogens implied. What is the IUPAC name of the compound?',
    options: ['2-Methylhexane', '3-Methylhexane', '4-Methylhexane', '3-Ethylpentane'], correct: 'B',
    explanation: 'The longest continuous chain has 6 carbons (hexane). The one-carbon branch sits on the third carbon counted from the left end (and the fourth from the right), so we number from the end that gives the lower locant: 3. Name: 3-methylhexane. "4-Methylhexane" is never correct because it violates the lowest-locant rule, and the chain through the branch is shorter, so it is not 3-ethylpentane.',
  },
  {
    topic: 'Hybridisation & π Bonds in a Conjugated System (Diagram)', difficulty: 'Hard', svg: cinnamaldehyde(),
    text: 'The skeletal structure of cinnamaldehyde (C_9H_8O) is shown. How many sp² hybridised carbon atoms and how many π bonds does it contain?',
    options: ['9 sp² carbons and 5 π bonds', '8 sp² carbons and 5 π bonds', '9 sp² carbons and 4 π bonds', '6 sp² carbons and 3 π bonds'], correct: 'A',
    explanation: 'All 9 carbons are sp²: 6 in the benzene ring, 2 in the C=C, and 1 in the C=O (carbonyl carbon). π bonds: 3 in the ring (Kekulé) + 1 C=C + 1 C=O = 5. There is no sp³ or sp carbon, so the whole molecule is planar and conjugated.',
  },
  {
    topic: 'Functional Groups (Diagram)', difficulty: 'Medium', svg: aspirin(),
    text: 'The structure of aspirin (acetylsalicylic acid) is shown. Which two functional groups are present on the benzene ring?',
    options: ['Carboxylic acid and ester', 'Carboxylic acid and ether', 'Ester and ketone', 'Phenol and carboxylic acid'], correct: 'A',
    explanation: 'The group –COOH (C=O with –OH on the same carbon) is a carboxylic acid. The group –O–C(=O)–CH_3 attached to the ring through oxygen is an ester (specifically an acetate ester); the free phenolic –OH of salicylic acid has been acetylated, so there is no phenol or ether.',
  },
  {
    topic: 'Structural Isomerism (Chain Diagram)', difficulty: 'Easy', svg: butaneAndIsobutane(),
    text: 'Compounds I and II shown here both have the molecular formula C_4H_10. What type of isomerism do they exhibit?',
    options: ['Chain isomerism', 'Position isomerism', 'Functional group isomerism', 'Geometrical isomerism'], correct: 'A',
    explanation: 'I is butane (a straight four-carbon chain) and II is 2-methylpropane (isobutane, a branched chain). They share a formula and functional group but differ in the arrangement of the carbon skeleton, which is chain (skeletal) isomerism.',
  },
  {
    topic: 'Geometrical (cis-trans) Isomerism (Diagram)', difficulty: 'Medium', svg: cisTransButene(),
    text: 'P and Q are the two stereoisomers of but-2-ene (CH_3–CH=CH–CH_3), shown as skeletal formulas. Which statement is correct?',
    options: ['P is the Z (cis) isomer and Q is the E (trans) isomer', 'P is the E (trans) isomer and Q is the Z (cis) isomer', 'Both P and Q are Z isomers', 'P and Q are structural isomers, not stereoisomers'], correct: 'A',
    explanation: 'On each double-bond carbon the substituent of higher priority is CH_3 (it outranks H). In P both CH_3 groups point to the same side of the C=C, so it is Z (cis). In Q they point to opposite sides, so it is E (trans). They have the same connectivity, so they are geometrical (cis-trans) stereoisomers.',
  },
  {
    topic: 'Aromaticity & Hückel Rule (Fused Ring Diagram)', difficulty: 'Hard', svg: naphthalene(),
    text: 'Naphthalene (C_10H_8) is shown with its fused benzene rings. Which statement about its π electrons and aromaticity is correct?',
    options: ['10 π electrons; aromatic (4n + 2 with n = 2)', '8 π electrons; anti-aromatic', '10 π electrons; non-aromatic', '12 π electrons; aromatic (4n + 2 with n = 3)'], correct: 'A',
    explanation: 'Naphthalene has 10 sp² carbons, each contributing one p electron to a planar, fully conjugated cyclic system: 5 double bonds × 2 = 10 π electrons. Hückel’s rule 4n + 2 is satisfied for n = 2, so the molecule is aromatic.',
  },
  {
    topic: 'Acidity of Phenol vs Alcohol (Diagram)', difficulty: 'Hard', svg: phenolAndEthanolClean(),
    text: 'Compound A (phenol) and compound B (ethanol) are shown. Which one is the stronger acid, and why?',
    options: [
      'A, because the phenoxide ion is stabilised by resonance with the benzene ring',
      'B, because the ethyl group donates electrons and stabilises the ethoxide ion',
      'A, because the O–H bond in phenol is longer than the O–H bond in ethanol',
      'Both are equally acidic because both contain an –OH group',
    ],
    correct: 'A',
    explanation: 'On losing H^+, phenol gives the phenoxide ion, whose negative charge is delocalised over the ring (ortho and para carbons) by resonance, which stabilises the conjugate base. In ethoxide the charge stays on oxygen and is destabilised by the +I effect of the ethyl group. Hence phenol (pK_a ≈ 10) is far more acidic than ethanol (pK_a ≈ 16).',
  },
  {
    topic: 'Degree of Unsaturation (Diagram)', difficulty: 'Hard', svg: cinnamaldehyde(),
    text: 'For the compound shown (C_9H_8O), what is the degree of unsaturation (double-bond equivalents, DBE), and how is it made up?',
    options: [
      '6 = 1 ring + 3 ring C=C + 1 C=C + 1 C=O',
      '5 = 3 ring C=C + 1 C=C + 1 C=O',
      '7 = 1 ring + 3 ring C=C + 2 C=C + 1 C=O',
      '4 = 3 ring C=C + 1 C=O',
    ],
    correct: 'A',
    explanation: 'DBE = (2C + 2 − H)/2 = (2×9 + 2 − 8)/2 = 6. Counting from the structure: 1 ring (the benzene ring itself) + 3 C=C in the Kekulé ring + 1 side-chain C=C + 1 C=O = 6, which matches the formula.',
  },
];

// ------------------------------------------------------------------ balance the answer key
// Hand-written keys cluster on one letter, so give each question a balanced, seeded target slot for
// its correct option and move the option there (reproducible: fixed seed).
{
  let seed = 20261028;
  const rnd = () => ((seed = (seed * 1664525 + 1013904223) >>> 0) / 4294967296);
  const targets = Q.map((_, i) => i % 4);
  for (let i = targets.length - 1; i > 0; i--) {
    const j = Math.floor(rnd() * (i + 1));
    [targets[i], targets[j]] = [targets[j], targets[i]];
  }
  Q.forEach((q, i) => {
    const right = 'ABCD'.indexOf(q.correct);
    const correctText = q.options[right];
    const rest = q.options.filter((_, k) => k !== right);
    rest.splice(targets[i], 0, correctText);
    q.options = rest;
    q.correct = 'ABCD'[targets[i]];
  });
}

// ------------------------------------------------------------------ SQL
const s = (v) => (v === null || v === undefined ? 'NULL' : "'" + String(v).replace(/\\/g, '\\\\').replace(/'/g, "''") + "'");
const out = [];
out.push('-- Generated by tools/build-organic.mjs. Re-importing replaces the organic paper and preset.');
out.push('USE cbt_portal;');
out.push('SET NAMES utf8mb4;');
out.push('');
out.push("DELETE FROM presets WHERE id = 'NEET_ORG';");
out.push("DELETE FROM exams WHERE slug = 'organic';");
out.push('');
out.push(
  "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('NEET_ORG', 'NEET Organic Chemistry', 'Medical Entrance / Chemistry', 45, 4, 1, 30, 6);"
);
const sections = [
  ['Hydrocarbons & Aromaticity', 'Benzene structure and resonance, Hückel rule, fused aromatics, alkane/alkene nomenclature, hybridisation, degree of unsaturation', 34],
  ['Functional Groups & Isomerism', 'Identifying functional groups from structures, chain/position/functional isomerism, cis-trans (E/Z) isomerism, IUPAC naming of skeletal formulas', 33],
  ['Reactions & Directive Effects', 'Electrophilic aromatic substitution, activating and deactivating groups, ortho/para vs meta directors, acidity of phenols, named reactions', 33],
];
sections.forEach(([n, t, w], i) =>
  out.push(`INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('NEET_ORG', ${i + 1}, ${s(n)}, ${s(t)}, ${w});`)
);
out.push('');
out.push(
  "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('organic', 'Organic Chemistry: Structures & Mechanisms', 'demo', 'ready', 20, 4, 1, 1);"
);
out.push('SET @org_exam = LAST_INSERT_ID();');
Q.forEach((q, i) => {
  out.push(
    `INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@org_exam, ${i + 1}, ${s(S)}, ${s(q.topic)}, ${s(q.difficulty)}, ${s(q.text)}, 1, ${s(q.svg)}, ${s(q.explanation)}, ${s(q.correct)});`
  );
  out.push('SET @q = LAST_INSERT_ID();');
  q.options.forEach((o, j) => out.push(`INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '${'ABCD'[j]}', ${s(o)});`));
});
writeFileSync(resolve(here, '../database/organic_chemistry.sql'), out.join('\n') + '\n');
console.log(`organic_chemistry.sql: ${Q.length} questions + NEET_ORG preset`);

const svgDir = process.argv.includes('--svg') ? process.argv[process.argv.indexOf('--svg') + 1] : null;
if (svgDir) {
  mkdirSync(svgDir, { recursive: true });
  Q.forEach((q, i) => writeFileSync(resolve(svgDir, `q${i + 1}.svg`), q.svg));
  console.log(`wrote ${Q.length} svgs to ${svgDir}`);
}
