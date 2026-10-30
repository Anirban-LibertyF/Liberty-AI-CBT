<?php
declare(strict_types=1);

/**
 * Turns LaTeX-style markup (\frac{a}{b}, \sqrt{x}, \int_{a}^{b}, Greek letters, x^2, ...)
 * into HTML. Port of the React <MathText /> engine; produces the same markup and classes.
 * All literal text is HTML-escaped, so the output is safe to print.
 */
final class MathRenderer
{
    private const SYMBOLS = [
        '\\alpha' => 'α', '\\beta' => 'β', '\\gamma' => 'γ', '\\Gamma' => 'Γ', '\\delta' => 'δ', '\\Delta' => 'Δ',
        '\\epsilon' => 'ε', '\\varepsilon' => 'ε', '\\zeta' => 'ζ', '\\eta' => 'η', '\\theta' => 'θ', '\\Theta' => 'Θ',
        '\\vartheta' => 'ϑ', '\\iota' => 'ι', '\\kappa' => 'κ', '\\lambda' => 'λ', '\\Lambda' => 'Λ', '\\mu' => 'μ',
        '\\nu' => 'ν', '\\xi' => 'ξ', '\\Xi' => 'Ξ', '\\pi' => 'π', '\\Pi' => 'Π', '\\rho' => 'ρ', '\\sigma' => 'σ',
        '\\Sigma' => 'Σ', '\\tau' => 'τ', '\\upsilon' => 'υ', '\\phi' => 'φ', '\\Phi' => 'Φ', '\\varphi' => 'ϕ',
        '\\chi' => 'χ', '\\psi' => 'ψ', '\\Psi' => 'Ψ', '\\omega' => 'ω', '\\Omega' => 'Ω',
        '\\int' => '∫', '\\iint' => '∬', '\\iiint' => '∭', '\\oint' => '∮', '\\sum' => '∑', '\\prod' => '∏',
        '\\infty' => '∞', '\\partial' => '∂', '\\nabla' => '∇', '\\pm' => '±', '\\mp' => '∓', '\\times' => '×',
        '\\div' => '÷', '\\cdot' => '·', '\\ast' => '∗', '\\circ' => '∘', '\\degree' => '°', '\\circC' => '°C',
        '\\leq' => '≤', '\\le' => '≤', '\\geq' => '≥', '\\ge' => '≥', '\\neq' => '≠', '\\ne' => '≠',
        '\\approx' => '≈', '\\equiv' => '≡', '\\cong' => '≅', '\\sim' => '∼', '\\propto' => '∝', '\\ll' => '≪',
        '\\gg' => '≫', '\\forall' => '∀', '\\exists' => '∃', '\\in' => '∈', '\\notin' => '∉', '\\subset' => '⊂',
        '\\subseteq' => '⊆', '\\cup' => '∪', '\\cap' => '∩', '\\emptyset' => '∅', '\\therefore' => '∴',
        '\\because' => '∵', '\\angle' => '∠', '\\triangle' => '△', '\\perp' => '⊥', '\\parallel' => '∥',
        '\\rightarrow' => '→', '\\to' => '→', '\\leftarrow' => '←', '\\leftrightarrow' => '↔', '\\Rightarrow' => '⇒',
        '\\Leftarrow' => '⇐', '\\Leftrightarrow' => '⇔', '\\uparrow' => '↑', '\\downarrow' => '↓',
        '\\sin' => 'sin', '\\cos' => 'cos', '\\tan' => 'tan', '\\cot' => 'cot', '\\sec' => 'sec', '\\csc' => 'csc',
        '\\log' => 'log', '\\ln' => 'ln', '\\lim' => 'lim', '\\max' => 'max', '\\min' => 'min', '\\det' => 'det',
        '\\gcd' => 'gcd', '\\lcm' => 'lcm', '\\mod' => 'mod',
        '\\left' => '', '\\right' => '', '\\,' => ' ', '\\;' => ' ', '\\quad' => '  ', '\\qquad' => '    ',
    ];

    private static ?string $symbolPattern = null;

    public static function render(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        return '<span class="math-rendered">' . self::parse($text, 'm') . '</span>';
    }

    /** Strip math delimiters, drop \text{...} wrappers, and swap commands for Unicode. */
    private static function normalize(string $raw): string
    {
        $text = str_replace(['$$', '$', '\\(', '\\)', '\\[', '\\]'], '', $raw);
        // \text{Revenue} -> Revenue (the React version left the braces in place)
        $text = preg_replace('/\\\\text\s*\{([^{}]*)\}/u', '$1', $text) ?? $text;

        if (self::$symbolPattern === null) {
            $keys = array_keys(self::SYMBOLS);
            usort($keys, fn ($a, $b) => strlen($b) <=> strlen($a)); // longest first: \varepsilon before \var..
            self::$symbolPattern = '/(?:' . implode('|', array_map(fn ($k) => preg_quote($k, '/'), $keys)) . ')(?![a-zA-Z])/u';
        }
        return preg_replace_callback(
            self::$symbolPattern,
            fn ($m) => self::SYMBOLS[$m[0]],
            $text
        ) ?? $text;
    }

    /** @param string[] $c characters (UTF-8 aware) */
    private static function startsWith(array $c, int $i, string $needle): bool
    {
        $n = mb_strlen($needle);
        return implode('', array_slice($c, $i, $n)) === $needle;
    }

    /** Balanced {...} group starting at $start; null if $c[$start] isn't "{". */
    private static function braces(array $c, int $start): ?array
    {
        if (($c[$start] ?? null) !== '{') {
            return null;
        }
        $depth = 0;
        $content = '';
        $n = count($c);
        for ($i = $start; $i < $n; $i++) {
            $ch = $c[$i];
            if ($ch === '{') {
                if ($depth > 0) {
                    $content .= $ch;
                }
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    return ['content' => $content, 'end' => $i];
                }
                $content .= $ch;
            } else {
                $content .= $ch;
            }
        }
        return ['content' => $content, 'end' => $n - 1];
    }

    private static function parse(string $input, string $prefix): string
    {
        $c = preg_split('//u', self::normalize($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($c);
        $out = '';
        $buf = '';
        $flush = function () use (&$out, &$buf): void {
            if ($buf !== '') {
                $out .= h($buf);
                $buf = '';
            }
        };
        $skipSpaces = function (int $k) use ($c, $n): int {
            while ($k < $n && $c[$k] === ' ') {
                $k++;
            }
            return $k;
        };

        $i = 0;
        while ($i < $n) {
            // 1. \frac{a}{b} / \dfrac{a}{b}
            if (self::startsWith($c, $i, '\\frac') || self::startsWith($c, $i, '\\dfrac')) {
                $cursor = $skipSpaces($i + (self::startsWith($c, $i, '\\dfrac') ? 6 : 5));
                $num = self::braces($c, $cursor);
                if ($num) {
                    $cursor = $skipSpaces($num['end'] + 1);
                    $den = self::braces($c, $cursor);
                    if ($den) {
                        $flush();
                        $out .= '<span class="inline-flex flex-col items-center justify-center align-middle mx-1 px-1 text-[0.92em] leading-tight" style="vertical-align:middle">'
                            . '<span class="border-b border-current px-1 pb-0.5 font-medium">' . self::parse($num['content'], "{$prefix}n$i") . '</span>'
                            . '<span class="px-1 pt-0.5 font-medium">' . self::parse($den['content'], "{$prefix}d$i") . '</span></span>';
                        $i = $den['end'] + 1;
                        continue;
                    }
                }
            }

            // 2. \sqrt[n]{x} / \sqrt{x}
            if (self::startsWith($c, $i, '\\sqrt')) {
                $cursor = $i + 5;
                $rootIndex = null;
                if (($c[$cursor] ?? null) === '[') {
                    for ($k = $cursor; $k < $n; $k++) {
                        if ($c[$k] === ']') {
                            $rootIndex = implode('', array_slice($c, $cursor + 1, $k - $cursor - 1));
                            $cursor = $k + 1;
                            break;
                        }
                    }
                }
                $rad = self::braces($c, $cursor);
                if ($rad) {
                    $flush();
                    $out .= '<span class="inline-flex items-baseline mx-0.5">'
                        . ($rootIndex !== null ? '<sup class="text-[0.65em] mr-0.5 font-semibold">' . h($rootIndex) . '</sup>' : '')
                        . '<span class="text-[1.08em] font-serif leading-none">√</span>'
                        . '<span class="border-t border-current px-0.5 pt-0.5">' . self::parse($rad['content'], "{$prefix}r$i") . '</span></span>';
                    $i = $rad['end'] + 1;
                    continue;
                }
            }

            // 3. ∫ ∮ ∑ ∏ with _{lower} ^{upper} bounds
            if (in_array($c[$i], ['∫', '∮', '∑', '∏'], true) && in_array($c[$i + 1] ?? '', ['_', '^'], true)) {
                $symbol = $c[$i];
                $cursor = $i + 1;
                $sub = null;
                $sup = null;
                for ($pass = 0; $pass < 2; $pass++) {
                    $marker = $c[$cursor] ?? null;
                    if ($marker !== '_' && $marker !== '^') {
                        break;
                    }
                    $cursor++;
                    $value = null;
                    if (($c[$cursor] ?? null) === '{') {
                        $g = self::braces($c, $cursor);
                        $value = $g['content'];
                        $cursor = $g['end'] + 1;
                    } elseif ($cursor < $n) {
                        $value = $c[$cursor];
                        $cursor++;
                    }
                    if ($marker === '_') {
                        $sub = $value;
                    } else {
                        $sup = $value;
                    }
                }
                $flush();
                $out .= '<span class="inline-flex items-center align-middle mx-1">'
                    . '<span class="text-[1.35em] font-serif leading-none">' . h($symbol) . '</span>'
                    . '<span class="inline-flex flex-col justify-between text-[0.7em] leading-none ml-0.5">'
                    . ($sup !== null ? '<span class="mb-1">' . self::parse($sup, "{$prefix}u$i") . '</span>' : '')
                    . ($sub !== null ? '<span class="mt-0.5">' . self::parse($sub, "{$prefix}l$i") . '</span>' : '')
                    . '</span></span>';
                $i = $cursor;
                continue;
            }

            // 4. superscript ^{...} or ^x
            if ($c[$i] === '^') {
                $cursor = $i + 1;
                if (($c[$cursor] ?? null) === '{') {
                    $g = self::braces($c, $cursor);
                    $flush();
                    $out .= '<sup class="text-[0.76em] font-semibold ml-[1px]">' . self::parse($g['content'], "{$prefix}p$i") . '</sup>';
                    $i = $g['end'] + 1;
                    continue;
                }
                if ($cursor < $n && preg_match('/^[0-9a-zA-Z+\-θλπαβ∞°]$/u', $c[$cursor])) {
                    $exp = '';
                    while ($cursor < $n && preg_match('/^[0-9+\-]$/', $c[$cursor])) {
                        $exp .= $c[$cursor++];
                    }
                    if ($exp === '') {
                        $exp = $c[$cursor++];
                    }
                    $flush();
                    $out .= '<sup class="text-[0.76em] font-semibold ml-[1px]">' . h($exp) . '</sup>';
                    $i = $cursor;
                    continue;
                }
            }

            // 5. subscript _{...} or _x
            if ($c[$i] === '_') {
                $cursor = $i + 1;
                if (($c[$cursor] ?? null) === '{') {
                    $g = self::braces($c, $cursor);
                    $flush();
                    $out .= '<sub class="text-[0.76em] font-semibold ml-[1px]">' . self::parse($g['content'], "{$prefix}s$i") . '</sub>';
                    $i = $g['end'] + 1;
                    continue;
                }
                if ($cursor < $n && preg_match('/^[0-9a-zA-Z]$/', $c[$cursor])) {
                    $flush();
                    $out .= '<sub class="text-[0.76em] font-semibold ml-[1px]">' . h($c[$cursor]) . '</sub>';
                    $i = $cursor + 1;
                    continue;
                }
            }

            $buf .= $c[$i];
            $i++;
        }

        $flush();
        return $out;
    }
}
