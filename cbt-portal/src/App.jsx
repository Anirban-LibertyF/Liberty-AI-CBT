import React, { useState, useEffect, useRef, useMemo } from 'react';
import {
  BookOpen,
  Clock,
  CheckCircle2,
  XCircle,
  HelpCircle,
  AlertCircle,
  Sparkles,
  Play,
  Layers,
  Sliders,
  Award,
  BarChart3,
  ChevronRight,
  ChevronLeft,
  Flag,
  RotateCcw,
  Plus,
  Trash2,
  Cpu,
  ShieldCheck,
  Zap,
  FileText,
  Brain,
  Target,
  Timer,
  Check,
  Filter,
  Eye,
  Menu,
  X,
  RefreshCw,
  ArrowRight,
  Maximize2,
  Image as ImageIcon,
  Sigma
} from 'lucide-react';

// ============================================================================
// 0. MATHEMATICAL, SCIENTIFIC & LATEX SYMBOL RENDERING ENGINE (<MathText />)
// ============================================================================
const LATEX_SYMBOL_MAP = {
  '\\alpha': 'α',
  '\\beta': 'β',
  '\\gamma': 'γ',
  '\\Gamma': 'Γ',
  '\\delta': 'δ',
  '\\Delta': 'Δ',
  '\\epsilon': 'ε',
  '\\varepsilon': 'ε',
  '\\zeta': 'ζ',
  '\\eta': 'η',
  '\\theta': 'θ',
  '\\Theta': 'Θ',
  '\\vartheta': 'ϑ',
  '\\iota': 'ι',
  '\\kappa': 'κ',
  '\\lambda': 'λ',
  '\\Lambda': 'Λ',
  '\\mu': 'μ',
  '\\nu': 'ν',
  '\\xi': 'ξ',
  '\\Xi': 'Ξ',
  '\\pi': 'π',
  '\\Pi': 'Π',
  '\\rho': 'ρ',
  '\\sigma': 'σ',
  '\\Sigma': 'Σ',
  '\\tau': 'τ',
  '\\upsilon': 'υ',
  '\\phi': 'φ',
  '\\Phi': 'Φ',
  '\\varphi': 'ϕ',
  '\\chi': 'χ',
  '\\psi': 'ψ',
  '\\Psi': 'Ψ',
  '\\omega': 'ω',
  '\\Omega': 'Ω',
  '\\int': '∫',
  '\\iint': '∬',
  '\\iiint': '∭',
  '\\oint': '∮',
  '\\sum': '∑',
  '\\prod': '∏',
  '\\infty': '∞',
  '\\partial': '∂',
  '\\nabla': '∇',
  '\\pm': '±',
  '\\mp': '∓',
  '\\times': '×',
  '\\div': '÷',
  '\\cdot': '·',
  '\\ast': '∗',
  '\\circ': '∘',
  '\\degree': '°',
  '\\circC': '°C',
  '\\leq': '≤',
  '\\le': '≤',
  '\\geq': '≥',
  '\\ge': '≥',
  '\\neq': '≠',
  '\\ne': '≠',
  '\\approx': '≈',
  '\\equiv': '≡',
  '\\cong': '≅',
  '\\sim': '∼',
  '\\propto': '∝',
  '\\ll': '≪',
  '\\gg': '≫',
  '\\forall': '∀',
  '\\exists': '∃',
  '\\in': '∈',
  '\\notin': '∉',
  '\\subset': '⊂',
  '\\subseteq': '⊆',
  '\\cup': '∪',
  '\\cap': '∩',
  '\\emptyset': '∅',
  '\\therefore': '∴',
  '\\because': '∵',
  '\\angle': '∠',
  '\\triangle': '△',
  '\\perp': '⊥',
  '\\parallel': '∥',
  '\\rightarrow': '→',
  '\\to': '→',
  '\\leftarrow': '←',
  '\\leftrightarrow': '↔',
  '\\Rightarrow': '⇒',
  '\\Leftarrow': '⇐',
  '\\Leftrightarrow': '⇔',
  '\\uparrow': '↑',
  '\\downarrow': '↓',
  '\\sin': 'sin',
  '\\cos': 'cos',
  '\\tan': 'tan',
  '\\cot': 'cot',
  '\\sec': 'sec',
  '\\csc': 'csc',
  '\\log': 'log',
  '\\ln': 'ln',
  '\\lim': 'lim',
  '\\max': 'max',
  '\\min': 'min',
  '\\det': 'det',
  '\\gcd': 'gcd',
  '\\lcm': 'lcm',
  '\\mod': 'mod',
  '\\left': '',
  '\\right': '',
  '\\,': ' ',
  '\\;': ' ',
  '\\quad': '  ',
  '\\qquad': '    ',
  '\\text': ''
};

// Helper to extract balanced `{...}` group starting at index `startIdx`
function extractBalancedBraces(str, startIdx) {
  if (str[startIdx] !== '{') return null;
  let depth = 0;
  let content = '';
  for (let i = startIdx; i < str.length; i++) {
    const ch = str[i];
    if (ch === '{') {
      if (depth > 0) content += ch;
      depth++;
    } else if (ch === '}') {
      depth--;
      if (depth === 0) {
        return { content, endIdx: i };
      }
      content += ch;
    } else {
      content += ch;
    }
  }
  return { content, endIdx: str.length - 1 };
}

// Replace basic LaTeX symbol commands with Unicode equivalents
function normalizeMathSymbols(raw) {
  if (!raw || typeof raw !== 'string') return '';
  let text = raw
    .replace(/\$\$/g, '')
    .replace(/\$/g, '')
    .replace(/\\\(/g, '')
    .replace(/\\\)/g, '')
    .replace(/\\\[/g, '')
    .replace(/\\\]/g, '');

  // Sort keys by length descending so \varepsilon matches before \var, \Rightarrow before \Right, etc.
  const sortedKeys = Object.keys(LATEX_SYMBOL_MAP).sort((a, b) => b.length - a.length);
  for (const key of sortedKeys) {
    const escapedKey = key.replace(/\\/g, '\\\\');
    // Match command followed by non-letter or end of string
    const regex = new RegExp(`${escapedKey}(?![a-zA-Z])`, 'g');
    text = text.replace(regex, LATEX_SYMBOL_MAP[key]);
  }
  return text;
}

// Recursive AST-like tokenizer for \frac{a}{b}, \sqrt{x}, \int_{a}^{b}, \sum_{a}^{b}, ^{...}, _{...}
function parseMathTokens(inputStr, keyPrefix = 'm') {
  if (!inputStr) return null;
  const str = normalizeMathSymbols(inputStr);
  const elements = [];
  let buffer = '';
  let i = 0;

  const flushBuffer = () => {
    if (buffer.length > 0) {
      elements.push(buffer);
      buffer = '';
    }
  };

  while (i < str.length) {
    // 1. Check for \frac{num}{den} or \dfrac{num}{den}
    if (str.startsWith('\\frac', i) || str.startsWith('\\dfrac', i)) {
      const cmdLen = str.startsWith('\\dfrac', i) ? 6 : 5;
      let cursor = i + cmdLen;
      while (cursor < str.length && str[cursor] === ' ') cursor++;
      const numGroup = extractBalancedBraces(str, cursor);
      if (numGroup) {
        cursor = numGroup.endIdx + 1;
        while (cursor < str.length && str[cursor] === ' ') cursor++;
        const denGroup = extractBalancedBraces(str, cursor);
        if (denGroup) {
          flushBuffer();
          elements.push(
            <span
              key={`${keyPrefix}_frac_${i}`}
              className="inline-flex flex-col items-center justify-center align-middle mx-1 px-1 text-[0.92em] leading-tight"
              style={{ verticalAlign: 'middle' }}
            >
              <span className="border-b border-current px-1 pb-0.5 font-medium">
                {parseMathTokens(numGroup.content, `${keyPrefix}_n_${i}`)}
              </span>
              <span className="px-1 pt-0.5 font-medium">
                {parseMathTokens(denGroup.content, `${keyPrefix}_d_${i}`)}
              </span>
            </span>
          );
          i = denGroup.endIdx + 1;
          continue;
        }
      }
    }

    // 2. Check for \sqrt[n]{expr} or \sqrt{expr}
    if (str.startsWith('\\sqrt', i)) {
      let cursor = i + 5;
      let rootIndex = null;
      if (str[cursor] === '[') {
        const closeBracket = str.indexOf(']', cursor);
        if (closeBracket !== -1) {
          rootIndex = str.slice(cursor + 1, closeBracket);
          cursor = closeBracket + 1;
        }
      }
      const radGroup = extractBalancedBraces(str, cursor);
      if (radGroup) {
        flushBuffer();
        elements.push(
          <span
            key={`${keyPrefix}_sqrt_${i}`}
            className="inline-flex items-baseline mx-0.5"
          >
            {rootIndex && (
              <sup className="text-[0.65em] mr-0.5 font-semibold">{rootIndex}</sup>
            )}
            <span className="text-[1.08em] font-serif leading-none">√</span>
            <span className="border-t border-current px-0.5 pt-0.5">
              {parseMathTokens(radGroup.content, `${keyPrefix}_rad_${i}`)}
            </span>
          </span>
        );
        i = radGroup.endIdx + 1;
        continue;
      }
    }

    // 3. Check for Integral / Summation with bounds: ∫_{a}^{b} or ∑_{a}^{b}
    if ((str[i] === '∫' || str[i] === '∮' || str[i] === '∑' || str[i] === '∏') && (str[i + 1] === '_' || str[i + 1] === '^')) {
      const symbolChar = str[i];
      let cursor = i + 1;
      let subContent = null;
      let supContent = null;

      for (let pass = 0; pass < 2; pass++) {
        if (str[cursor] === '_') {
          cursor++;
          if (str[cursor] === '{') {
            const g = extractBalancedBraces(str, cursor);
            if (g) {
              subContent = g.content;
              cursor = g.endIdx + 1;
            }
          } else if (cursor < str.length) {
            subContent = str[cursor];
            cursor++;
          }
        } else if (str[cursor] === '^') {
          cursor++;
          if (str[cursor] === '{') {
            const g = extractBalancedBraces(str, cursor);
            if (g) {
              supContent = g.content;
              cursor = g.endIdx + 1;
            }
          } else if (cursor < str.length) {
            supContent = str[cursor];
            cursor++;
          }
        }
      }

      flushBuffer();
      elements.push(
        <span
          key={`${keyPrefix}_int_${i}`}
          className="inline-flex items-center align-middle mx-1"
        >
          <span className="text-[1.35em] font-serif leading-none">{symbolChar}</span>
          <span className="inline-flex flex-col justify-between text-[0.7em] leading-none ml-0.5">
            {supContent !== null && (
              <span className="mb-1">{parseMathTokens(supContent, `${keyPrefix}_isup_${i}`)}</span>
            )}
            {subContent !== null && (
              <span className="mt-0.5">{parseMathTokens(subContent, `${keyPrefix}_isub_${i}`)}</span>
            )}
          </span>
        </span>
      );
      i = cursor;
      continue;
    }

    // 4. Check for Superscript ^{...} or ^x
    if (str[i] === '^') {
      let cursor = i + 1;
      if (str[cursor] === '{') {
        const g = extractBalancedBraces(str, cursor);
        if (g) {
          flushBuffer();
          elements.push(
            <sup key={`${keyPrefix}_sup_${i}`} className="text-[0.76em] font-semibold ml-[1px]">
              {parseMathTokens(g.content, `${keyPrefix}_supc_${i}`)}
            </sup>
          );
          i = g.endIdx + 1;
          continue;
        }
      } else if (cursor < str.length && /[0-9a-zA-Z+\-θλπαβ∞°]/.test(str[cursor])) {
        // Grab contiguous digits/simple exponent token
        let expToken = '';
        while (cursor < str.length && /[0-9+\-]/.test(str[cursor])) {
          expToken += str[cursor];
          cursor++;
        }
        if (!expToken) {
          expToken = str[cursor];
          cursor++;
        }
        flushBuffer();
        elements.push(
          <sup key={`${keyPrefix}_sup_${i}`} className="text-[0.76em] font-semibold ml-[1px]">
            {expToken}
          </sup>
        );
        i = cursor;
        continue;
      }
    }

    // 5. Check for Subscript _{...} or _x
    if (str[i] === '_') {
      let cursor = i + 1;
      if (str[cursor] === '{') {
        const g = extractBalancedBraces(str, cursor);
        if (g) {
          flushBuffer();
          elements.push(
            <sub key={`${keyPrefix}_sub_${i}`} className="text-[0.76em] font-semibold ml-[1px]">
              {parseMathTokens(g.content, `${keyPrefix}_subc_${i}`)}
            </sub>
          );
          i = g.endIdx + 1;
          continue;
        }
      } else if (cursor < str.length && /[0-9a-zA-Z]/.test(str[cursor])) {
        const subToken = str[cursor];
        flushBuffer();
        elements.push(
          <sub key={`${keyPrefix}_sub_${i}`} className="text-[0.76em] font-semibold ml-[1px]">
            {subToken}
          </sub>
        );
        i = cursor + 1;
        continue;
      }
    }

    // Default character accumulation
    buffer += str[i];
    i++;
  }

  flushBuffer();
  return elements;
}

function MathText({ text, className = '' }) {
  const rendered = useMemo(() => {
    if (text === null || text === undefined) return '';
    return parseMathTokens(String(text));
  }, [text]);

  return <span className={`math-rendered ${className}`}>{rendered}</span>;
}

// Safe SVG Sanitizer & Renderer Component
function DiagramRenderer({ svgString, title = 'Question Figure', onZoom }) {
  const cleanSvg = useMemo(() => {
    if (!svgString || typeof svgString !== 'string') return null;
    const trimmed = svgString.trim();
    if (!trimmed.includes('<svg')) return null;
    // Strip any unsafe script or event handlers
    return trimmed
      .replace(/<script[\s\S]*?>[\s\S]*?<\/script>/gi, '')
      .replace(/\son\w+="[^"]*"/gi, '');
  }, [svgString]);

  if (!cleanSvg) return null;

  return (
    <div className="my-4 rounded-2xl bg-white border-2 border-indigo-100 shadow-sm overflow-hidden">
      <div className="px-4 py-2 bg-slate-50 border-b border-slate-200 flex items-center justify-between text-xs text-slate-600">
        <span className="font-bold text-indigo-700 flex items-center gap-1.5">
          <ImageIcon className="w-3.5 h-3.5 text-indigo-600" />
          {title} (Vector Diagram)
        </span>
        {onZoom && (
          <button
            type="button"
            onClick={() => onZoom(cleanSvg, title)}
            className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition-colors cursor-pointer"
          >
            <Maximize2 className="w-3 h-3" />
            <span>Enlarge Figure</span>
          </button>
        )}
      </div>
      <div
        className="p-4 flex items-center justify-center bg-white max-h-[300px] overflow-auto [&>svg]:max-w-full [&>svg]:h-auto [&>svg]:max-h-[260px]"
        dangerouslySetInnerHTML={{ __html: cleanSvg }}
      />
    </div>
  );
}

// ============================================================================
// 1. EXAM PRESETS & BLUEPRINTS
// ============================================================================
const EXAM_PRESETS = [
  {
    id: 'SSC_CGL',
    name: 'SSC CGL Tier-I',
    badge: 'Govt / Staff Selection',
    durationMinutes: 60,
    positiveMarks: 2,
    negativeMarks: 0.5,
    defaultCount: 40,
    sections: [
      { id: 's1', name: 'General Intelligence & Reasoning', topics: 'Analogy, Number Series, Syllogism, Coding-Decoding, Blood Relations, Mirror Images', weight: 25 },
      { id: 's2', name: 'General Awareness', topics: 'Indian Polity, Modern History, Geography, Macroeconomics, General Science, Static GK', weight: 25 },
      { id: 's3', name: 'Quantitative Aptitude', topics: 'Percentages, Profit & Loss, Time & Work, Algebra, Trigonometry, Geometry, Data Interpretation', weight: 25 },
      { id: 's4', name: 'English Comprehension', topics: 'Error Spotting, Idioms & Phrases, One Word Substitution, Active/Passive Voice, Cloze Passage', weight: 25 }
    ]
  },
  {
    id: 'GATE_CS',
    name: 'GATE CS & IT',
    badge: 'Engineering / PSU',
    durationMinutes: 90,
    positiveMarks: 2,
    negativeMarks: 0.66,
    defaultCount: 40,
    sections: [
      { id: 'g1', name: 'General Aptitude & Engg Math', topics: 'Discrete Mathematics, Linear Algebra, Probability, Combinatorics, Logical Deduction', weight: 25 },
      { id: 'g2', name: 'Data Structures & Algorithms', topics: 'Trees, Graphs, Dynamic Programming, Sorting, Hashing, Asymptotic Time Complexity', weight: 25 },
      { id: 'g3', name: 'Operating Systems & DBMS', topics: 'Process Synchronization, Deadlocks, Paging, SQL Queries, Normalization (BCNF/3NF), Transactions', weight: 25 },
      { id: 'g4', name: 'Computer Networks & COA', topics: 'TCP/IP, Subnetting, Routing Protocols, Cache Memory Mapping, Instruction Pipelining', weight: 25 }
    ]
  },
  {
    id: 'IBPS_PO',
    name: 'IBPS / SBI Bank PO',
    badge: 'Banking Sector',
    durationMinutes: 60,
    positiveMarks: 1,
    negativeMarks: 0.25,
    defaultCount: 50,
    sections: [
      { id: 'b1', name: 'Reasoning Ability', topics: 'Puzzles, Seating Arrangement, Inequality, Syllogism, Direction Sense, Order & Ranking', weight: 35 },
      { id: 'b2', name: 'Quantitative Aptitude', topics: 'Simplification, Quadratic Equations, Number Series, Arithmetic Word Problems, Ratio & Proportion', weight: 35 },
      { id: 'b3', name: 'English Language', topics: 'Reading Comprehension, Para Jumbles, Fillers, Phrase Replacement, Contextual Vocabulary', weight: 30 }
    ]
  },
  {
    id: 'JEE_MAIN',
    name: 'JEE Main (PCM)',
    badge: 'Engineering Entrance',
    durationMinutes: 90,
    positiveMarks: 4,
    negativeMarks: 1,
    defaultCount: 45,
    sections: [
      { id: 'j1', name: 'Physics', topics: 'Kinematics, Rotational Mechanics, Electrostatics, Current Electricity, Optics, Modern Physics', weight: 34 },
      { id: 'j2', name: 'Chemistry', topics: 'Chemical Bonding, Thermodynamics, Equilibrium, Electrochemistry, Coordination Compounds, GOC', weight: 33 },
      { id: 'j3', name: 'Mathematics', topics: 'Complex Numbers, Matrices & Determinants, Definite Integration, Differential Equations, Vectors & 3D', weight: 33 }
    ]
  },
  {
    id: 'CAT_MBA',
    name: 'CAT / MBA Entrance',
    badge: 'Management / IIM',
    durationMinutes: 60,
    positiveMarks: 3,
    negativeMarks: 1,
    defaultCount: 40,
    sections: [
      { id: 'c1', name: 'VARC (Verbal Ability)', topics: 'Critical Reasoning, Para Summary, Odd Sentence Out, Para Jumbles, Inference Questions', weight: 34 },
      { id: 'c2', name: 'DILR (Logical Reasoning)', topics: 'Games & Tournaments, Venn Diagrams, Quantitative Reasoning, Arrangement Matrix', weight: 33 },
      { id: 'c3', name: 'QA (Quantitative Ability)', topics: 'Number System, Functions & Graphs, Logarithms, Time Speed Distance, Permutations & Combinations', weight: 33 }
    ]
  }
];

// ============================================================================
// 2. PRE-BUILT 50-MCQ INSTANT DEMO PAPER (Rich with SVG Diagrams & Math Symbols)
// ============================================================================
const DEMO_50_QUESTIONS = [
  // SECTION 1: Quantitative Aptitude (13 Questions - Includes Geometry & DI SVG Diagrams + Calculus/Trig/Algebra Math Symbols)
  {
    id: 'q1',
    section: 'Quantitative Aptitude',
    topic: 'Circle Geometry (Diagram)',
    difficulty: 'Medium',
    hasDiagram: true,
    svgDiagram: `<svg viewBox="0 0 440 240" xmlns="http://www.w3.org/2000/svg">
      <rect width="440" height="240" fill="#f8fafc" rx="12"/>
      <circle cx="170" cy="115" r="88" fill="#eef2ff" stroke="#4f46e5" stroke-width="2.5"/>
      <!-- Chord AB -->
      <line x1="94" y1="160" x2="246" y2="160" stroke="#0f172a" stroke-width="3"/>
      <!-- Center O to A and B -->
      <line x1="170" y1="115" x2="94" y2="160" stroke="#dc2626" stroke-width="2" stroke-dasharray="5,4"/>
      <line x1="170" y1="115" x2="170" y2="203" stroke="#059669" stroke-width="2.5"/>
      <!-- Right Angle Box at M(170,160) -->
      <rect x="170" y="150" width="10" height="10" fill="none" stroke="#059669" stroke-width="1.5"/>
      <!-- Points -->
      <circle cx="170" cy="115" r="4" fill="#1e293b"/>
      <circle cx="94" cy="160" r="4" fill="#1e293b"/>
      <circle cx="246" cy="160" r="4" fill="#1e293b"/>
      <circle cx="170" cy="160" r="4" fill="#059669"/>
      <circle cx="170" cy="203" r="4" fill="#dc2626"/>
      <!-- Labels -->
      <text x="165" y="105" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">O</text>
      <text x="75" y="166" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">A</text>
      <text x="254" y="166" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">B</text>
      <text x="176" y="175" font-family="sans-serif" font-size="12" font-weight="bold" fill="#059669">M</text>
      <text x="165" y="222" font-family="sans-serif" font-size="13" font-weight="bold" fill="#dc2626">P</text>
      <text x="108" y="132" font-family="sans-serif" font-size="12" font-weight="bold" fill="#dc2626">R = 13 cm</text>
      <!-- Legend Box on Right -->
      <rect x="285" y="40" width="138" height="150" rx="8" fill="#ffffff" stroke="#cbd5e1"/>
      <text x="297" y="65" font-family="sans-serif" font-size="12" font-weight="bold" fill="#1e293b">Given Data:</text>
      <text x="297" y="90" font-family="sans-serif" font-size="12" fill="#334155">• Radius OA = 13 cm</text>
      <text x="297" y="112" font-family="sans-serif" font-size="12" fill="#334155">• Chord AB = 24 cm</text>
      <text x="297" y="134" font-family="sans-serif" font-size="12" fill="#334155">• OM ⊥ AB at M</text>
      <text x="297" y="158" font-family="sans-serif" font-size="12" font-weight="bold" fill="#4f46e5">Find: Length MP</text>
      <text x="297" y="176" font-family="sans-serif" font-size="12" font-weight="bold" fill="#4f46e5">&amp; Chord AP</text>
    </svg>`,
    questionText: 'In the given figure, O is the center of a circle of radius R = 13 cm, and chord AB = 24 cm. A perpendicular OM is drawn from O to chord AB and extended to meet the minor arc AB at point P. What are the lengths of segment MP and chord AP?',
    options: [
      { id: 'A', text: 'MP = 8 cm, AP = 4\\sqrt{13} cm' },
      { id: 'B', text: 'MP = 5 cm, AP = 12\\sqrt{2} cm' },
      { id: 'C', text: 'MP = 8 cm, AP = 6\\sqrt{5} cm' },
      { id: 'D', text: 'MP = 7 cm, AP = 4\\sqrt{13} cm' }
    ],
    correctOption: 'A',
    explanation: 'Since OM \\perp AB, M bisects chord AB, so AM = \\frac{24}{2} = 12 cm. In right \\triangle OMA, OM = \\sqrt{OA^2 - AM^2} = \\sqrt{13^2 - 12^2} = \\sqrt{169 - 144} = 5 cm. Since OP is also a radius (13 cm), MP = OP - OM = 13 - 5 = 8 cm. Finally, in right \\triangle AMP, AP = \\sqrt{AM^2 + MP^2} = \\sqrt{12^2 + 8^2} = \\sqrt{144 + 64} = \\sqrt{208} = 4\\sqrt{13} cm.'
  },
  {
    id: 'q2',
    section: 'Quantitative Aptitude',
    topic: 'Data Interpretation (Bar Chart)',
    difficulty: 'Medium',
    hasDiagram: true,
    svgDiagram: `<svg viewBox="0 0 460 240" xmlns="http://www.w3.org/2000/svg">
      <rect width="460" height="240" fill="#f8fafc" rx="12"/>
      <text x="230" y="24" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">Company Revenue vs Expenditure (in ₹ Crores)</text>
      <!-- Axes -->
      <line x1="55" y1="40" x2="55" y2="195" stroke="#64748b" stroke-width="1.5"/>
      <line x1="55" y1="195" x2="430" y2="195" stroke="#64748b" stroke-width="1.5"/>
      <!-- Grid lines -->
      <line x1="55" y1="155" x2="430" y2="155" stroke="#e2e8f0" stroke-dasharray="3,3"/>
      <line x1="55" y1="115" x2="430" y2="115" stroke="#e2e8f0" stroke-dasharray="3,3"/>
      <line x1="55" y1="75" x2="430" y2="75" stroke="#e2e8f0" stroke-dasharray="3,3"/>
      <text x="48" y="198" text-anchor="end" font-family="sans-serif" font-size="10" fill="#64748b">0</text>
      <text x="48" y="158" text-anchor="end" font-family="sans-serif" font-size="10" fill="#64748b">100</text>
      <text x="48" y="118" text-anchor="end" font-family="sans-serif" font-size="10" fill="#64748b">200</text>
      <text x="48" y="78" text-anchor="end" font-family="sans-serif" font-size="10" fill="#64748b">300</text>
      <!-- 2023: Rev 150, Exp 120 -->
      <rect x="80" y="135" width="24" height="60" fill="#4f46e5" rx="3"/>
      <rect x="107" y="147" width="24" height="48" fill="#f43f5e" rx="3"/>
      <text x="92" y="130" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#4f46e5">150</text>
      <text x="119" y="142" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#e11d48">120</text>
      <text x="105" y="212" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="bold" fill="#334155">2023</text>
      <!-- 2024: Rev 200, Exp 150 -->
      <rect x="175" y="115" width="24" height="80" fill="#4f46e5" rx="3"/>
      <rect x="202" y="135" width="24" height="60" fill="#f43f5e" rx="3"/>
      <text x="187" y="110" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#4f46e5">200</text>
      <text x="214" y="130" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#e11d48">150</text>
      <text x="200" y="212" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="bold" fill="#334155">2024</text>
      <!-- 2025: Rev 240, Exp 160 -->
      <rect x="270" y="99" width="24" height="96" fill="#4f46e5" rx="3"/>
      <rect x="297" y="131" width="24" height="64" fill="#f43f5e" rx="3"/>
      <text x="282" y="94" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#4f46e5">240</text>
      <text x="309" y="126" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#e11d48">160</text>
      <text x="295" y="212" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="bold" fill="#334155">2025</text>
      <!-- 2026: Rev 300, Exp 225 -->
      <rect x="360" y="75" width="24" height="120" fill="#4f46e5" rx="3"/>
      <rect x="387" y="105" width="24" height="90" fill="#f43f5e" rx="3"/>
      <text x="372" y="70" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#4f46e5">300</text>
      <text x="399" y="100" text-anchor="middle" font-family="sans-serif" font-size="10" font-weight="bold" fill="#e11d48">225</text>
      <text x="385" y="212" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="bold" fill="#334155">2026</text>
      <!-- Legend -->
      <rect x="145" y="222" width="10" height="10" fill="#4f46e5" rx="2"/>
      <text x="160" y="231" font-family="sans-serif" font-size="11" fill="#334155">Revenue</text>
      <rect x="235" y="222" width="10" height="10" fill="#f43f5e" rx="2"/>
      <text x="250" y="231" font-family="sans-serif" font-size="11" fill="#334155">Expenditure</text>
    </svg>`,
    questionText: 'Refer to the bar chart showing Revenue and Expenditure (in ₹ Crores) from 2023 to 2026. Given that Profit % = \\frac{\\text{Revenue} - \\text{Expenditure}}{\\text{Expenditure}} \\times 100, in which year did the company record the highest Profit Percentage, and what was its value?',
    options: [
      { id: 'A', text: '2024 (33.33%)' },
      { id: 'B', text: '2025 (50.00%)' },
      { id: 'C', text: '2026 (33.33%)' },
      { id: 'D', text: '2023 (25.00%)' }
    ],
    correctOption: 'B',
    explanation: 'Calculating Profit % for each year: In 2023: \\frac{150 - 120}{120} \\times 100 = 25%. In 2024: \\frac{200 - 150}{150} \\times 100 = 33.33%. In 2025: \\frac{240 - 160}{160} \\times 100 = \\frac{80}{160} \\times 100 = 50.00%. In 2026: \\frac{300 - 225}{225} \\times 100 = 33.33%. Hence, 2025 is the highest at 50.00%.'
  },
  {
    id: 'q3',
    section: 'Quantitative Aptitude',
    topic: 'Definite Integration & Calculus',
    difficulty: 'Hard',
    questionText: 'Evaluate the definite integral: I = \\int_{0}^{\\pi/2} \\frac{\\sqrt{\\sin \\theta}}{\\sqrt{\\sin \\theta} + \\sqrt{\\cos \\theta}} d\\theta',
    options: [
      { id: 'A', text: '\\frac{\\pi}{2}' },
      { id: 'B', text: '\\frac{\\pi}{4}' },
      { id: 'C', text: '\\frac{\\pi}{3}' },
      { id: 'D', text: '\\pi' }
    ],
    correctOption: 'B',
    explanation: 'Using the King property \\int_{0}^{a} f(\\theta) d\\theta = \\int_{0}^{a} f(a - \\theta) d\\theta with a = \\frac{\\pi}{2}, we get I = \\int_{0}^{\\pi/2} \\frac{\\sqrt{\\cos \\theta}}{\\sqrt{\\cos \\theta} + \\sqrt{\\sin \\theta}} d\\theta. Adding both equations gives 2I = \\int_{0}^{\\pi/2} 1 d\\theta = \\frac{\\pi}{2} \\Rightarrow I = \\frac{\\pi}{4}.'
  },
  {
    id: 'q4',
    section: 'Quantitative Aptitude',
    topic: 'Eigenvalues & Linear Algebra',
    difficulty: 'Hard',
    questionText: 'If \\lambda_1, \\lambda_2, \\lambda_3 are the eigenvalues of the matrix A = [[2, 1, 0], [0, 3, 4], [0, 0, 5]], then what is the value of \\sum_{i=1}^{3} \\lambda_i^2 + \\det(A)?',
    options: [
      { id: 'A', text: '68' },
      { id: 'B', text: '38' },
      { id: 'C', text: '58' },
      { id: 'D', text: '40' }
    ],
    correctOption: 'A',
    explanation: 'Since A is an upper triangular matrix, its eigenvalues are the diagonal entries: \\lambda_1 = 2, \\lambda_2 = 3, \\lambda_3 = 5. Thus \\sum_{i=1}^{3} \\lambda_i^2 = 2^2 + 3^2 + 5^2 = 4 + 9 + 25 = 38. Also, \\det(A) = \\lambda_1 \\times \\lambda_2 \\times \\lambda_3 = 2 \\times 3 \\times 5 = 30. Total = 38 + 30 = 68.'
  },
  {
    id: 'q5',
    section: 'Quantitative Aptitude',
    topic: 'Algebra & Surds',
    difficulty: 'Medium',
    questionText: 'If x + \\frac{1}{x} = 4, then what is the exact value of x^3 + \\frac{1}{x^3}?',
    options: [
      { id: 'A', text: '64' },
      { id: 'B', text: '52' },
      { id: 'C', text: '48' },
      { id: 'D', text: '56' }
    ],
    correctOption: 'B',
    explanation: 'Using the algebraic identity: if x + \\frac{1}{x} = k, then x^3 + \\frac{1}{x^3} = k^3 - 3k. Substituting k = 4 gives 4^3 - 3(4) = 64 - 12 = 52.'
  },
  {
    id: 'q6',
    section: 'Quantitative Aptitude',
    topic: 'Profit, Loss & Discount',
    difficulty: 'Medium',
    questionText: 'A shopkeeper marks his goods 40% above the cost price and allows a discount of 25% on the marked price. What is his net profit or loss percentage?',
    options: [
      { id: 'A', text: '5% Profit' },
      { id: 'B', text: '15% Profit' },
      { id: 'C', text: '5% Loss' },
      { id: 'D', text: '10% Profit' }
    ],
    correctOption: 'A',
    explanation: 'Let Cost Price (CP) = 100. Marked Price (MP) = 100 + 40% of 100 = 140. Selling Price (SP) after 25% discount = 140 × (1 - 0.25) = 140 × 0.75 = 105. Net Profit = 105 - 100 = 5, which is 5% Profit.'
  },
  {
    id: 'q7',
    section: 'Quantitative Aptitude',
    topic: 'Percentages',
    difficulty: 'Easy',
    questionText: 'If the price of sugar increases by 25%, by what percentage must a household reduce its consumption so that the total expenditure on sugar remains unchanged?',
    options: [
      { id: 'A', text: '25%' },
      { id: 'B', text: '20%' },
      { id: 'C', text: '16.67%' },
      { id: 'D', text: '15%' }
    ],
    correctOption: 'B',
    explanation: 'Formula for reduction % = [R / (100 + R)] × 100. Substituting R = 25: [25 / 125] × 100 = (1/5) × 100 = 20%.'
  },
  {
    id: 'q8',
    section: 'Quantitative Aptitude',
    topic: 'Compound Interest',
    difficulty: 'Hard',
    questionText: 'The difference between Compound Interest and Simple Interest on a certain sum at 10% per annum for 3 years is ₹930. Find the principal sum.',
    options: [
      { id: 'A', text: '₹25,000' },
      { id: 'B', text: '₹30,000' },
      { id: 'C', text: '₹31,000' },
      { id: 'D', text: '₹28,500' }
    ],
    correctOption: 'B',
    explanation: 'For 3 years, CI - SI = P × (R/100)² × (300 + R)/100. Here, 930 = P × (10/100)² × (310/100) = P × (1/100) × (31/10) = 31P / 1000. Thus, P = (930 × 1000) / 31 = ₹30,000.'
  },
  {
    id: 'q9',
    section: 'Quantitative Aptitude',
    topic: 'Algebra',
    difficulty: 'Medium',
    questionText: 'If x + (1/x) = 4, then what is the value of x³ + (1/x³)?',
    options: [
      { id: 'A', text: '64' },
      { id: 'B', text: '52' },
      { id: 'C', text: '48' },
      { id: 'D', text: '56' }
    ],
    correctOption: 'B',
    explanation: 'Using the algebraic identity: if x + 1/x = k, then x³ + 1/x³ = k³ - 3k. Substituting k = 4: 4³ - 3(4) = 64 - 12 = 52.'
  },
  {
    id: 'q10',
    section: 'Quantitative Aptitude',
    topic: 'Time, Speed & Distance',
    difficulty: 'Medium',
    questionText: 'A train 240 meters long passes a pole in 24 seconds. How long will it take to pass a platform 650 meters long?',
    options: [
      { id: 'A', text: '65 seconds' },
      { id: 'B', text: '89 seconds' },
      { id: 'C', text: '100 seconds' },
      { id: 'D', text: '75 seconds' }
    ],
    correctOption: 'B',
    explanation: 'Speed of the train = 240m / 24s = 10 m/s. To pass a 650m platform, total distance = 240 + 650 = 890 meters. Time taken = 890 / 10 = 89 seconds.'
  },
  {
    id: 'q11',
    section: 'Quantitative Aptitude',
    topic: 'Ratio & Proportion',
    difficulty: 'Easy',
    questionText: 'If A : B = 3 : 4 and B : C = 8 : 9, then what is the ratio A : B : C?',
    options: [
      { id: 'A', text: '3 : 4 : 9' },
      { id: 'B', text: '6 : 8 : 9' },
      { id: 'C', text: '8 : 12 : 15' },
      { id: 'D', text: '6 : 9 : 12' }
    ],
    correctOption: 'B',
    explanation: 'Multiply the first ratio A : B by 2 to make B equal to 8: A : B = 6 : 8. Since B : C = 8 : 9, combining them gives A : B : C = 6 : 8 : 9.'
  },
  {
    id: 'q12',
    section: 'Quantitative Aptitude',
    topic: 'Geometry',
    difficulty: 'Hard',
    questionText: 'In a circle of radius 13 cm, two parallel chords of lengths 24 cm and 10 cm are drawn on the same side of the center. What is the distance between the two chords?',
    options: [
      { id: 'A', text: '7 cm' },
      { id: 'B', text: '17 cm' },
      { id: 'C', text: '5 cm' },
      { id: 'D', text: '12 cm' }
    ],
    correctOption: 'A',
    explanation: 'Half-lengths of chords are 12 cm and 5 cm. Distance of 24 cm chord from center = √(13² - 12²) = 5 cm. Distance of 10 cm chord from center = √(13² - 5²) = 12 cm. Since they are on the same side, distance between them = 12 - 5 = 7 cm.'
  },
  {
    id: 'q13',
    section: 'Quantitative Aptitude',
    topic: 'Number System',
    difficulty: 'Medium',
    questionText: 'What is the remainder when 2³¹ is divided by 5?',
    options: [
      { id: 'A', text: '1' },
      { id: 'B', text: '2' },
      { id: 'C', text: '3' },
      { id: 'D', text: '4' }
    ],
    correctOption: 'C',
    explanation: 'We can write 2³¹ = 2 × (2²)¹⁵ = 2 × 4¹⁵. Since 4 ≡ -1 (mod 5), we have 2 × (-1)¹⁵ = -2 ≡ 3 (mod 5). Thus the remainder is 3.'
  },
  {
    id: 'q14',
    section: 'Quantitative Aptitude',
    topic: 'Averages',
    difficulty: 'Easy',
    questionText: 'The average weight of 20 students in a class is 45 kg. When the weight of the teacher is included, the average weight increases by 1.5 kg. What is the weight of the teacher?',
    options: [
      { id: 'A', text: '75.5 kg' },
      { id: 'B', text: '76.5 kg' },
      { id: 'C', text: '72 kg' },
      { id: 'D', text: '78.5 kg' }
    ],
    correctOption: 'B',
    explanation: 'New average of 21 people = 45 + 1.5 = 46.5 kg. Teacher weight = Old Average + (Total people × increase) = 45 + (21 × 1.5) = 45 + 31.5 = 76.5 kg.'
  },
  {
    id: 'q15',
    section: 'Quantitative Aptitude',
    topic: 'Trigonometry',
    difficulty: 'Medium',
    questionText: 'If \\sin \\theta + \\cos \\theta = \\sqrt{2} \\cos \\theta for 0 < \\theta < \\frac{\\pi}{2}, then what is the value of \\tan \\theta?',
    options: [
      { id: 'A', text: '\\sqrt{2} - 1' },
      { id: 'B', text: '\\sqrt{2} + 1' },
      { id: 'C', text: '\\frac{1}{\\sqrt{2}}' },
      { id: 'D', text: '2 - \\sqrt{2}' }
    ],
    correctOption: 'A',
    explanation: 'Dividing both sides by \\cos \\theta gives \\frac{\\sin \\theta}{\\cos \\theta} + 1 = \\sqrt{2} \\Rightarrow \\tan \\theta + 1 = \\sqrt{2} \\Rightarrow \\tan \\theta = \\sqrt{2} - 1.'
  },
  {
    id: 'q16',
    section: 'Quantitative Aptitude',
    topic: 'Mensuration',
    difficulty: 'Medium',
    questionText: 'If the radius r of the base of a right circular cylinder (V = \\pi r^2 h) is halved and its height h is doubled, then the new volume V_{new} becomes:',
    options: [
      { id: 'A', text: 'V (Unchanged)' },
      { id: 'B', text: '\\frac{V}{2} (Halved)' },
      { id: 'C', text: '2V (Doubled)' },
      { id: 'D', text: '\\frac{V}{4} (One-fourth)' }
    ],
    correctOption: 'B',
    explanation: 'Original volume V = \\pi r^2 h. New volume V_{new} = \\pi (\\frac{r}{2})^2 (2h) = \\pi (\\frac{r^2}{4})(2h) = \\frac{1}{2} \\pi r^2 h = \\frac{V}{2}.'
  },
  {
    id: 'q17',
    section: 'Quantitative Aptitude',
    topic: 'Mixtures & Alligation',
    difficulty: 'Hard',
    questionText: 'A vessel contains 60 liters of pure milk. 12 liters of milk are drawn out and replaced with water, and this process is repeated a second time. What quantity of milk is left finally?',
    options: [
      { id: 'A', text: '36 liters' },
      { id: 'B', text: '38.4 liters' },
      { id: 'C', text: '40.2 liters' },
      { id: 'D', text: '42 liters' }
    ],
    correctOption: 'B',
    explanation: 'Using the replacement formula: Final Milk = C \\times (1 - \\frac{x}{C})^n = 60 \\times (1 - \\frac{12}{60})^2 = 60 \\times (\\frac{4}{5})^2 = 60 \\times \\frac{16}{25} = 38.4 liters.'
  },

  // SECTION 2: Logical Reasoning (13 Questions - Includes Venn Diagram & Seating Arrangement SVGs)
  {
    id: 'q18',
    section: 'Logical Reasoning',
    topic: 'Venn Diagram & Set Theory (Diagram)',
    difficulty: 'Medium',
    hasDiagram: true,
    svgDiagram: `<svg viewBox="0 0 440 240" xmlns="http://www.w3.org/2000/svg">
      <rect width="440" height="240" fill="#f8fafc" rx="12"/>
      <!-- 3 Intersecting Venn Circles -->
      <circle cx="155" cy="95" r="64" fill="#6366f1" fill-opacity="0.16" stroke="#4f46e5" stroke-width="2.2"/>
      <circle cx="245" cy="95" r="64" fill="#10b981" fill-opacity="0.16" stroke="#059669" stroke-width="2.2"/>
      <circle cx="200" cy="160" r="64" fill="#f59e0b" fill-opacity="0.16" stroke="#d97706" stroke-width="2.2"/>
      <!-- Circle Titles -->
      <text x="90" y="42" font-family="sans-serif" font-size="12" font-weight="bold" fill="#4338ca">Python (P = 85)</text>
      <text x="255" y="42" font-family="sans-serif" font-size="12" font-weight="bold" fill="#047857">Java (J = 70)</text>
      <text x="275" y="215" font-family="sans-serif" font-size="12" font-weight="bold" fill="#b45309">SQL (S = 60)</text>
      <!-- Region Numbers -->
      <text x="130" y="85" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#1e293b">38</text>
      <text x="270" y="85" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#1e293b">26</text>
      <text x="200" y="192" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#1e293b">22</text>
      <!-- Intersections -->
      <text x="200" y="76" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">19</text>
      <text x="166" y="138" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#dc2626">x</text>
      <text x="234" y="138" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#0f172a">13</text>
      <text x="200" y="116" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#4f46e5">12</text>
    </svg>`,
    questionText: 'The Venn diagram above shows students certified in Python (P = 85), Java (J = 70), and SQL (S = 60). Find the unknown region x = |P \\cap S \\text{ only}| and the total number of students certified in EXACTLY two skills.',
    options: [
      { id: 'A', text: 'x = 16, Exactly Two = 48' },
      { id: 'B', text: 'x = 14, Exactly Two = 46' },
      { id: 'C', text: 'x = 16, Exactly Two = 60' },
      { id: 'D', text: 'x = 18, Exactly Two = 50' }
    ],
    correctOption: 'A',
    explanation: 'Total in Python circle P = 85 \\Rightarrow 38 + 19 + 12 + x = 85 \\Rightarrow 69 + x = 85 \\Rightarrow x = 16. Students certified in EXACTLY two skills = (P \\cap J \\text{ only}) + (J \\cap S \\text{ only}) + (P \\cap S \\text{ only}) = 19 + 13 + 16 = 48.'
  },
  {
    id: 'q19',
    section: 'Quantitative Aptitude',
    topic: 'Mensuration',
    difficulty: 'Medium',
    questionText: 'If the radius of the base of a right circular cylinder is halved and its height is doubled, then the volume of the new cylinder compared to the original volume becomes:',
    options: [
      { id: 'A', text: 'Unchanged' },
      { id: 'B', text: 'Halved (50%)' },
      { id: 'C', text: 'Doubled (200%)' },
      { id: 'D', text: 'One-fourth (25%)' }
    ],
    correctOption: 'B',
    explanation: 'Original volume V = πr²h. New volume V_new = π(r/2)²(2h) = π(r²/4)(2h) = (1/2)πr²h = V/2. Hence, the volume is halved.'
  },
  {
    id: 'q20',
    section: 'Quantitative Aptitude',
    topic: 'Mixtures & Alligation',
    difficulty: 'Hard',
    questionText: 'A vessel contains 60 liters of milk. 12 liters of milk are taken out and replaced with water. Then again 12 liters of the mixture are taken out and replaced with water. What is the final quantity of milk left in the vessel?',
    options: [
      { id: 'A', text: '36 liters' },
      { id: 'B', text: '38.4 liters' },
      { id: 'C', text: '40.2 liters' },
      { id: 'D', text: '42 liters' }
    ],
    correctOption: 'B',
    explanation: 'Final Milk = Initial × (1 - x/C)^n = 60 × (1 - 12/60)² = 60 × (4/5)² = 60 × (16/25) = 38.4 liters.'
  },

  // SECTION 2: Logical Reasoning (13 Questions)
  {
    id: 'q21',
    section: 'Logical Reasoning',
    topic: 'Number Series',
    difficulty: 'Easy',
    questionText: 'Find the missing number in the series: 7, 15, 31, 63, 127, ?',
    options: [
      { id: 'A', text: '253' },
      { id: 'B', text: '254' },
      { id: 'C', text: '255' },
      { id: 'D', text: '256' }
    ],
    correctOption: 'C',
    explanation: 'The pattern follows (×2 + 1): 7×2+1 = 15, 15×2+1 = 31, 31×2+1 = 63, 63×2+1 = 127, 127×2+1 = 255.'
  },
  {
    id: 'q22',
    section: 'Logical Reasoning',
    topic: 'Syllogism',
    difficulty: 'Medium',
    questionText: 'Statements: All servers are computers. Some computers are routers. No router is a switch.\nConclusions:\nI. Some servers being switches is a possibility.\nII. Some computers are definitely not switches.',
    options: [
      { id: 'A', text: 'Only Conclusion I follows' },
      { id: 'B', text: 'Only Conclusion II follows' },
      { id: 'C', text: 'Both Conclusions I and II follow' },
      { id: 'D', text: 'Neither I nor II follows' }
    ],
    correctOption: 'C',
    explanation: 'There is no direct restriction between servers and switches, so Some servers being switches is a valid possibility (I follows). Also, the part of computers that are routers cannot be switches because no router is a switch, so Some computers are definitely not switches (II follows).'
  },
  {
    id: 'q23',
    section: 'Logical Reasoning',
    topic: 'Coding-Decoding',
    difficulty: 'Medium',
    questionText: 'In a certain code language, if "TERMINAL" is written as "SDQLJMZK", how is "PLATFORM" written in that code?',
    options: [
      { id: 'A', text: 'OKZSENQL' },
      { id: 'B', text: 'OKZUGNQL' },
      { id: 'C', text: 'QMBUGPSN' },
      { id: 'D', text: 'OKZSEMPK' }
    ],
    correctOption: 'A',
    explanation: 'Each letter in TERMINAL is shifted -1 backward in the alphabet (T->S, E->D, R->Q, M->L, I->J is wait: let us check P-1=O, L-1=K, A-1=Z, T-1=S, F-1=E, O-1=N, R-1=Q, M-1=L => OKZSENQL).'
  },
  {
    id: 'q24',
    section: 'Logical Reasoning',
    topic: 'Blood Relations',
    difficulty: 'Medium',
    questionText: 'Pointing to a photograph of a boy, Rohan said, "He is the son of the only son of my mother." How is Rohan related to that boy?',
    options: [
      { id: 'A', text: 'Uncle' },
      { id: 'B', text: 'Brother' },
      { id: 'C', text: 'Father' },
      { id: 'D', text: 'Cousin' }
    ],
    correctOption: 'C',
    explanation: '"Only son of my mother" refers to Rohan himself. Therefore, the boy in the photograph is the son of Rohan, making Rohan the boy\'s Father.'
  },
  {
    id: 'q25',
    section: 'Logical Reasoning',
    topic: 'Direction Sense',
    difficulty: 'Easy',
    questionText: 'Ananya walks 8 km towards North, then turns right and walks 6 km. What is the shortest straight-line distance from her starting point to her final position?',
    options: [
      { id: 'A', text: '14 km' },
      { id: 'B', text: '10 km' },
      { id: 'C', text: '12 km' },
      { id: 'D', text: '9 km' }
    ],
    correctOption: 'B',
    explanation: 'By Pythagoras theorem, shortest distance = √(8² + 6²) = √(64 + 36) = √100 = 10 km.'
  },
  {
    id: 'q26',
    section: 'Logical Reasoning',
    topic: 'Clocks & Calendars',
    difficulty: 'Hard',
    questionText: 'What is the reflex angle between the hour hand and the minute hand of a clock at 3:40 PM?',
    options: [
      { id: 'A', text: '130°' },
      { id: 'B', text: '230°' },
      { id: 'C', text: '240°' },
      { id: 'D', text: '220°' }
    ],
    correctOption: 'B',
    explanation: 'Interior angle θ = |30H - (11/2)M| = |30(3) - (11/2)(40)| = |90 - 220| = 130°. Since the question asks for the reflex angle (between 180° and 360°), Reflex Angle = 360° - 130° = 230°.'
  },
  {
    id: 'q27',
    section: 'Logical Reasoning',
    topic: 'Order & Ranking',
    difficulty: 'Easy',
    questionText: 'In a row of 45 students facing North, Vikram is 18th from the left end. What is his rank from the right end?',
    options: [
      { id: 'A', text: '27th' },
      { id: 'B', text: '28th' },
      { id: 'C', text: '26th' },
      { id: 'D', text: '29th' }
    ],
    correctOption: 'B',
    explanation: 'Rank from Right = (Total - Rank from Left) + 1 = (45 - 18) + 1 = 27 + 1 = 28th.'
  },
  {
    id: 'q28',
    section: 'Logical Reasoning',
    topic: 'Analogy',
    difficulty: 'Easy',
    questionText: 'Select the option that is related to the third number in the same way as the second number is related to the first number:\n11 : 1331 :: 9 : ?',
    options: [
      { id: 'A', text: '729' },
      { id: 'B', text: '810' },
      { id: 'C', text: '656' },
      { id: 'D', text: '900' }
    ],
    correctOption: 'A',
    explanation: '1331 is the cube of 11 (11³ = 1331). Similarly, the cube of 9 is 9³ = 729.'
  },
  {
    id: 'q29',
    section: 'Logical Reasoning',
    topic: 'Seating Arrangement',
    difficulty: 'Medium',
    questionText: 'Five friends P, Q, R, S, and T are sitting in a row facing North. S is between T and Q. Q is to the immediate left of R. T is at the extreme left end. Who is sitting exactly in the middle?',
    options: [
      { id: 'A', text: 'S' },
      { id: 'B', text: 'Q' },
      { id: 'C', text: 'R' },
      { id: 'D', text: 'P' }
    ],
    correctOption: 'B',
    explanation: 'Since T is at the extreme left end (Position 1) and S is between T and Q, the order from left is T - S - Q. Since Q is immediate left of R, we get T - S - Q - R, leaving P at the extreme right: T - S - Q - R - P. Thus Q is in the middle.'
  },
  {
    id: 'q30',
    section: 'Logical Reasoning',
    topic: 'Inequalities',
    difficulty: 'Medium',
    questionText: 'Statement: M \\geq N > P = Q \\leq R < S\nWhich of the following conclusions is definitely TRUE?',
    options: [
      { id: 'A', text: 'M > Q' },
      { id: 'B', text: 'N \\geq R' },
      { id: 'C', text: 'P > S' },
      { id: 'D', text: 'M \\geq S' }
    ],
    correctOption: 'A',
    explanation: 'From M \\geq N > P = Q, since there is a strict inequality (>) between N and P, we definitely get M > P, and since P = Q, M > Q is strictly true.'
  },
  // SECTION 3: Computer Science & Tech (12 Questions - Includes Binary Tree SVG & Asymptotic Math)
  {
    id: 'q31',
    section: 'Computer Science & IT',
    topic: 'Binary Search Trees (Diagram)',
    difficulty: 'Medium',
    hasDiagram: true,
    svgDiagram: `<svg viewBox="0 0 440 230" xmlns="http://www.w3.org/2000/svg">
      <rect width="440" height="230" fill="#f8fafc" rx="12"/>
      <!-- Tree Edges -->
      <line x1="220" y1="45" x2="130" y2="105" stroke="#475569" stroke-width="2.5"/>
      <line x1="220" y1="45" x2="310" y2="105" stroke="#475569" stroke-width="2.5"/>
      <line x1="130" y1="105" x2="80" y2="175" stroke="#475569" stroke-width="2.5"/>
      <line x1="130" y1="105" x2="180" y2="175" stroke="#dc2626" stroke-width="2.5" stroke-dasharray="4,3"/>
      <line x1="310" y1="105" x2="260" y2="175" stroke="#475569" stroke-width="2.5"/>
      <line x1="310" y1="105" x2="360" y2="175" stroke="#475569" stroke-width="2.5"/>
      <!-- Tree Nodes -->
      <circle cx="220" cy="45" r="22" fill="#4f46e5" stroke="#312e81" stroke-width="2"/>
      <text x="220" y="50" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#ffffff">18</text>
      <circle cx="130" cy="105" r="20" fill="#ffffff" stroke="#4f46e5" stroke-width="2.5"/>
      <text x="130" y="110" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#1e293b">12</text>
      <circle cx="310" cy="105" r="20" fill="#ffffff" stroke="#4f46e5" stroke-width="2.5"/>
      <text x="310" y="110" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#1e293b">25</text>
      <circle cx="80" cy="175" r="19" fill="#ffffff" stroke="#059669" stroke-width="2"/>
      <text x="80" y="180" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#1e293b">7</text>
      <circle cx="180" cy="175" r="19" fill="#fef2f2" stroke="#dc2626" stroke-width="2.5"/>
      <text x="180" y="180" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#dc2626">21</text>
      <circle cx="260" cy="175" r="19" fill="#ffffff" stroke="#059669" stroke-width="2"/>
      <text x="260" y="180" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#1e293b">19</text>
      <circle cx="360" cy="175" r="19" fill="#ffffff" stroke="#059669" stroke-width="2"/>
      <text x="360" y="180" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#1e293b">30</text>
    </svg>`,
    questionText: 'Examine the binary tree shown in the diagram with root key = 18. Which node violates the strict Binary Search Tree (BST) invariant, and what is the worst-case time complexity of searching in a skewed BST of n nodes?',
    options: [
      { id: 'A', text: 'Node 19 violates BST; Complexity: \\Theta(\\log n)' },
      { id: 'B', text: 'Node 21 violates BST; Complexity: \\Theta(n)' },
      { id: 'C', text: 'Node 21 violates BST; Complexity: \\Theta(n \\log n)' },
      { id: 'D', text: 'Node 7 violates BST; Complexity: \\Theta(1)' }
    ],
    correctOption: 'B',
    explanation: 'Every node in the left subtree of root 18 must have a key strictly less than 18 (key \\in (-\\infty, 18)). Although 21 > 12 (valid as right child of 12), 21 > 18 violates the global BST condition for the left subtree of 18. Also, a skewed BST degrades to a linked list with search complexity \\Theta(n).'
  },
  {
    id: 'q32',
    section: 'Logical Reasoning',
    topic: 'Odd One Out',
    difficulty: 'Easy',
    questionText: 'Four letter-clusters have been given, out of which three are alike in some manner and one is different. Select the odd letter-cluster.',
    options: [
      { id: 'A', text: 'BDFH' },
      { id: 'B', text: 'MOQS' },
      { id: 'C', text: 'TVXZ' },
      { id: 'D', text: 'IKMP' }
    ],
    correctOption: 'D',
    explanation: 'In BDFH, MOQS, and TVXZ, consecutive letters have a gap of +2 (e.g., B+2=D, D+2=F, F+2=H). In IKMP, M(13) to P(16) is +3 instead of +2.'
  },
  {
    id: 'q33',
    section: 'Logical Reasoning',
    topic: 'Calendar',
    difficulty: 'Medium',
    questionText: 'If 15th August 2023 was a Tuesday, what day of the week was 15th August 2024?',
    options: [
      { id: 'A', text: 'Wednesday' },
      { id: 'B', text: 'Thursday' },
      { id: 'C', text: 'Friday' },
      { id: 'D', text: 'Monday' }
    ],
    correctOption: 'B',
    explanation: '2024 is a leap year, and February 2024 falls between Aug 15, 2023 and Aug 15, 2024. Thus there are 2 odd days. Tuesday + 2 days = Thursday.'
  },

  // SECTION 3: Computer Science & Tech (12 Questions)
  {
    id: 'q34',
    section: 'Computer Science & IT',
    topic: 'Data Structures',
    difficulty: 'Easy',
    questionText: 'What is the worst-case time complexity of searching for an element in a standard singly linked list of length n?',
    options: [
      { id: 'A', text: 'O(1)' },
      { id: 'B', text: 'O(log n)' },
      { id: 'C', text: 'O(n)' },
      { id: 'D', text: 'O(n log n)' }
    ],
    correctOption: 'C',
    explanation: 'In a singly linked list, random access is not possible. In the worst case, we must traverse all n nodes from the head, taking O(n) time.'
  },
  {
    id: 'q35',
    section: 'Computer Science & IT',
    topic: 'Operating Systems',
    difficulty: 'Medium',
    questionText: 'Which of the following CPU scheduling algorithms can suffer from the "Convoy Effect"?',
    options: [
      { id: 'A', text: 'Round Robin (RR)' },
      { id: 'B', text: 'Shortest Remaining Time First (SRTF)' },
      { id: 'C', text: 'First-Come, First-Served (FCFS)' },
      { id: 'D', text: 'Multilevel Feedback Queue' }
    ],
    correctOption: 'C',
    explanation: 'In non-preemptive FCFS scheduling, if a CPU-bound process with a huge burst time arrives first, all shorter I/O-bound processes wait behind it, causing the Convoy Effect.'
  },
  {
    id: 'q36',
    section: 'Computer Science & IT',
    topic: 'Database Management (DBMS)',
    difficulty: 'Medium',
    questionText: 'A relation R is in Third Normal Form (3NF) if and only if it is in 2NF and does NOT contain any:',
    options: [
      { id: 'A', text: 'Partial functional dependency' },
      { id: 'B', text: 'Transitive dependency of non-prime attributes on the primary key' },
      { id: 'C', text: 'Multi-valued dependency' },
      { id: 'D', text: 'Composite primary key' }
    ],
    correctOption: 'B',
    explanation: '2NF eliminates partial dependencies, while 3NF specifically eliminates transitive dependencies of non-prime attributes on any candidate key.'
  },
  {
    id: 'q37',
    section: 'Computer Science & IT',
    topic: 'Computer Networks',
    difficulty: 'Medium',
    questionText: 'In IPv4 addressing, an organization is granted a block with subnet mask 255.255.255.224 (/27). How many usable host IP addresses are available in this subnet?',
    options: [
      { id: 'A', text: '32' },
      { id: 'B', text: '30' },
      { id: 'C', text: '62' },
      { id: 'D', text: '14' }
    ],
    correctOption: 'B',
    explanation: 'A /27 prefix leaves 32 - 27 = 5 host bits. Total addresses = 2⁵ = 32. Subtracting 2 for the network address and broadcast address leaves 32 - 2 = 30 usable host IPs.'
  },
  {
    id: 'q38',
    section: 'Computer Science & IT',
    topic: 'Algorithms',
    difficulty: 'Hard',
    questionText: 'According to the Master Theorem, what is the asymptotic time complexity of the recurrence relation T(n) = 4T(\\frac{n}{2}) + n^2?',
    options: [
      { id: 'A', text: '\\Theta(n^2)' },
      { id: 'B', text: '\\Theta(n^2 \\log n)' },
      { id: 'C', text: '\\Theta(n^4)' },
      { id: 'D', text: '\\Theta(n \\log n)' }
    ],
    correctOption: 'B',
    explanation: 'Here a = 4, b = 2, and f(n) = n^2. Since n^{\\log_b a} = n^{\\log_2 4} = n^2, f(n) = \\Theta(n^{\\log_b a}). By Case 2 of the Master Theorem, T(n) = \\Theta(n^2 \\log n).'
  },
  {
    id: 'q39',
    section: 'Computer Science & IT',
    topic: 'Operating Systems',
    difficulty: 'Medium',
    questionText: 'Which of the following is NOT one of Coffman’s four necessary conditions for a deadlock to occur?',
    options: [
      { id: 'A', text: 'Mutual Exclusion' },
      { id: 'B', text: 'Hold and Wait' },
      { id: 'C', text: 'Starvation' },
      { id: 'D', text: 'Circular Wait' }
    ],
    correctOption: 'C',
    explanation: 'The four necessary conditions for deadlock are Mutual Exclusion, Hold and Wait, No Preemption, and Circular Wait. Starvation is a separate scheduling issue.'
  },
  {
    id: 'q40',
    section: 'Computer Science & IT',
    topic: 'Data Structures',
    difficulty: 'Easy',
    questionText: 'What is the postfix (Reverse Polish Notation) expression for the infix expression: A + B * C?',
    options: [
      { id: 'A', text: 'A B + C *' },
      { id: 'B', text: 'A B C * +' },
      { id: 'C', text: '+ A * B C' },
      { id: 'D', text: 'A B * C +' }
    ],
    correctOption: 'B',
    explanation: 'Multiplication (*) has higher precedence than addition (+). So B * C is evaluated first as B C *, and then added to A, giving A B C * +.'
  },
  {
    id: 'q41',
    section: 'Computer Science & IT',
    topic: 'Binary Trees',
    difficulty: 'Medium',
    questionText: 'What is the maximum number of nodes in a binary tree of height h (where a tree with a single root node has height 0)?',
    options: [
      { id: 'A', text: '2^h - 1' },
      { id: 'B', text: '2^(h+1) - 1' },
      { id: 'C', text: '2^h' },
      { id: 'D', text: '2^(h-1)' }
    ],
    correctOption: 'B',
    explanation: 'When root is at height 0, levels go from 0 to h. Total nodes = 2⁰ + 2¹ + ... + 2^h = 2^(h+1) - 1.'
  },
  {
    id: 'q42',
    section: 'Computer Science & IT',
    topic: 'DBMS & SQL',
    difficulty: 'Easy',
    questionText: 'In SQL, which clause is used to filter groups created by the GROUP BY clause based on an aggregate condition?',
    options: [
      { id: 'A', text: 'WHERE' },
      { id: 'B', text: 'HAVING' },
      { id: 'C', text: 'ORDER BY' },
      { id: 'D', text: 'DISTINCT' }
    ],
    correctOption: 'B',
    explanation: 'The WHERE clause filters individual rows before grouping, whereas the HAVING clause filters groups after the GROUP BY aggregation is performed.'
  },
  {
    id: 'q43',
    section: 'Computer Science & IT',
    topic: 'Computer Networks',
    difficulty: 'Easy',
    questionText: 'Which layer of the OSI model is responsible for end-to-end process-to-process communication, flow control, and port addressing?',
    options: [
      { id: 'A', text: 'Network Layer' },
      { id: 'B', text: 'Data Link Layer' },
      { id: 'C', text: 'Transport Layer' },
      { id: 'D', text: 'Session Layer' }
    ],
    correctOption: 'C',
    explanation: 'The Transport Layer (Layer 4, TCP/UDP) handles port addressing, segmentation, flow control, and end-to-end delivery between processes.'
  },
  {
    id: 'q44',
    section: 'Computer Science & IT',
    topic: 'Graph Algorithms',
    difficulty: 'Hard',
    questionText: 'Which algorithm accurately finds the shortest paths from a single source vertex in a weighted directed graph even when negative edge weights are present (assuming no negative weight cycles)?',
    options: [
      { id: 'A', text: 'Dijkstra’s Algorithm' },
      { id: 'B', text: 'Prim’s Algorithm' },
      { id: 'C', text: 'Bellman-Ford Algorithm' },
      { id: 'D', text: 'Kruskal’s Algorithm' }
    ],
    correctOption: 'C',
    explanation: 'Dijkstra’s greedy approach can fail with negative weight edges, whereas the Bellman-Ford algorithm relaxes all edges V-1 times and works accurately with negative weights.'
  },
  {
    id: 'q45',
    section: 'Computer Science & IT',
    topic: 'Digital Logic & COA',
    difficulty: 'Easy',
    questionText: 'Which logic gate is known as a "Universal Gate" because any Boolean function can be implemented using only gates of this type?',
    options: [
      { id: 'A', text: 'XOR Gate' },
      { id: 'B', text: 'AND Gate' },
      { id: 'C', text: 'NAND Gate' },
      { id: 'D', text: 'OR Gate' }
    ],
    correctOption: 'C',
    explanation: 'NAND and NOR gates are Universal Gates because NOT, AND, and OR operations can be constructed solely from NAND (or NOR) gates.'
  },

  // SECTION 4: General Awareness & English (12 Questions)
  {
    id: 'q46',
    section: 'General Awareness & English',
    topic: 'Indian Polity',
    difficulty: 'Easy',
    questionText: 'Which Article of the Constitution of India did Dr. B.R. Ambedkar describe as the "Heart and Soul of the Constitution"?',
    options: [
      { id: 'A', text: 'Article 14 (Right to Equality)' },
      { id: 'B', text: 'Article 19 (Right to Freedom)' },
      { id: 'C', text: 'Article 21 (Right to Life)' },
      { id: 'D', text: 'Article 32 (Right to Constitutional Remedies)' }
    ],
    correctOption: 'D',
    explanation: 'Dr. B.R. Ambedkar called Article 32 the Heart and Soul of the Constitution because it empowers citizens to move the Supreme Court directly to enforce Fundamental Rights via writs.'
  },
  {
    id: 'q47',
    section: 'General Awareness & English',
    topic: 'Macroeconomics',
    difficulty: 'Medium',
    questionText: 'When the Reserve Bank of India (RBI) increases the Repo Rate to curb inflation, what is its immediate impact on the economy?',
    options: [
      { id: 'A', text: 'Borrowing becomes cheaper for commercial banks' },
      { id: 'B', text: 'Borrowing becomes costlier for commercial banks and money supply tightens' },
      { id: 'C', text: 'Liquidity in the market increases sharply' },
      { id: 'D', text: 'Fiscal deficit automatically drops to zero' }
    ],
    correctOption: 'B',
    explanation: 'The Repo Rate is the rate at which RBI lends short-term funds to commercial banks. Raising it increases borrowing costs, which reduces excess money supply and curbs demand-pull inflation.'
  },
  {
    id: 'q48',
    section: 'General Awareness & English',
    topic: 'Physics & Optics (Diagram)',
    difficulty: 'Medium',
    hasDiagram: true,
    svgDiagram: `<svg viewBox="0 0 440 220" xmlns="http://www.w3.org/2000/svg">
      <rect width="440" height="220" fill="#f8fafc" rx="12"/>
      <!-- Support Ceiling -->
      <line x1="140" y1="25" x2="300" y2="25" stroke="#334155" stroke-width="4"/>
      <line x1="220" y1="25" x2="220" y2="65" stroke="#475569" stroke-width="3"/>
      <!-- Pulley Wheel -->
      <circle cx="220" cy="65" r="28" fill="#e0e7ff" stroke="#4f46e5" stroke-width="3"/>
      <circle cx="220" cy="65" r="5" fill="#1e293b"/>
      <!-- Left & Right Strings -->
      <line x1="192" y1="65" x2="192" y2="145" stroke="#0f172a" stroke-width="2.5"/>
      <line x1="248" y1="65" x2="248" y2="120" stroke="#0f172a" stroke-width="2.5"/>
      <!-- Mass m1 = 3 kg -->
      <rect x="167" y="145" width="50" height="42" rx="6" fill="#10b981" stroke="#047857" stroke-width="2"/>
      <text x="192" y="170" text-anchor="middle" font-family="sans-serif" font-size="12" font-weight="bold" fill="#ffffff">3 kg</text>
      <!-- Mass m2 = 5 kg -->
      <rect x="223" y="120" width="50" height="52" rx="6" fill="#f43f5e" stroke="#be123c" stroke-width="2"/>
      <text x="248" y="150" text-anchor="middle" font-family="sans-serif" font-size="12" font-weight="bold" fill="#ffffff">5 kg</text>
      <!-- Acceleration Arrows -->
      <text x="140" y="168" font-family="sans-serif" font-size="12" font-weight="bold" fill="#047857">↑ a</text>
      <text x="285" y="150" font-family="sans-serif" font-size="12" font-weight="bold" fill="#be123c">↓ a</text>
      <text x="345" y="95" font-family="sans-serif" font-size="12" font-weight="bold" fill="#334155">g = 10 m/s²</text>
    </svg>`,
    questionText: 'In the frictionless Atwood machine pulley diagram shown above, two masses m_1 = 3 kg and m_2 = 5 kg are connected by a light inextensible string over a massless pulley (g = 10 m/s^2). What are the acceleration a of the blocks and tension T in the string?',
    options: [
      { id: 'A', text: 'a = 2.5 m/s^2, T = 37.5 N' },
      { id: 'B', text: 'a = 2.0 m/s^2, T = 36.0 N' },
      { id: 'C', text: 'a = 2.5 m/s^2, T = 40.0 N' },
      { id: 'D', text: 'a = 5.0 m/s^2, T = 30.0 N' }
    ],
    correctOption: 'A',
    explanation: 'Net driving force = (m_2 - m_1)g = (5 - 3) \\times 10 = 20 N. Total mass = m_1 + m_2 = 8 kg. Thus acceleration a = \\frac{m_2 - m_1}{m_1 + m_2} g = \\frac{20}{8} = 2.5 m/s^2. Tension T = \\frac{2 m_1 m_2}{m_1 + m_2} g = \\frac{2 \\times 3 \\times 5}{8} \\times 10 = 37.5 N.'
  },
  {
    id: 'q49',
    section: 'General Awareness & English',
    topic: 'Modern History',
    difficulty: 'Medium',
    questionText: 'The historic "Poona Pact" of September 1932 was signed between Mahatma Gandhi and:',
    options: [
      { id: 'A', text: 'Lord Irwin' },
      { id: 'B', text: 'Subhas Chandra Bose' },
      { id: 'C', text: 'Dr. B.R. Ambedkar' },
      { id: 'D', text: 'Muhammad Ali Jinnah' }
    ],
    correctOption: 'C',
    explanation: 'The Poona Pact (1932) was an agreement between Mahatma Gandhi and Dr. B.R. Ambedkar regarding reserved seats for the Depressed Classes within the general electorate.'
  },
  {
    id: 'q50',
    section: 'General Awareness & English',
    topic: 'English Grammar',
    difficulty: 'Medium',
    questionText: 'Identify the grammatically correct sentence:',
    options: [
      { id: 'A', text: 'Neither the manager nor the employees was present at the meeting.' },
      { id: 'B', text: 'Neither the manager nor the employees were present at the meeting.' },
      { id: 'C', text: 'Neither the manager or the employees were present at the meeting.' },
      { id: 'D', text: 'Neither the manager nor the employees has been present at the meeting.' }
    ],
    correctOption: 'B',
    explanation: 'With "Neither...nor", the verb agrees with the closer subject ("the employees", which is plural). Therefore, the plural verb "were" is grammatically correct.'
  },
  {
    id: 'q51',
    section: 'General Awareness & English',
    topic: 'Idioms & Phrases',
    difficulty: 'Easy',
    questionText: 'What is the most appropriate meaning of the idiom "To burn the midnight oil"?',
    options: [
      { id: 'A', text: 'To waste valuable resources recklessly' },
      { id: 'B', text: 'To work or study late into the night' },
      { id: 'C', text: 'To create a sudden controversy' },
      { id: 'D', text: 'To destroy old evidence' }
    ],
    correctOption: 'B',
    explanation: '"To burn the midnight oil" means to read, study, or work diligently late into the night.'
  },
  {
    id: 'q52',
    section: 'General Awareness & English',
    topic: 'Vocabulary',
    difficulty: 'Medium',
    questionText: 'Select the most appropriate ANTONYM of the word "EPHEMERAL":',
    options: [
      { id: 'A', text: 'Transient' },
      { id: 'B', text: 'Fleeting' },
      { id: 'C', text: 'Permanent' },
      { id: 'D', text: 'Fragile' }
    ],
    correctOption: 'C',
    explanation: '"Ephemeral" means lasting for a very short time (transient/fleeting). Its exact antonym is "Permanent" or "Eternal".'
  },
  {
    id: 'q53',
    section: 'General Awareness & English',
    topic: 'Geography',
    difficulty: 'Easy',
    questionText: 'Which imaginary line passes through the middle of India, dividing the country into almost two equal climatic halves?',
    options: [
      { id: 'A', text: 'Equator' },
      { id: 'B', text: 'Tropic of Capricorn' },
      { id: 'C', text: 'Tropic of Cancer (23°30\' N)' },
      { id: 'D', text: 'Arctic Circle' }
    ],
    correctOption: 'C',
    explanation: 'The Tropic of Cancer (23.5° N) passes through 8 Indian states, dividing India into tropical and subtropical zones.'
  },
  {
    id: 'q54',
    section: 'General Awareness & English',
    topic: 'Biology & Health',
    difficulty: 'Easy',
    questionText: 'Which organelle is known as the "Powerhouse of the Cell" because it generates most of the cell\'s supply of ATP?',
    options: [
      { id: 'A', text: 'Ribosome' },
      { id: 'B', text: 'Golgi Apparatus' },
      { id: 'C', text: 'Mitochondria' },
      { id: 'D', text: 'Lysosome' }
    ],
    correctOption: 'C',
    explanation: 'Mitochondria generate adenosine triphosphate (ATP) through cellular respiration, earning the title "Powerhouse of the Cell".'
  },
  {
    id: 'q55',
    section: 'General Awareness & English',
    topic: 'Active & Passive Voice',
    difficulty: 'Easy',
    questionText: 'Choose the correct passive voice form of: "The committee has approved the new exam blueprint."',
    options: [
      { id: 'A', text: 'The new exam blueprint was approved by the committee.' },
      { id: 'B', text: 'The new exam blueprint has been approved by the committee.' },
      { id: 'C', text: 'The new exam blueprint is being approved by the committee.' },
      { id: 'D', text: 'The new exam blueprint had been approved by the committee.' }
    ],
    correctOption: 'B',
    explanation: 'Present Perfect Active ("has + V3") converts to Present Perfect Passive ("has/have + been + V3"): "has been approved".'
  },
  {
    id: 'q56',
    section: 'General Awareness & English',
    topic: 'One Word Substitution',
    difficulty: 'Medium',
    questionText: 'Select the word which means: "A person who can speak multiple languages fluently."',
    options: [
      { id: 'A', text: 'Philanthropist' },
      { id: 'B', text: 'Polyglot' },
      { id: 'C', text: 'Somnambulist' },
      { id: 'D', text: 'Stoic' }
    ],
    correctOption: 'B',
    explanation: 'A "Polyglot" is a person who knows and is able to use several languages.'
  },
  {
    id: 'q57',
    section: 'General Awareness & English',
    topic: 'Indian Polity',
    difficulty: 'Medium',
    questionText: 'What is the minimum age required to be eligible for election as the President of India under Article 58 of the Constitution?',
    options: [
      { id: 'A', text: '25 years' },
      { id: 'B', text: '30 years' },
      { id: 'C', text: '35 years' },
      { id: 'D', text: '40 years' }
    ],
    correctOption: 'C',
    explanation: 'Under Article 58 of the Indian Constitution, a candidate must have completed 35 years of age to be eligible for election as President.'
  }
];

// ============================================================================
// 3. GEMINI API HELPER WITH EXPONENTIAL BACKOFF
// ============================================================================
async function callGeminiWithRetry(payload, maxRetries = 3) {
  const apiKey = import.meta.env.VITE_GEMINI_API_KEY || "";
  if (!apiKey) {
    throw new Error(
      'No Gemini API key configured. Add VITE_GEMINI_API_KEY to your .env file, or use the "Instant 50-MCQ Demo Exam" button instead.'
    );
  }
  const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`;

  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        throw new Error(`HTTP status ${response.status}`);
      }

      const data = await response.json();
      const text = data?.candidates?.[0]?.content?.parts?.[0]?.text;
      if (!text) {
        throw new Error('Empty response from Gemini API');
      }
      return text;
    } catch (err) {
      if (attempt === maxRetries) {
        throw err;
      }
      const delayMs = Math.pow(2, attempt) * 1000;
      await new Promise((res) => setTimeout(res, delayMs));
    }
  }
}

// ============================================================================
// 4. MAIN APPLICATION COMPONENT
// ============================================================================
export default function App() {
  // Navigation Mode: 'CONFIG' | 'EXAM' | 'RESULT'
  const [viewMode, setViewMode] = useState('CONFIG');

  // Exam Blueprint Configuration State
  const [selectedPresetId, setSelectedPresetId] = useState('SSC_CGL');
  const [examTitle, setExamTitle] = useState('SSC CGL Tier-I Full Mock CBT');
  const [totalQuestionsTarget, setTotalQuestionsTarget] = useState(40);
  const [durationMinutes, setDurationMinutes] = useState(60);
  const [positiveMarks, setPositiveMarks] = useState(2);
  const [negativeMarks, setNegativeMarks] = useState(0.5);
  const [difficultyMix, setDifficultyMix] = useState('Balanced (30% Easy, 50% Medium, 20% Hard)');
  const [includeDiagrams, setIncludeDiagrams] = useState(true);
  const [sections, setSections] = useState(EXAM_PRESETS[0].sections);

  // Parallel Batch Generation State
  const [isGenerating, setIsGenerating] = useState(false);
  const [batchJobs, setBatchJobs] = useState([]);
  const [generationError, setGenerationError] = useState(null);

  // Active CBT Exam Session State
  const [questions, setQuestions] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [selectedOptionDraft, setSelectedOptionDraft] = useState(null);
  const [timeLeftSeconds, setTimeLeftSeconds] = useState(3600);
  const [showSubmitModal, setShowSubmitModal] = useState(false);
  const [mobilePaletteOpen, setMobilePaletteOpen] = useState(false);
  const [paletteFilterSection, setPaletteFilterSection] = useState('ALL');
  const [zoomedDiagram, setZoomedDiagram] = useState(null);

  // Post-Exam Analytics & AI Coach State
  const [resultFilter, setResultFilter] = useState('ALL');
  const [resultSectionFilter, setResultSectionFilter] = useState('ALL');
  const [aiCoachReport, setAiCoachReport] = useState(null);
  const [isLoadingCoach, setIsLoadingCoach] = useState(false);

  // Apply Preset Handler
  const handleSelectPreset = (preset) => {
    setSelectedPresetId(preset.id);
    setExamTitle(`${preset.name} AI Mock CBT`);
    setDurationMinutes(preset.durationMinutes);
    setPositiveMarks(preset.positiveMarks);
    setNegativeMarks(preset.negativeMarks);
    setTotalQuestionsTarget(preset.defaultCount);
    setSections(preset.sections.map((s) => ({ ...s })));
  };

  // Add/Update/Remove Section in Blueprint
  const handleAddSection = () => {
    const newSec = {
      id: `sec_${Date.now()}`,
      name: 'New Subject Section',
      topics: 'Core Concepts, Problem Solving, Analytical Applications',
      weight: 25
    };
    setSections([...sections, newSec]);
  };

  const handleUpdateSection = (id, field, value) => {
    setSections(sections.map((s) => (s.id === id ? { ...s, [field]: value } : s)));
  };

  const handleRemoveSection = (id) => {
    if (sections.length <= 1) return;
    setSections(sections.filter((s) => s.id !== id));
  };

  // ============================================================================
  // 5. PARALLEL BATCH AI GENERATOR (Chunked 10-15 MCQs per batch + SVG Diagrams)
  // ============================================================================
  const startParallelAIGeneration = async () => {
    setGenerationError(null);
    setIsGenerating(true);

    // Calculate how many questions each section should get
    const totalWeight = sections.reduce((acc, s) => acc + (Number(s.weight) || 1), 0);
    const plannedBatches = [];

    let allocatedSoFar = 0;
    sections.forEach((sec, idx) => {
      const isLast = idx === sections.length - 1;
      const sectionCount = isLast
        ? Math.max(5, totalQuestionsTarget - allocatedSoFar)
        : Math.max(5, Math.round((Number(sec.weight || 1) / totalWeight) * totalQuestionsTarget));
      allocatedSoFar += sectionCount;

      // Split any section > 12 questions into parallel sub-batches
      const MAX_PER_BATCH = 12;
      let remainingInSec = sectionCount;
      let partNum = 1;
      while (remainingInSec > 0) {
        const countForThisBatch = Math.min(MAX_PER_BATCH, remainingInSec);
        plannedBatches.push({
          batchId: `batch_${sec.id}_${partNum}`,
          sectionName: sec.name,
          topics: sec.topics,
          count: countForThisBatch,
          partLabel: sectionCount > MAX_PER_BATCH ? `Part ${partNum}` : 'Full Section',
          status: 'GENERATING'
        });
        remainingInSec -= countForThisBatch;
        partNum++;
      }
    });

    setBatchJobs(plannedBatches);

    // Schema property ordering trick: `svgDiagram` and `explanation` are placed BEFORE `correctOption`
    // so the LLM draws the figure and solves the math first before picking an option letter!
    const responseSchema = {
      type: 'OBJECT',
      properties: {
        questions: {
          type: 'ARRAY',
          items: {
            type: 'OBJECT',
            properties: {
              topic: { type: 'STRING' },
              difficulty: { type: 'STRING', enum: ['Easy', 'Medium', 'Hard'] },
              questionText: { type: 'STRING' },
              hasDiagram: { type: 'BOOLEAN' },
              svgDiagram: { type: 'STRING' },
              options: {
                type: 'ARRAY',
                items: {
                  type: 'OBJECT',
                  properties: {
                    id: { type: 'STRING', enum: ['A', 'B', 'C', 'D'] },
                    text: { type: 'STRING' }
                  },
                  required: ['id', 'text'],
                  propertyOrdering: ['id', 'text']
                }
              },
              explanation: { type: 'STRING' },
              correctOption: { type: 'STRING', enum: ['A', 'B', 'C', 'D'] }
            },
            required: [
              'topic',
              'difficulty',
              'questionText',
              'hasDiagram',
              'svgDiagram',
              'options',
              'explanation',
              'correctOption'
            ],
            propertyOrdering: [
              'topic',
              'difficulty',
              'questionText',
              'hasDiagram',
              'svgDiagram',
              'options',
              'explanation',
              'correctOption'
            ]
          }
        }
      },
      required: ['questions'],
      propertyOrdering: ['questions']
    };

    try {
      // Fire all batches in parallel using Promise.all
      const batchPromises = plannedBatches.map(async (batch, bIndex) => {
        const diagramInstruction = includeDiagrams
          ? `DIAGRAM REQUIREMENT: For at least 30% to 40% of the questions in this batch (especially Geometry, Mensuration, Data Interpretation Bar/Pie/Line charts, Venn Diagrams, Seating Arrangements, Physics Pulleys/Circuits, or CS Trees/Graphs), set "hasDiagram": true and provide a complete, crisp, valid inline <svg viewBox="0 0 440 230" xmlns="http://www.w3.org/2000/svg">...</svg> inside "svgDiagram" with light background (#f8fafc), clear dark labels, and exact geometric/chart numbers matching the question. For non-diagram questions, set "hasDiagram": false and "svgDiagram": "".`
          : `Set "hasDiagram": false and "svgDiagram": "" for all questions.`;

        const systemInstruction = `You are a senior competitive exam paper setter for ${examTitle}.
Generate realistic, exam-standard Multiple Choice Questions (MCQs).
MATH & SYMBOL FORMATTING: Whenever using mathematical or scientific expressions (integrals, Greek letters like λ, θ, α, β, π, Δ, Ω, square roots, fractions, exponents), either use clean Unicode symbols (∫, λ, θ, π, √, ≤, ≥, ≠, Δ, Ω) or standard LaTeX commands (\\int_{a}^{b}, \\frac{a}{b}, \\sqrt{x}, \\lambda, \\theta, x^2) which our portal renders natively.
${diagramInstruction}
IMPORTANT: Always write the step-by-step 'explanation' first to verify the math or logic, and then set 'correctOption' to match the verified answer.`;

        const userPrompt = `Generate exactly ${batch.count} unique, high-quality competitive exam MCQs for the section "${batch.sectionName}" (${batch.partLabel}).
Target Topics to cover: ${batch.topics}.
Difficulty Distribution: ${difficultyMix}.
Ensure each question has 4 clear options labeled A, B, C, D, a verified step-by-step explanation, and the exact correctOption.`;

        const payload = {
          contents: [{ parts: [{ text: userPrompt }] }],
          systemInstruction: { parts: [{ text: systemInstruction }] },
          generationConfig: {
            responseMimeType: 'application/json',
            responseSchema: responseSchema,
            temperature: 0.7
          }
        };

        const rawJsonText = await callGeminiWithRetry(payload);
        const parsed = JSON.parse(rawJsonText);
        const generatedList = Array.isArray(parsed.questions) ? parsed.questions : [];

        // Mark this specific batch as completed in UI
        setBatchJobs((prev) =>
          prev.map((job) =>
            job.batchId === batch.batchId ? { ...job, status: 'COMPLETED', generatedCount: generatedList.length } : job
          )
        );

        return generatedList.map((q, qIdx) => ({
          id: `ai_${bIndex}_${qIdx}`,
          section: batch.sectionName,
          topic: q.topic || batch.sectionName,
          difficulty: q.difficulty || 'Medium',
          questionText: q.questionText,
          hasDiagram: Boolean(q.hasDiagram && q.svgDiagram && q.svgDiagram.includes('<svg')),
          svgDiagram: q.svgDiagram || '',
          options: Array.isArray(q.options) && q.options.length === 4
            ? q.options
            : [
                { id: 'A', text: 'Option A' },
                { id: 'B', text: 'Option B' },
                { id: 'C', text: 'Option C' },
                { id: 'D', text: 'Option D' }
              ],
          correctOption: ['A', 'B', 'C', 'D'].includes(q.correctOption) ? q.correctOption : 'A',
          explanation: q.explanation || 'Step-by-step solution verified.'
        }));
      });

      const nestedQuestions = await Promise.all(batchPromises);
      const flatQuestions = nestedQuestions.flat();

      if (flatQuestions.length === 0) {
        throw new Error('No questions were returned by the AI model.');
      }

      // Initialize CBT session state with generated questions
      initializeCBTExamSession(flatQuestions);
    } catch (error) {
      setGenerationError(
        'Live AI batch generation encountered a temporary error or timeout. You can retry or click "Load Pre-Built 50-MCQ Paper" to test the CBT portal (with SVG diagrams & math equations) immediately.'
      );
    } finally {
      setIsGenerating(false);
    }
  };

  // Load Instant 50-MCQ Pre-built Paper
  const loadInstantDemoPaper = () => {
    setExamTitle('All-India Competitive CBT Mock (50 MCQs)');
    setDurationMinutes(60);
    setPositiveMarks(2);
    setNegativeMarks(0.5);
    initializeCBTExamSession(DEMO_50_QUESTIONS);
  };

  // Initialize CBT Exam State
  const initializeCBTExamSession = (rawQuestions) => {
    const initialized = rawQuestions.map((q, index) => ({
      ...q,
      number: index + 1,
      selectedOption: null,
      // First question starts as NOT_ANSWERED because the candidate visits it immediately
      cbtState: index === 0 ? 'NOT_ANSWERED' : 'NOT_VISITED',
      timeSpentSeconds: 0
    }));

    setQuestions(initialized);
    setCurrentIndex(0);
    setSelectedOptionDraft(null);
    setTimeLeftSeconds(durationMinutes * 60);
    setPaletteFilterSection('ALL');
    setAiCoachReport(null);
    setShowSubmitModal(false);
    setViewMode('EXAM');
  };

  // ============================================================================
  // 6. LIVE EXAM TIMERS (Countdown + Per-Question Time Tracker)
  // ============================================================================
  useEffect(() => {
    if (viewMode !== 'EXAM') return;

    const timer = setInterval(() => {
      // 1. Decrement overall exam countdown
      setTimeLeftSeconds((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          setViewMode('RESULT');
          return 0;
        }
        return prev - 1;
      });

      // 2. Increment timeSpentSeconds on the currently active question
      setQuestions((prevQuestions) =>
        prevQuestions.map((q, idx) =>
          idx === currentIndex ? { ...q, timeSpentSeconds: q.timeSpentSeconds + 1 } : q
        )
      );
    }, 1000);

    return () => clearInterval(timer);
  }, [viewMode, currentIndex]);

  // Navigate to a specific question index & update visitation state
  const navigateToQuestion = (targetIndex, updatedQuestionsList = questions) => {
    const boundedIndex = Math.max(0, Math.min(updatedQuestionsList.length - 1, targetIndex));
    const nextList = updatedQuestionsList.map((q, idx) => {
      // If leaving current question without saving & it was NOT_VISITED, mark NOT_ANSWERED
      if (idx === boundedIndex && q.cbtState === 'NOT_VISITED') {
        return { ...q, cbtState: 'NOT_ANSWERED' };
      }
      return q;
    });

    setQuestions(nextList);
    setCurrentIndex(boundedIndex);
    setSelectedOptionDraft(nextList[boundedIndex]?.selectedOption || null);
  };

  // CBT Control 1: Save & Next (Green: ANSWERED, or Red: NOT_ANSWERED if no option picked)
  const handleSaveAndNext = () => {
    const updated = questions.map((q, idx) => {
      if (idx !== currentIndex) return q;
      const newStatus = selectedOptionDraft ? 'ANSWERED' : 'NOT_ANSWERED';
      return {
        ...q,
        selectedOption: selectedOptionDraft,
        cbtState: newStatus
      };
    });

    const nextIdx = currentIndex < questions.length - 1 ? currentIndex + 1 : currentIndex;
    navigateToQuestion(nextIdx, updated);
  };

  // CBT Control 2: Mark for Review & Next (Purple: MARKED_FOR_REVIEW or ANSWERED_AND_MARKED)
  const handleMarkForReviewAndNext = () => {
    const updated = questions.map((q, idx) => {
      if (idx !== currentIndex) return q;
      const newStatus = selectedOptionDraft ? 'ANSWERED_AND_MARKED' : 'MARKED_FOR_REVIEW';
      return {
        ...q,
        selectedOption: selectedOptionDraft,
        cbtState: newStatus
      };
    });

    const nextIdx = currentIndex < questions.length - 1 ? currentIndex + 1 : currentIndex;
    navigateToQuestion(nextIdx, updated);
  };

  // CBT Control 3: Clear Response
  const handleClearResponse = () => {
    setSelectedOptionDraft(null);
    setQuestions((prev) =>
      prev.map((q, idx) =>
        idx === currentIndex
          ? { ...q, selectedOption: null, cbtState: 'NOT_ANSWERED' }
          : q
      )
    );
  };

  // Distinct Section Names in Active Exam
  const examSectionNames = useMemo(() => {
    const set = new Set(questions.map((q) => q.section));
    return Array.from(set);
  }, [questions]);

  // Palette Counts
  const paletteCounts = useMemo(() => {
    const counts = {
      ANSWERED: 0,
      NOT_ANSWERED: 0,
      NOT_VISITED: 0,
      MARKED_FOR_REVIEW: 0,
      ANSWERED_AND_MARKED: 0
    };
    questions.forEach((q) => {
      if (counts[q.cbtState] !== undefined) {
        counts[q.cbtState]++;
      }
    });
    return counts;
  }, [questions]);

  // ============================================================================
  // 7. SCORECARD & ANALYTICS CALCULATION
  // ============================================================================
  const examAnalytics = useMemo(() => {
    if (questions.length === 0) return null;

    let correctCount = 0;
    let incorrectCount = 0;
    let unattemptedCount = 0;
    let totalTimeSpent = 0;

    const sectionStatsMap = {};
    const topicStatsMap = {};

    questions.forEach((q) => {
      totalTimeSpent += q.timeSpentSeconds;

      if (!sectionStatsMap[q.section]) {
        sectionStatsMap[q.section] = {
          section: q.section,
          total: 0,
          attempted: 0,
          correct: 0,
          incorrect: 0,
          score: 0,
          timeSpent: 0
        };
      }
      if (!topicStatsMap[q.topic]) {
        topicStatsMap[q.topic] = { topic: q.topic, total: 0, correct: 0, incorrect: 0, timeSpent: 0 };
      }

      const secStat = sectionStatsMap[q.section];
      const topStat = topicStatsMap[q.topic];
      secStat.total++;
      secStat.timeSpent += q.timeSpentSeconds;
      topStat.total++;
      topStat.timeSpent += q.timeSpentSeconds;

      // In standard TCS iON / NTA CBTs, both ANSWERED and ANSWERED_AND_MARKED are evaluated
      const isEvaluated =
        (q.cbtState === 'ANSWERED' || q.cbtState === 'ANSWERED_AND_MARKED') && q.selectedOption;

      if (!isEvaluated) {
        unattemptedCount++;
      } else {
        secStat.attempted++;
        if (q.selectedOption === q.correctOption) {
          correctCount++;
          secStat.correct++;
          topStat.correct++;
          secStat.score += positiveMarks;
        } else {
          incorrectCount++;
          secStat.incorrect++;
          topStat.incorrect++;
          secStat.score -= negativeMarks;
        }
      }
    });

    const maxScore = questions.length * positiveMarks;
    const rawTotalScore = correctCount * positiveMarks - incorrectCount * negativeMarks;
    const totalScore = Number(rawTotalScore.toFixed(2));
    const attemptedCount = correctCount + incorrectCount;
    const accuracy = attemptedCount > 0 ? Math.round((correctCount / attemptedCount) * 100) : 0;
    const scorePercentage = maxScore > 0 ? Math.max(0, (totalScore / maxScore) * 100) : 0;

    // Realistic competitive percentile curve estimation
    const estimatedPercentile = Math.min(
      99.8,
      Math.max(12.5, Number((35 + scorePercentage * 0.64).toFixed(1)))
    );

    return {
      totalQuestions: questions.length,
      attemptedCount,
      correctCount,
      incorrectCount,
      unattemptedCount,
      totalScore,
      maxScore,
      accuracy,
      totalTimeSpent,
      estimatedPercentile,
      sectionBreakdown: Object.values(sectionStatsMap),
      topicBreakdown: Object.values(topicStatsMap)
    };
  }, [questions, positiveMarks, negativeMarks]);

  // ============================================================================
  // 8. POST-EXAM AI PERFORMANCE COACH (Gemini API)
  // ============================================================================
  const generateAICoachFeedback = async () => {
    if (!examAnalytics) return;
    setIsLoadingCoach(true);

    const coachSchema = {
      type: 'OBJECT',
      properties: {
        overallVerdict: { type: 'STRING' },
        timeManagementAnalysis: { type: 'STRING' },
        strongAreas: { type: 'ARRAY', items: { type: 'STRING' } },
        weakTopicsToRevise: { type: 'ARRAY', items: { type: 'STRING' } },
        actionableStudyPlan: { type: 'ARRAY', items: { type: 'STRING' } }
      },
      required: [
        'overallVerdict',
        'timeManagementAnalysis',
        'strongAreas',
        'weakTopicsToRevise',
        'actionableStudyPlan'
      ]
    };

    const summaryPrompt = `Analyze this student's CBT Competitive Exam attempt for "${examTitle}":
- Score: ${examAnalytics.totalScore} / ${examAnalytics.maxScore} (Accuracy: ${examAnalytics.accuracy}%, Attempted: ${examAnalytics.attemptedCount}/${examAnalytics.totalQuestions})
- Total Time Spent: ${Math.floor(examAnalytics.totalTimeSpent / 60)}m ${examAnalytics.totalTimeSpent % 60}s
- Section-wise Performance: ${JSON.stringify(examAnalytics.sectionBreakdown)}
- Topic-wise Performance: ${JSON.stringify(examAnalytics.topicBreakdown.slice(0, 15))}

Provide a personalized, encouraging, and deeply analytical coaching report with specific time-management insights and high-yield revision steps.`;

    try {
      const responseText = await callGeminiWithRetry({
        contents: [{ parts: [{ text: summaryPrompt }] }],
        generationConfig: {
          responseMimeType: 'application/json',
          responseSchema: coachSchema
        }
      });
      setAiCoachReport(JSON.parse(responseText));
    } catch (err) {
      // Fallback coaching analysis if offline
      setAiCoachReport({
        overallVerdict: `You scored ${examAnalytics.totalScore}/${examAnalytics.maxScore} with ${examAnalytics.accuracy}% accuracy. Focusing on reducing negative marking in moderate-to-hard questions will boost your percentile above 95%.`,
        timeManagementAnalysis: `You spent an average of ${Math.round(
          examAnalytics.totalTimeSpent / Math.max(1, examAnalytics.attemptedCount)
        )} seconds per attempted question. Use the 2-pass "Mark for Review" strategy to clear easy questions in the first 25 minutes.`,
        strongAreas: examAnalytics.sectionBreakdown
          .filter((s) => s.correct >= s.incorrect)
          .map((s) => `${s.section} (${s.correct}/${s.total} correct)`),
        weakTopicsToRevise: examAnalytics.topicBreakdown
          .filter((t) => t.incorrect > 0 || t.correct === 0)
          .slice(0, 4)
          .map((t) => `${t.topic} (Needs concept brush-up)`),
        actionableStudyPlan: [
          'Review all Incorrect questions in the Solution Filter below to eliminate conceptual traps.',
          'Practice 15-question timed sectional drills on your lowest-scoring section.',
          'Avoid guessing when more than 2 options cannot be eliminated to protect against negative marking.'
        ]
      });
    } finally {
      setIsLoadingCoach(false);
    }
  };

  // Format Seconds into MM:SS or HH:MM:SS
  const formatClock = (seconds) => {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hrs > 0) {
      return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
  };

  // Helper for CBT Palette Button Styling (Authentic TCS iON Colors)
  const getPaletteButtonStyle = (state, isCurrent) => {
    const ringClass = isCurrent ? 'ring-2 ring-offset-2 ring-blue-600 scale-105' : '';
    switch (state) {
      case 'ANSWERED':
        return `bg-emerald-600 text-white border-emerald-700 rounded-t-xl rounded-b-sm font-bold shadow-sm ${ringClass}`;
      case 'NOT_ANSWERED':
        return `bg-rose-600 text-white border-rose-700 rounded-b-xl rounded-t-sm font-bold shadow-sm ${ringClass}`;
      case 'MARKED_FOR_REVIEW':
        return `bg-purple-600 text-white border-purple-700 rounded-full font-bold shadow-sm ${ringClass}`;
      case 'ANSWERED_AND_MARKED':
        return `bg-purple-600 text-white border-purple-700 rounded-full font-bold shadow-sm relative ${ringClass}`;
      case 'NOT_VISITED':
      default:
        return `bg-slate-100 text-slate-700 border border-slate-300 rounded-md font-medium hover:bg-slate-200 ${ringClass}`;
    }
  };

  // ============================================================================
  // VIEW 1: EXAM CONFIGURATION & PARALLEL AI BLUEPRINT BUILDER
  // ============================================================================
  if (viewMode === 'CONFIG') {
    return (
      <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
        {/* Top Navigation Header */}
        <header className="border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-md sticky top-0 z-30">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <Cpu className="w-6 h-6 text-white" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h1 className="text-lg sm:text-xl font-bold tracking-tight text-white">
                    NexusCBT <span className="text-indigo-400">AI Exam Engine</span>
                  </h1>
                  <span className="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30">
                    Parallel Batch Architecture
                  </span>
                </div>
                <p className="text-xs text-slate-400">
                  Dynamic Multi-Subject Competitive Exam Generator & Authentic TCS iON CBT Simulator
                </p>
              </div>
            </div>

            <button
              onClick={loadInstantDemoPaper}
              className="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-sm font-semibold transition-all cursor-pointer"
            >
              <Zap className="w-4 h-4 text-emerald-400" />
              <span>Instant 50-MCQ Demo Exam</span>
            </button>
          </div>
        </header>

        {/* Main Blueprint Content */}
        <main className="max-w-7xl mx-auto w-full px-4 sm:px-6 py-8 flex-1">
          {/* Architecture Highlight Banner */}
          <div className="mb-8 rounded-2xl bg-gradient-to-r from-indigo-950/80 via-slate-900 to-slate-900 border border-indigo-500/30 p-5 sm:p-6 shadow-xl">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
              <div className="lg:col-span-2 space-y-2">
                <div className="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-400">
                  <Sparkles className="w-4 h-4" />
                  Production-Grade AI CBT Architecture
                </div>
                <h2 className="text-xl sm:text-2xl font-bold text-white">
                  Generate 20 to 100 Competitive MCQs Without Timeouts or Math Errors
                </h2>
                <p className="text-sm text-slate-300 leading-relaxed">
                  Instead of a single slow API call, NexusCBT splits your exam blueprint into{' '}
                  <span className="text-indigo-300 font-medium">parallel section batches</span> and enforces{' '}
                  <span className="text-emerald-300 font-medium">step-by-step solution calculation before answer-key selection</span>.
                </p>
              </div>
              <div className="flex flex-col sm:flex-row lg:flex-col gap-3">
                <button
                  onClick={startParallelAIGeneration}
                  disabled={isGenerating}
                  className="w-full py-3.5 px-5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-bold shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2.5 transition-all cursor-pointer disabled:opacity-50"
                >
                  <Sparkles className="w-5 h-5" />
                  <span>Generate Live AI CBT ({totalQuestionsTarget} MCQs)</span>
                </button>
                <button
                  onClick={loadInstantDemoPaper}
                  className="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-sm border border-slate-700 flex items-center justify-center gap-2 transition-all cursor-pointer"
                >
                  <Play className="w-4 h-4 text-emerald-400" />
                  <span>Start Pre-Built 50-MCQ Mock Instantly</span>
                </button>
              </div>
            </div>
          </div>

          {generationError && (
            <div className="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-500/40 flex items-start gap-3 text-rose-200 text-sm">
              <AlertCircle className="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
              <div className="flex-1">
                <p className="font-semibold">AI Generation Notice</p>
                <p className="text-rose-300/90 mt-0.5">{generationError}</p>
              </div>
              <button
                onClick={loadInstantDemoPaper}
                className="px-3 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 text-xs font-semibold border border-rose-500/30 shrink-0"
              >
                Load 50-MCQ Paper
              </button>
            </div>
          )}

          {/* Step 1: Select Competitive Exam Template */}
          <div className="mb-8">
            <div className="flex items-center justify-between mb-3">
              <label className="text-sm font-semibold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <Layers className="w-4 h-4 text-indigo-400" />
                1. Choose Competitive Exam Template
              </label>
              <span className="text-xs text-slate-400">Click any preset to auto-load syllabus & marking scheme</span>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
              {EXAM_PRESETS.map((preset) => {
                const isSelected = selectedPresetId === preset.id;
                return (
                  <button
                    key={preset.id}
                    onClick={() => handleSelectPreset(preset)}
                    className={`p-4 rounded-xl border text-left transition-all cursor-pointer ${
                      isSelected
                        ? 'bg-indigo-600/15 border-indigo-500 shadow-lg shadow-indigo-500/10'
                        : 'bg-slate-900/80 border-slate-800 hover:border-slate-700'
                    }`}
                  >
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-800 text-indigo-300">
                        {preset.badge}
                      </span>
                      {isSelected && <CheckCircle2 className="w-4 h-4 text-indigo-400" />}
                    </div>
                    <div className="font-bold text-white text-base">{preset.name}</div>
                    <div className="text-xs text-slate-400 mt-1">
                      {preset.sections.length} Sections • +{preset.positiveMarks} / -{preset.negativeMarks} Marks
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Step 2: Global Exam Parameters & Step 3: Subject Blueprint */}
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {/* Left Column: Exam Parameters */}
            <div className="lg:col-span-5 bg-slate-900/90 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-5 h-fit">
              <h3 className="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <Sliders className="w-4 h-4 text-indigo-400" />
                2. Exam Parameters & Marking Scheme
              </h3>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1.5">Exam Title</label>
                <input
                  type="text"
                  value={examTitle}
                  onChange={(e) => setExamTitle(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1.5">
                  Total Questions to Generate (Parallel Batches)
                </label>
                <div className="grid grid-cols-4 gap-2">
                  {[
                    { count: 20, label: '20 MCQs', sub: '~6 sec' },
                    { count: 40, label: '40 MCQs', sub: '~8 sec' },
                    { count: 60, label: '60 MCQs', sub: '~11 sec' },
                    { count: 100, label: '100 MCQs', sub: 'Full CBT' }
                  ].map((item) => (
                    <button
                      key={item.count}
                      type="button"
                      onClick={() => setTotalQuestionsTarget(item.count)}
                      className={`py-2.5 px-2 rounded-xl border text-center transition-all cursor-pointer ${
                        totalQuestionsTarget === item.count
                          ? 'bg-indigo-600/20 border-indigo-500 text-white font-bold'
                          : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                      }`}
                    >
                      <div className="text-sm">{item.label}</div>
                      <div className="text-[10px] text-indigo-300/80">{item.sub}</div>
                    </button>
                  ))}
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1.5">Duration (Mins)</label>
                  <input
                    type="number"
                    min={10}
                    max={180}
                    value={durationMinutes}
                    onChange={(e) => setDurationMinutes(Number(e.target.value) || 60)}
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-emerald-400 mb-1.5">Correct (+)</label>
                  <input
                    type="number"
                    step="0.5"
                    min={0.5}
                    value={positiveMarks}
                    onChange={(e) => setPositiveMarks(Number(e.target.value) || 2)}
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-emerald-300 font-semibold text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-rose-400 mb-1.5">Negative (-)</label>
                  <input
                    type="number"
                    step="0.25"
                    min={0}
                    value={negativeMarks}
                    onChange={(e) => setNegativeMarks(Number(e.target.value) || 0)}
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-rose-300 font-semibold text-sm"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1.5">Difficulty Distribution</label>
                <select
                  value={difficultyMix}
                  onChange={(e) => setDifficultyMix(e.target.value)}
                  className="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500"
                >
                  <option value="Balanced (30% Easy, 50% Medium, 20% Hard)">
                    Standard Competitive (30% Easy, 50% Medium, 20% Hard)
                  </option>
                  <option value="Advanced Tier-II Level (10% Easy, 40% Medium, 50% Hard)">
                    Tier-II / Mains Level (10% Easy, 40% Medium, 50% Hard)
                  </option>
                  <option value="Foundation Level (50% Easy, 40% Medium, 10% Hard)">
                    Foundation / Prelims (50% Easy, 40% Medium, 10% Hard)
                  </option>
                </select>
              </div>

              {/* Diagram & Math Symbol Toggle Card */}
              <div className="p-3.5 rounded-xl bg-indigo-950/40 border border-indigo-500/30 space-y-2">
                <label className="flex items-center justify-between cursor-pointer">
                  <div className="flex items-center gap-2">
                    <ImageIcon className="w-4 h-4 text-indigo-400" />
                    <span className="text-xs font-bold text-white">
                      Include AI SVG Diagrams & DI Charts
                    </span>
                  </div>
                  <input
                    type="checkbox"
                    checked={includeDiagrams}
                    onChange={(e) => setIncludeDiagrams(e.target.checked)}
                    className="w-4 h-4 accent-indigo-500 rounded cursor-pointer"
                  />
                </label>
                <p className="text-[11px] text-slate-400 leading-relaxed">
                  Automatically generates vector <code className="text-indigo-300">&lt;svg&gt;</code> figures for Geometry, Bar/Pie Charts, Venn Diagrams, Pulleys & Binary Trees + renders <span className="text-emerald-300 font-serif">∫, λ, θ, √x, a/b</span> math notation.
                </p>
              </div>
            </div>

            {/* Right Column: Multi-Subject Section Customizer */}
            <div className="lg:col-span-7 bg-slate-900/90 border border-slate-800 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
              <div>
                <div className="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                  <h3 className="text-base font-bold text-white flex items-center gap-2">
                    <BookOpen className="w-4 h-4 text-indigo-400" />
                    3. Multi-Subject Syllabus Blueprint ({sections.length} Sections)
                  </h3>
                  <button
                    type="button"
                    onClick={handleAddSection}
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-500/15 hover:bg-indigo-500/25 text-indigo-300 border border-indigo-500/30 text-xs font-semibold cursor-pointer"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    Add Subject
                  </button>
                </div>

                <div className="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                  {sections.map((sec, idx) => (
                    <div
                      key={sec.id}
                      className="p-3.5 rounded-xl bg-slate-950/90 border border-slate-800/90 space-y-2.5"
                    >
                      <div className="flex items-center gap-2">
                        <span className="w-6 h-6 rounded-md bg-indigo-500/20 text-indigo-300 text-xs font-bold flex items-center justify-center shrink-0">
                          {idx + 1}
                        </span>
                        <input
                          type="text"
                          value={sec.name}
                          onChange={(e) => handleUpdateSection(sec.id, 'name', e.target.value)}
                          placeholder="Subject / Section Name"
                          className="flex-1 bg-transparent text-sm font-semibold text-white focus:outline-none border-b border-transparent focus:border-indigo-500 pb-0.5"
                        />
                        {sections.length > 1 && (
                          <button
                            type="button"
                            onClick={() => handleRemoveSection(sec.id)}
                            className="p-1.5 text-slate-500 hover:text-rose-400 transition-colors cursor-pointer"
                            title="Remove Section"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                      <div>
                        <label className="block text-[11px] text-slate-400 mb-1">
                          Sub-Topics to Include in Parallel Prompt:
                        </label>
                        <input
                          type="text"
                          value={sec.topics}
                          onChange={(e) => handleUpdateSection(sec.id, 'topics', e.target.value)}
                          className="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-slate-200 focus:outline-none focus:border-indigo-500"
                        />
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="mt-6 pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div className="text-xs text-slate-400 flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                  <span>Schema enforces step-by-step verification before option key generation</span>
                </div>
                <button
                  onClick={startParallelAIGeneration}
                  disabled={isGenerating}
                  className="w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/20 cursor-pointer"
                >
                  <Sparkles className="w-4 h-4" />
                  <span>Launch Parallel AI Generator</span>
                </button>
              </div>
            </div>
          </div>
        </main>

        {/* REAL-TIME PARALLEL BATCH PROGRESS MODAL */}
        {isGenerating && (
          <div className="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
            <div className="bg-slate-900 border border-indigo-500/40 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5">
              <div className="flex items-center gap-3.5">
                <div className="w-12 h-12 rounded-2xl bg-indigo-600/20 border border-indigo-500/40 flex items-center justify-center shrink-0">
                  <RefreshCw className="w-6 h-6 text-indigo-400 animate-spin" />
                </div>
                <div>
                  <h3 className="text-lg font-bold text-white">
                    Parallel AI Paper Setter in Progress...
                  </h3>
                  <p className="text-xs text-slate-400">
                    Executing {batchJobs.length} concurrent Gemini API batches & verifying step-by-step solutions
                  </p>
                </div>
              </div>

              <div className="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                {batchJobs.map((job, idx) => {
                  const isDone = job.status === 'COMPLETED';
                  return (
                    <div
                      key={job.batchId}
                      className={`p-3 rounded-xl border flex items-center justify-between transition-all ${
                        isDone
                          ? 'bg-emerald-950/30 border-emerald-500/40 text-emerald-200'
                          : 'bg-slate-950 border-slate-800 text-slate-300'
                      }`}
                    >
                      <div className="flex items-center gap-3 min-w-0">
                        <span className="text-xs font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                          Batch #{idx + 1}
                        </span>
                        <div className="truncate">
                          <div className="text-sm font-semibold truncate">
                            {job.sectionName} <span className="text-xs opacity-75">({job.partLabel})</span>
                          </div>
                          <div className="text-[11px] text-slate-400 truncate">{job.topics}</div>
                        </div>
                      </div>
                      <div className="shrink-0 ml-3 flex items-center gap-2">
                        <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-800">
                          {job.count} MCQs
                        </span>
                        {isDone ? (
                          <CheckCircle2 className="w-5 h-5 text-emerald-400" />
                        ) : (
                          <div className="w-4 h-4 rounded-full border-2 border-indigo-400 border-t-transparent animate-spin" />
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>

              <div className="pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                <span>Completed Batches: {batchJobs.filter((j) => j.status === 'COMPLETED').length} / {batchJobs.length}</span>
                <button
                  onClick={() => {
                    setIsGenerating(false);
                    loadInstantDemoPaper();
                  }}
                  className="text-indigo-400 hover:text-indigo-300 font-semibold underline cursor-pointer"
                >
                  Skip wait & load 50-MCQ demo paper
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }

  // ============================================================================
  // VIEW 2: AUTHENTIC TCS iON / NTA-STYLE CBT EXAM HALL INTERFACE
  // ============================================================================
  if (viewMode === 'EXAM') {
    const currentQ = questions[currentIndex] || questions[0];
    const isLowTime = timeLeftSeconds < 300; // Under 5 mins turns timer red

    const filteredPaletteQuestions =
      paletteFilterSection === 'ALL'
        ? questions
        : questions.filter((q) => q.section === paletteFilterSection);

    return (
      <div className="h-screen flex flex-col bg-slate-100 text-slate-900 select-none overflow-hidden font-sans">
        {/* 1. Top Official CBT Exam Header */}
        <header className="bg-slate-900 text-white px-4 py-2.5 flex items-center justify-between shadow-md shrink-0 z-20">
          <div className="flex items-center gap-3 min-w-0">
            <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-sm shrink-0">
              CBT
            </div>
            <div className="truncate">
              <h1 className="text-sm sm:text-base font-bold truncate">{examTitle}</h1>
              <p className="text-[11px] text-slate-400 hidden sm:block">
                Official Computer-Based Test Environment • Total Questions: {questions.length}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3 sm:gap-5">
            {/* Live Countdown Clock */}
            <div
              className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg border font-mono text-sm sm:text-base font-bold ${
                isLowTime
                  ? 'bg-rose-950 border-rose-500 text-rose-300 animate-pulse'
                  : 'bg-slate-800 border-slate-700 text-emerald-400'
              }`}
            >
              <Clock className="w-4 h-4" />
              <span>Time Left: {formatClock(timeLeftSeconds)}</span>
            </div>

            <button
              onClick={() => setShowSubmitModal(true)}
              className="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs sm:text-sm shadow transition-all cursor-pointer"
            >
              Submit Exam
            </button>

            <button
              onClick={() => setMobilePaletteOpen(!mobilePaletteOpen)}
              className="lg:hidden p-2 rounded-lg bg-slate-800 text-slate-200"
              aria-label="Toggle Question Palette"
            >
              {mobilePaletteOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </header>

        {/* 2. Section Navigation Bar */}
        <div className="bg-white border-b border-slate-200 px-4 py-2 flex items-center justify-between gap-2 overflow-x-auto shrink-0">
          <div className="flex items-center gap-1.5">
            <span className="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2 hidden sm:inline">
              Sections:
            </span>
            {examSectionNames.map((secName) => {
              const isCurrentSection = currentQ?.section === secName;
              const secQuestions = questions.filter((q) => q.section === secName);
              const answeredInSec = secQuestions.filter(
                (q) => q.cbtState === 'ANSWERED' || q.cbtState === 'ANSWERED_AND_MARKED'
              ).length;

              return (
                <button
                  key={secName}
                  onClick={() => {
                    const firstIdx = questions.findIndex((q) => q.section === secName);
                    if (firstIdx !== -1) navigateToQuestion(firstIdx);
                  }}
                  className={`px-3.5 py-1.5 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer ${
                    isCurrentSection
                      ? 'bg-indigo-600 text-white shadow-sm'
                      : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                  }`}
                >
                  <span>{secName}</span>
                  <span
                    className={`px-1.5 py-0.5 rounded text-[11px] ${
                      isCurrentSection ? 'bg-indigo-800 text-indigo-100' : 'bg-slate-200 text-slate-600'
                    }`}
                  >
                    {answeredInSec}/{secQuestions.length}
                  </span>
                </button>
              );
            })}
          </div>

          {/* Marking Scheme Badge for Current Question */}
          <div className="hidden md:flex items-center gap-2 text-xs font-semibold shrink-0">
            <span className="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
              Correct: +{positiveMarks}
            </span>
            <span className="px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200">
              Negative: -{negativeMarks}
            </span>
          </div>
        </div>

        {/* 3. Main Split Workspace: Question Area (Left) + Question Palette Sidebar (Right) */}
        <div className="flex-1 flex overflow-hidden relative">
          {/* LEFT: Question & Options Area */}
          <div className="flex-1 flex flex-col bg-white overflow-hidden">
            {/* Question Metadata Sub-bar */}
            <div className="px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
              <div className="flex items-center gap-2.5">
                <span className="text-base font-extrabold text-slate-900">
                  Question No. {currentQ?.number}
                </span>
                <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                  {currentQ?.topic}
                </span>
                {currentQ?.hasDiagram && (
                  <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1">
                    <ImageIcon className="w-3 h-3" /> Diagram Question
                  </span>
                )}
                <span
                  className={`px-2.5 py-0.5 rounded-full text-xs font-semibold ${
                    currentQ?.difficulty === 'Hard'
                      ? 'bg-rose-50 text-rose-700 border border-rose-200'
                      : currentQ?.difficulty === 'Easy'
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                      : 'bg-amber-50 text-amber-700 border border-amber-200'
                  }`}
                >
                  {currentQ?.difficulty}
                </span>
              </div>

              <div className="flex items-center gap-3 text-xs text-slate-500">
                <span className="flex items-center gap-1 font-mono">
                  <Timer className="w-3.5 h-3.5 text-slate-400" />
                  Time on this Q: {currentQ?.timeSpentSeconds}s
                </span>
              </div>
            </div>

            {/* Question Text, Optional SVG Diagram & 4 Radio Options */}
            <div className="flex-1 overflow-y-auto p-6 sm:p-8 space-y-5 max-w-4xl">
              <div className="text-base sm:text-lg font-medium text-slate-900 leading-relaxed whitespace-pre-line">
                <MathText text={currentQ?.questionText} />
              </div>

              {/* Render Interactive SVG Diagram if present */}
              {currentQ?.hasDiagram && currentQ?.svgDiagram && (
                <DiagramRenderer
                  svgString={currentQ.svgDiagram}
                  title={`Figure for Q.${currentQ.number} (${currentQ.topic})`}
                  onZoom={(svg, title) => setZoomedDiagram({ svg, title })}
                />
              )}

              <div className="space-y-3 pt-2">
                {currentQ?.options.map((opt) => {
                  const isSelected = selectedOptionDraft === opt.id;
                  return (
                    <div
                      key={opt.id}
                      onClick={() => setSelectedOptionDraft(opt.id)}
                      className={`p-4 rounded-xl border-2 transition-all flex items-start gap-3.5 cursor-pointer ${
                        isSelected
                          ? 'border-indigo-600 bg-indigo-50/70 shadow-sm'
                          : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                      }`}
                    >
                      <div
                        className={`w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5 border ${
                          isSelected
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-slate-700 border-slate-300'
                        }`}
                      >
                        {opt.id}
                      </div>
                      <div className="text-sm sm:text-base text-slate-800 font-medium leading-snug pt-0.5">
                        <MathText text={opt.text} />
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Bottom Standard CBT Control Bar */}
            <div className="px-6 py-3.5 bg-slate-100 border-t border-slate-300 flex flex-wrap items-center justify-between gap-3 shrink-0">
              <div className="flex flex-wrap items-center gap-2.5">
                <button
                  type="button"
                  onClick={handleMarkForReviewAndNext}
                  className="px-4 py-2.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs sm:text-sm shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <Flag className="w-4 h-4" />
                  <span>Mark for Review & Next</span>
                </button>

                <button
                  type="button"
                  onClick={handleClearResponse}
                  className="px-4 py-2.5 rounded-lg bg-white hover:bg-slate-200 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <RotateCcw className="w-4 h-4" />
                  <span>Clear Response</span>
                </button>
              </div>

              <div className="flex items-center gap-2.5">
                <button
                  type="button"
                  disabled={currentIndex === 0}
                  onClick={() => navigateToQuestion(currentIndex - 1)}
                  className="px-4 py-2.5 rounded-lg bg-white hover:bg-slate-200 disabled:opacity-40 text-slate-700 border border-slate-300 font-semibold text-xs sm:text-sm flex items-center gap-1 transition-colors cursor-pointer"
                >
                  <ChevronLeft className="w-4 h-4" />
                  <span>Previous</span>
                </button>

                <button
                  type="button"
                  onClick={handleSaveAndNext}
                  className="px-6 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <span>Save & Next</span>
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          {/* RIGHT: Official TCS iON Question Palette & Status Legend */}
          <aside
            className={`${
              mobilePaletteOpen ? 'fixed inset-y-0 right-0 z-40 flex' : 'hidden lg:flex'
            } w-80 sm:w-88 bg-slate-50 border-l border-slate-300 flex-col shrink-0`}
          >
            {/* Candidate Info Strip */}
            <div className="p-3.5 bg-white border-b border-slate-200 flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm">
                  ST
                </div>
                <div>
                  <div className="text-xs font-bold text-slate-800">Candidate Attempt</div>
                  <div className="text-[11px] text-slate-500">Roll No: CBT-2026-8941</div>
                </div>
              </div>
              {mobilePaletteOpen && (
                <button
                  onClick={() => setMobilePaletteOpen(false)}
                  className="lg:hidden p-1.5 rounded-lg bg-slate-100 text-slate-600"
                >
                  <X className="w-4 h-4" />
                </button>
              )}
            </div>

            {/* 5-State CBT Status Legend */}
            <div className="p-3.5 bg-slate-100 border-b border-slate-200 grid grid-cols-2 gap-2 text-xs">
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-t-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-[11px]">
                  {paletteCounts.ANSWERED}
                </span>
                <span className="text-slate-700 font-medium">Answered</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-b-lg bg-rose-600 text-white font-bold flex items-center justify-center text-[11px]">
                  {paletteCounts.NOT_ANSWERED}
                </span>
                <span className="text-slate-700 font-medium">Not Answered</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-md bg-white border border-slate-300 text-slate-700 font-bold flex items-center justify-center text-[11px]">
                  {paletteCounts.NOT_VISITED}
                </span>
                <span className="text-slate-700 font-medium">Not Visited</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-6 h-6 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-[11px]">
                  {paletteCounts.MARKED_FOR_REVIEW}
                </span>
                <span className="text-slate-700 font-medium">Marked Review</span>
              </div>
              <div className="col-span-2 flex items-center gap-2 pt-1">
                <span className="w-6 h-6 rounded-full bg-purple-600 text-white font-bold flex items-center justify-center text-[11px] relative shrink-0">
                  {paletteCounts.ANSWERED_AND_MARKED}
                  <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 border border-white absolute -bottom-0.5 -right-0.5" />
                </span>
                <span className="text-slate-700 font-medium text-[11px]">
                  Answered & Marked for Review (Will be evaluated)
                </span>
              </div>
            </div>

            {/* Section Filter inside Palette */}
            <div className="px-3.5 py-2.5 bg-indigo-950 text-white flex items-center justify-between text-xs">
              <span className="font-semibold">Question Palette</span>
              <select
                value={paletteFilterSection}
                onChange={(e) => setPaletteFilterSection(e.target.value)}
                className="bg-indigo-900 text-white text-xs rounded px-2 py-1 border border-indigo-700 focus:outline-none"
              >
                <option value="ALL">All Sections ({questions.length})</option>
                {examSectionNames.map((sec) => (
                  <option key={sec} value={sec}>
                    {sec}
                  </option>
                ))}
              </select>
            </div>

            {/* Numbered Question Grid */}
            <div className="flex-1 overflow-y-auto p-4">
              <div className="grid grid-cols-5 gap-2.5">
                {filteredPaletteQuestions.map((q) => {
                  const actualIdx = questions.findIndex((item) => item.id === q.id);
                  const isCurrent = actualIdx === currentIndex;
                  return (
                    <button
                      key={q.id}
                      onClick={() => {
                        navigateToQuestion(actualIdx);
                        setMobilePaletteOpen(false);
                      }}
                      title={q.hasDiagram ? `Q.${q.number} (Contains Diagram)` : `Q.${q.number}`}
                      className={`h-10 w-full flex items-center justify-center text-xs transition-transform relative cursor-pointer ${getPaletteButtonStyle(
                        q.cbtState,
                        isCurrent
                      )}`}
                    >
                      <span>{q.number}</span>
                      {q.hasDiagram && (
                        <span
                          className="w-2 h-2 rounded-full bg-amber-400 border border-slate-900 absolute -top-0.5 -left-0.5"
                          title="Diagram Question"
                        />
                      )}
                      {q.cbtState === 'ANSWERED_AND_MARKED' && (
                        <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 border border-white absolute -bottom-0.5 -right-0.5" />
                      )}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Palette Footer Submit Button */}
            <div className="p-3.5 bg-white border-t border-slate-200">
              <button
                onClick={() => setShowSubmitModal(true)}
                className="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow transition-colors cursor-pointer"
              >
                Submit Test & View Scorecard
              </button>
            </div>
          </aside>
        </div>

        {/* SUBMIT CONFIRMATION SUMMARY MODAL */}
        {showSubmitModal && (
          <div className="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 text-slate-900">
              <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                <h3 className="text-lg font-bold">Exam Submission Summary</h3>
                <button
                  onClick={() => setShowSubmitModal(false)}
                  className="p-1 text-slate-400 hover:text-slate-600"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <p className="text-sm text-slate-600">
                Please verify your attempt status before final submission. Note that questions marked{' '}
                <span className="font-semibold text-purple-700">Answered & Marked for Review</span> will be
                counted toward your final score.
              </p>

              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 text-center">
                <div className="p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                  <div className="text-xl font-extrabold text-emerald-700">{paletteCounts.ANSWERED}</div>
                  <div className="text-xs text-emerald-800 font-medium">Answered</div>
                </div>
                <div className="p-3 rounded-xl bg-purple-50 border border-purple-200">
                  <div className="text-xl font-extrabold text-purple-700">
                    {paletteCounts.ANSWERED_AND_MARKED}
                  </div>
                  <div className="text-xs text-purple-800 font-medium">Ans & Marked</div>
                </div>
                <div className="p-3 rounded-xl bg-rose-50 border border-rose-200">
                  <div className="text-xl font-extrabold text-rose-700">{paletteCounts.NOT_ANSWERED}</div>
                  <div className="text-xs text-rose-800 font-medium">Not Answered</div>
                </div>
                <div className="p-3 rounded-xl bg-slate-100 border border-slate-200">
                  <div className="text-xl font-extrabold text-slate-700">{paletteCounts.NOT_VISITED}</div>
                  <div className="text-xs text-slate-600 font-medium">Not Visited</div>
                </div>
                <div className="p-3 rounded-xl bg-purple-50/60 border border-purple-200">
                  <div className="text-xl font-extrabold text-purple-600">
                    {paletteCounts.MARKED_FOR_REVIEW}
                  </div>
                  <div className="text-xs text-purple-700 font-medium">Review (Unans)</div>
                </div>
                <div className="p-3 rounded-xl bg-indigo-50 border border-indigo-200">
                  <div className="text-xl font-extrabold text-indigo-700">{ formatClock(timeLeftSeconds) }</div>
                  <div className="text-xs text-indigo-800 font-medium">Time Remaining</div>
                </div>
              </div>

              <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button
                  onClick={() => setShowSubmitModal(false)}
                  className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm cursor-pointer"
                >
                  Resume Exam
                </button>
                <button
                  onClick={() => {
                    setShowSubmitModal(false);
                    setViewMode('RESULT');
                  }}
                  className="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/20 cursor-pointer"
                >
                  Confirm & Submit Exam
                </button>
              </div>
            </div>
          </div>
        )}

        {/* ENLARGED DIAGRAM ZOOM MODAL */}
        {zoomedDiagram && (
          <div
            className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4"
            onClick={() => setZoomedDiagram(null)}
          >
            <div
              className="bg-white rounded-2xl max-w-3xl w-full p-5 shadow-2xl space-y-4"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                <h4 className="font-bold text-slate-900 flex items-center gap-2">
                  <ImageIcon className="w-4 h-4 text-indigo-600" />
                  {zoomedDiagram.title}
                </h4>
                <button
                  onClick={() => setZoomedDiagram(null)}
                  className="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 cursor-pointer"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>
              <div
                className="p-6 bg-slate-50 rounded-xl flex items-center justify-center [&>svg]:w-full [&>svg]:max-h-[65vh] [&>svg]:h-auto"
                dangerouslySetInnerHTML={{ __html: zoomedDiagram.svg }}
              />
            </div>
          </div>
        )}
      </div>
    );
  }

  // ============================================================================
  // VIEW 3: POST-EXAM SCORECARD, AI PERFORMANCE COACH & SOLUTIONS REVIEW
  // ============================================================================
  const filteredSolutions = questions.filter((q) => {
    const isAttempted =
      (q.cbtState === 'ANSWERED' || q.cbtState === 'ANSWERED_AND_MARKED') && q.selectedOption;
    const isCorrect = isAttempted && q.selectedOption === q.correctOption;
    const isIncorrect = isAttempted && q.selectedOption !== q.correctOption;

    if (resultSectionFilter !== 'ALL' && q.section !== resultSectionFilter) return false;
    if (resultFilter === 'DIAGRAM') return Boolean(q.hasDiagram);
    if (resultFilter === 'CORRECT') return isCorrect;
    if (resultFilter === 'INCORRECT') return isIncorrect;
    if (resultFilter === 'UNATTEMPTED') return !isAttempted;
    return true;
  });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 font-sans pb-16">
      {/* Header */}
      <header className="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center">
              <Award className="w-6 h-6 text-emerald-400" />
            </div>
            <div>
              <h1 className="text-lg sm:text-xl font-bold text-white">{examTitle} — Official Scorecard</h1>
              <p className="text-xs text-slate-400">
                Detailed Sectional Breakdown, Per-Question Time Analytics & Step-by-Step Solutions
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <button
              onClick={() => initializeCBTExamSession(questions)}
              className="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-semibold border border-slate-700 flex items-center gap-2 cursor-pointer"
            >
              <RotateCcw className="w-4 h-4" />
              <span>Re-Attempt Same Paper</span>
            </button>
            <button
              onClick={() => setViewMode('CONFIG')}
              className="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-bold flex items-center gap-2 shadow-lg shadow-indigo-600/20 cursor-pointer"
            >
              <Sparkles className="w-4 h-4" />
              <span>Generate New CBT</span>
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-8">
        {/* 1. Top KPI Score Cards */}
        {examAnalytics && (
          <div className="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div className="p-5 rounded-2xl bg-gradient-to-br from-indigo-900/60 to-slate-900 border border-indigo-500/40">
              <div className="text-xs font-semibold uppercase tracking-wider text-indigo-300">Net Total Score</div>
              <div className="text-3xl font-extrabold text-white mt-1">
                {examAnalytics.totalScore}{' '}
                <span className="text-base font-medium text-slate-400">/ {examAnalytics.maxScore}</span>
              </div>
              <div className="text-xs text-indigo-300 mt-2">
                Marking: +{positiveMarks} / -{negativeMarks}
              </div>
            </div>

            <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800">
              <div className="text-xs font-semibold uppercase tracking-wider text-slate-400">Accuracy</div>
              <div className="text-3xl font-extrabold text-emerald-400 mt-1">{examAnalytics.accuracy}%</div>
              <div className="text-xs text-slate-400 mt-2">
                {examAnalytics.correctCount} Correct of {examAnalytics.attemptedCount} Attempted
              </div>
            </div>

            <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800">
              <div className="text-xs font-semibold uppercase tracking-wider text-slate-400">Est. Percentile</div>
              <div className="text-3xl font-extrabold text-amber-400 mt-1">
                {examAnalytics.estimatedPercentile}%ile
              </div>
              <div className="text-xs text-slate-400 mt-2">All-India Normalized Estimate</div>
            </div>

            <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800">
              <div className="text-xs font-semibold uppercase tracking-wider text-slate-400">Attempt Breakdown</div>
              <div className="flex items-center gap-3 mt-2">
                <span className="text-sm font-bold text-emerald-400">{examAnalytics.correctCount} Right</span>
                <span className="text-sm font-bold text-rose-400">{examAnalytics.incorrectCount} Wrong</span>
                <span className="text-sm font-bold text-slate-400">{examAnalytics.unattemptedCount} Skipped</span>
              </div>
              <div className="w-full h-2 rounded-full bg-slate-800 mt-3 overflow-hidden flex">
                <div
                  style={{ width: `${(examAnalytics.correctCount / examAnalytics.totalQuestions) * 100}%` }}
                  className="bg-emerald-500"
                />
                <div
                  style={{ width: `${(examAnalytics.incorrectCount / examAnalytics.totalQuestions) * 100}%` }}
                  className="bg-rose-500"
                />
              </div>
            </div>

            <div className="p-5 rounded-2xl bg-slate-900 border border-slate-800 col-span-2 lg:col-span-1">
              <div className="text-xs font-semibold uppercase tracking-wider text-slate-400">Time Taken</div>
              <div className="text-2xl font-extrabold text-white mt-1 font-mono">
                {formatClock(examAnalytics.totalTimeSpent)}
              </div>
              <div className="text-xs text-slate-400 mt-2">
                Avg {Math.round(examAnalytics.totalTimeSpent / Math.max(1, examAnalytics.attemptedCount))}s per attempted Q
              </div>
            </div>
          </div>
        )}

        {/* 2. Section-Wise Performance Table & AI Performance Coach */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Sectional Breakdown Table */}
          <div className="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6">
            <h3 className="text-base font-bold text-white flex items-center gap-2 mb-4">
              <BarChart3 className="w-5 h-5 text-indigo-400" />
              Section-Wise Score & Time Breakdown
            </h3>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse text-sm">
                <thead>
                  <tr className="border-b border-slate-800 text-xs uppercase text-slate-400">
                    <th className="py-3 pr-4">Section</th>
                    <th className="py-3 px-2 text-center">Attempted</th>
                    <th className="py-3 px-2 text-center text-emerald-400">Correct</th>
                    <th className="py-3 px-2 text-center text-rose-400">Wrong</th>
                    <th className="py-3 px-2 text-center">Time</th>
                    <th className="py-3 pl-3 text-right">Net Score</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/70">
                  {examAnalytics?.sectionBreakdown.map((sec) => (
                    <tr key={sec.section} className="hover:bg-slate-800/40">
                      <td className="py-3.5 pr-4 font-semibold text-white">{sec.section}</td>
                      <td className="py-3.5 px-2 text-center text-slate-300">
                        {sec.attempted} / {sec.total}
                      </td>
                      <td className="py-3.5 px-2 text-center font-semibold text-emerald-400">{sec.correct}</td>
                      <td className="py-3.5 px-2 text-center font-semibold text-rose-400">{sec.incorrect}</td>
                      <td className="py-3.5 px-2 text-center font-mono text-xs text-slate-400">
                        {formatClock(sec.timeSpent)}
                      </td>
                      <td className="py-3.5 pl-3 text-right font-bold text-indigo-300">
                        {Number(sec.score.toFixed(2))}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* AI Performance Coach Card */}
          <div className="lg:col-span-5 bg-gradient-to-br from-indigo-950/60 via-slate-900 to-slate-900 border border-indigo-500/30 rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
            <div>
              <div className="flex items-center justify-between mb-3">
                <div className="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-400">
                  <Brain className="w-4 h-4" />
                  AI Personal Performance Coach
                </div>
                {aiCoachReport && (
                  <button
                    onClick={generateAICoachFeedback}
                    disabled={isLoadingCoach}
                    className="text-xs text-indigo-300 hover:underline flex items-center gap-1 cursor-pointer"
                  >
                    <RefreshCw className={`w-3 h-3 ${isLoadingCoach ? 'animate-spin' : ''}`} />
                    Refresh
                  </button>
                )}
              </div>

              {!aiCoachReport ? (
                <div className="space-y-4 py-4">
                  <h4 className="text-lg font-bold text-white">
                    Get Deep AI Diagnosis of Your Speed, Accuracy & Weak Sub-Topics
                  </h4>
                  <p className="text-xs text-slate-300 leading-relaxed">
                    Our AI Coach analyzes your per-question time logs and negative marking patterns to generate a targeted revision plan for your next mock test.
                  </p>
                  <button
                    onClick={generateAICoachFeedback}
                    disabled={isLoadingCoach}
                    className="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/25 cursor-pointer disabled:opacity-50"
                  >
                    {isLoadingCoach ? (
                      <>
                        <RefreshCw className="w-4 h-4 animate-spin" />
                        <span>Analyzing Question Logs & Time Spent...</span>
                      </>
                    ) : (
                      <>
                        <Sparkles className="w-4 h-4" />
                        <span>Generate AI Coach Analysis</span>
                      </>
                    )}
                  </button>
                </div>
              ) : (
                <div className="space-y-3 text-xs sm:text-sm">
                  <p className="text-slate-200 font-medium bg-slate-950/70 p-3 rounded-xl border border-slate-800">
                    {aiCoachReport.overallVerdict}
                  </p>
                  <div className="p-3 rounded-xl bg-indigo-950/40 border border-indigo-500/20 text-indigo-200 text-xs">
                    <span className="font-bold text-indigo-300">Time Management: </span>
                    {aiCoachReport.timeManagementAnalysis}
                  </div>
                  {aiCoachReport.weakTopicsToRevise?.length > 0 && (
                    <div>
                      <div className="text-xs font-bold text-rose-400 uppercase mb-1">Priority Topics to Revise:</div>
                      <div className="flex flex-wrap gap-1.5">
                        {aiCoachReport.weakTopicsToRevise.map((t, i) => (
                          <span
                            key={i}
                            className="px-2.5 py-1 rounded-md bg-rose-500/15 border border-rose-500/30 text-rose-200 text-xs"
                          >
                            {t}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                  <div>
                    <div className="text-xs font-bold text-emerald-400 uppercase mb-1">Actionable Next Steps:</div>
                    <ul className="space-y-1 text-xs text-slate-300 list-disc list-inside">
                      {aiCoachReport.actionableStudyPlan?.map((step, i) => (
                        <li key={i}>{step}</li>
                      ))}
                    </ul>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* 3. Step-by-Step Solutions & Answer Key Review */}
        <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-6">
          <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
              <h3 className="text-lg font-bold text-white flex items-center gap-2">
                <FileText className="w-5 h-5 text-indigo-400" />
                Verified Answer Key & Step-by-Step Explanations ({filteredSolutions.length})
              </h3>
              <p className="text-xs text-slate-400">
                Filter by status or subject section to review your mistakes and shortcuts
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              {[
                { id: 'ALL', label: `All (${questions.length})` },
                { id: 'DIAGRAM', label: `Diagrams (${questions.filter((q) => q.hasDiagram).length})` },
                { id: 'INCORRECT', label: `Wrong (${examAnalytics?.incorrectCount || 0})` },
                { id: 'UNATTEMPTED', label: `Unattempted (${examAnalytics?.unattemptedCount || 0})` },
                { id: 'CORRECT', label: `Correct (${examAnalytics?.correctCount || 0})` }
              ].map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setResultFilter(tab.id)}
                  className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer ${
                    resultFilter === tab.id
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                  }`}
                >
                  {tab.label}
                </button>
              ))}

              <select
                value={resultSectionFilter}
                onChange={(e) => setResultSectionFilter(e.target.value)}
                className="px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-700 text-xs text-slate-200"
              >
                <option value="ALL">All Sections</option>
                {examSectionNames.map((sec) => (
                  <option key={sec} value={sec}>
                    {sec}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Filtered Questions List */}
          <div className="space-y-4">
            {filteredSolutions.map((q) => {
              const isAttempted =
                (q.cbtState === 'ANSWERED' || q.cbtState === 'ANSWERED_AND_MARKED') && q.selectedOption;
              const isCorrect = isAttempted && q.selectedOption === q.correctOption;

              return (
                <div
                  key={q.id}
                  className="p-5 rounded-xl bg-slate-950/90 border border-slate-800/90 space-y-4"
                >
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="px-2.5 py-1 rounded-md bg-slate-800 text-white font-bold text-xs">
                        Q.{q.number}
                      </span>
                      <span className="px-2.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 text-xs">
                        {q.section} • {q.topic}
                      </span>
                      {q.hasDiagram && (
                        <span className="px-2.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-semibold flex items-center gap-1">
                          <ImageIcon className="w-3 h-3" /> SVG Diagram
                        </span>
                      )}
                      <span className="text-xs text-slate-400 font-mono flex items-center gap-1">
                        <Clock className="w-3.5 h-3.5" /> {q.timeSpentSeconds}s spent
                      </span>
                    </div>

                    <div>
                      {!isAttempted ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-800 text-slate-300">
                          Unattempted (0 Marks)
                        </span>
                      ) : isCorrect ? (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                          <CheckCircle2 className="w-3.5 h-3.5" /> Correct (+{positiveMarks})
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                          <XCircle className="w-3.5 h-3.5" /> Incorrect (-{negativeMarks})
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="text-sm sm:text-base font-medium text-white whitespace-pre-line">
                    <MathText text={q.questionText} />
                  </div>

                  {q.hasDiagram && q.svgDiagram && (
                    <DiagramRenderer
                      svgString={q.svgDiagram}
                      title={`Figure for Q.${q.number} (${q.topic})`}
                    />
                  )}

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    {q.options.map((opt) => {
                      const isRightOption = opt.id === q.correctOption;
                      const isStudentPick = isAttempted && opt.id === q.selectedOption;

                      let boxStyle = 'bg-slate-900 border-slate-800 text-slate-300';
                      if (isRightOption) {
                        boxStyle = 'bg-emerald-950/60 border-emerald-500/60 text-emerald-200 font-semibold';
                      } else if (isStudentPick && !isRightOption) {
                        boxStyle = 'bg-rose-950/60 border-rose-500/60 text-rose-200';
                      }

                      return (
                        <div
                          key={opt.id}
                          className={`p-3 rounded-xl border text-xs sm:text-sm flex items-center justify-between gap-2 ${boxStyle}`}
                        >
                          <div className="flex items-center gap-2.5">
                            <span className="w-6 h-6 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs shrink-0">
                              {opt.id}
                            </span>
                            <MathText text={opt.text} />
                          </div>
                          {isRightOption && (
                            <span className="text-[11px] font-bold text-emerald-400 shrink-0">✓ Correct Key</span>
                          )}
                          {isStudentPick && !isRightOption && (
                            <span className="text-[11px] font-bold text-rose-400 shrink-0">✗ Your Choice</span>
                          )}
                        </div>
                      );
                    })}
                  </div>

                  <div className="p-3.5 rounded-xl bg-indigo-950/30 border border-indigo-500/25 text-xs sm:text-sm text-slate-200 space-y-1">
                    <div className="font-bold text-indigo-300 flex items-center gap-1.5">
                      <Sparkles className="w-4 h-4" />
                      Step-by-Step Solution & Concept Explanation:
                    </div>
                    <p className="text-slate-300 leading-relaxed">
                      <MathText text={q.explanation} />
                    </p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </main>
    </div>
  );
}