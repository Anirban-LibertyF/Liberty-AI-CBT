<?php
declare(strict_types=1);

/**
 * Sanitiser for question diagrams. AI output is untrusted, and the SVG is printed
 * straight into the page, so strip anything that can run script or load content.
 */
final class Svg
{
    private const DANGEROUS_TAGS = 'script|foreignObject|iframe|object|embed|animate|set';

    /** Returns clean <svg>…</svg> markup, or null if there is no usable SVG. */
    public static function sanitize(?string $svg): ?string
    {
        if ($svg === null) {
            return null;
        }
        $start = stripos($svg, '<svg');
        $end = strripos($svg, '</svg>');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }
        $svg = substr($svg, $start, $end + 6 - $start);

        // Repeat until stable so nested tricks like <scr<script></script>ipt> can't survive one pass.
        do {
            $before = $svg;
            $svg = preg_replace('#<\s*(' . self::DANGEROUS_TAGS . ')\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $svg) ?? '';
            $svg = preg_replace('#<\s*/?\s*(' . self::DANGEROUS_TAGS . ')\b[^>]*>#i', '', $svg) ?? '';
            $svg = preg_replace('#[\s/]on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $svg) ?? '';
            $svg = preg_replace('#(href\s*=\s*)(["\'])\s*(?:javascript|data|vbscript):[^"\']*\2#i', '$1$2#$2', $svg) ?? '';
        } while ($svg !== $before);

        return str_contains($svg, '<svg') ? $svg : null;
    }
}
