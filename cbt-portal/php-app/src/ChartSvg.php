<?php
declare(strict_types=1);

/**
 * Bar, line and pie charts drawn from numbers the AI supplies, so data-interpretation and
 * biology/physics graph questions always show exactly the data in the question.
 *
 * Spec: chartType (bar|line|pie), title, xLabel, yLabel, labels[], series[{name, values[]}]
 */
final class ChartSvg
{
    private const PALETTE = ['#dc2626', '#2563eb', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#be185d', '#65a30d', '#475569', '#ea580c', '#0d9488', '#a21caf'];

    public static function fromSpec(mixed $spec): ?string
    {
        if (!is_array($spec)) {
            return null;
        }
        try {
            [$labels, $series] = self::validate($spec);
            $title = mb_substr(trim((string) ($spec['title'] ?? '')), 0, 70);
            return match ($spec['chartType'] ?? '') {
                'bar'   => self::axes($spec, $title, $labels, $series, false),
                'line'  => self::axes($spec, $title, $labels, $series, true),
                'pie'   => self::pie($title, $labels, $series[0]),
                default => null,
            };
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** @return array{0:string[],1:array<int,array{name:string,values:float[]}>} */
    private static function validate(array $spec): array
    {
        $labels = array_values((array) ($spec['labels'] ?? []));
        $n = count($labels);
        if ($n < 2 || $n > 12) {
            throw new InvalidArgumentException('labels');
        }
        $labels = array_map(fn ($l) => mb_substr(trim((string) $l), 0, 22), $labels);
        $series = [];
        foreach (array_slice((array) ($spec['series'] ?? []), 0, 4) as $s) {
            $vals = array_values((array) ($s['values'] ?? []));
            if (count($vals) !== $n) {
                throw new InvalidArgumentException('values');
            }
            foreach ($vals as $v) {
                if (!is_numeric($v) || !is_finite((float) $v) || abs((float) $v) > 1e9) {
                    throw new InvalidArgumentException('number');
                }
            }
            $series[] = ['name' => mb_substr(trim((string) ($s['name'] ?? '')), 0, 24), 'values' => array_map('floatval', $vals)];
        }
        if (!$series || (($spec['chartType'] ?? '') === 'pie' && (count($series) !== 1 || min($series[0]['values']) < 0 || array_sum($series[0]['values']) <= 0))) {
            throw new InvalidArgumentException('series');
        }
        return [$labels, $series];
    }

    private static function fmt(float $v): string
    {
        $r = round($v, 2);
        return abs($r) >= 1000 ? number_format($r, $r == floor($r) ? 0 : 1) : rtrim(rtrim(number_format($r, 2, '.', ''), '0'), '.');
    }

    /** @return array{0:float,1:float,2:float} axis min, max, step */
    private static function scale(float $lo, float $hi): array
    {
        $lo = min(0.0, $lo);
        $hi = max($hi, $lo + 1e-9);
        $raw = ($hi - $lo) / 5;
        $mag = 10 ** floor(log10($raw));
        $step = $mag;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            $step = $m * $mag;
            if ($step >= $raw) {
                break;
            }
        }
        return [floor($lo / $step) * $step, ceil($hi / $step) * $step, $step];
    }

    private static function wrap(int $w, int $h, string $inner): string
    {
        return "<svg viewBox=\"0 0 $w $h\" xmlns=\"http://www.w3.org/2000/svg\"><rect width=\"$w\" height=\"$h\" fill=\"#f8fafc\" rx=\"12\"/>$inner</svg>";
    }

    private static function axes(array $spec, string $title, array $labels, array $series, bool $line): string
    {
        $W = 540;
        $H = 320;
        $x0 = 78;
        $x1 = $W - 24;
        $y0 = 62 + (count($series) > 1 ? 18 : 0);
        $y1 = $H - 64;
        $all = array_merge(...array_column($series, 'values'));
        [$min, $max, $step] = self::scale(min($all), max($all));
        $Y = fn (float $v) => $y1 - ($v - $min) / ($max - $min) * ($y1 - $y0);
        $g = sprintf('<text x="%g" y="26" text-anchor="middle" font-family="Arial" font-size="15" font-weight="700" fill="#0f172a">%s</text>', $W / 2, h($title));
        for ($v = $min; $v <= $max + $step / 1000; $v += $step) {
            $y = $Y($v);
            $g .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="%s" stroke-width="1"/><text x="%d" y="%.1f" text-anchor="end" dominant-baseline="central" font-family="Arial" font-size="11" fill="#475569">%s</text>', $x0, $y, $x1, $y, abs($v) < 1e-9 ? '#64748b' : '#e2e8f0', $x0 - 7, $y, h(self::fmt($v)));
        }
        $g .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#64748b" stroke-width="1.6"/>', $x0, $y0, $x0, $y1);
        $n = count($labels);
        $slot = ($x1 - $x0) / $n;
        $fs = $n > 8 ? 10 : 12;
        foreach ($labels as $i => $l) {
            $g .= sprintf('<text x="%.1f" y="%d" text-anchor="middle" font-family="Arial" font-size="%d" fill="#0f172a">%s</text>', $x0 + $slot * ($i + 0.5), $y1 + 18, $fs, h($l));
        }
        $k = count($series);
        if (!$line) {
            $bw = min(46.0, $slot * 0.8 / $k);
            foreach ($series as $si => $s) {
                foreach ($s['values'] as $i => $v) {
                    $bx = $x0 + $slot * ($i + 0.5) - $bw * $k / 2 + $bw * $si;
                    $yv = $Y($v);
                    $g .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s" rx="2"/>', $bx, min($yv, $Y(0.0)), $bw - 2, abs($Y(0.0) - $yv), self::PALETTE[$si]);
                    if ($n * $k <= 24) {
                        $g .= sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" font-family="Arial" font-size="10" font-weight="700" fill="#0f172a">%s</text>', $bx + ($bw - 2) / 2, ($v >= 0 ? $yv - 5 : $yv + 13), h(self::fmt($v)));
                    }
                }
            }
        } else {
            foreach ($series as $si => $s) {
                $pts = [];
                foreach ($s['values'] as $i => $v) {
                    $pts[] = sprintf('%.1f,%.1f', $x0 + $slot * ($i + 0.5), $Y($v));
                }
                $g .= sprintf('<polyline points="%s" fill="none" stroke="%s" stroke-width="3" stroke-linejoin="round"/>', implode(' ', $pts), self::PALETTE[$si]);
                foreach ($s['values'] as $i => $v) {
                    $cx = $x0 + $slot * ($i + 0.5);
                    $g .= sprintf('<circle cx="%.1f" cy="%.1f" r="4" fill="#fff" stroke="%s" stroke-width="2.5"/>', $cx, $Y($v), self::PALETTE[$si]);
                    if ($k === 1 && $n <= 12) {
                        $g .= sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" font-family="Arial" font-size="10" font-weight="700" fill="#0f172a">%s</text>', $cx, $Y($v) - 9, h(self::fmt($v)));
                    }
                }
            }
        }
        if ($k > 1) {
            $lx = $x0;
            foreach ($series as $si => $s) {
                $g .= sprintf('<rect x="%d" y="40" width="12" height="12" fill="%s" rx="2"/><text x="%d" y="50" font-family="Arial" font-size="12" fill="#0f172a">%s</text>', $lx, self::PALETTE[$si], $lx + 17, h($s['name']));
                $lx += 30 + 7 * max(4, mb_strlen($s['name']));
            }
        }
        $xl = mb_substr(trim((string) ($spec['xLabel'] ?? '')), 0, 50);
        $yl = mb_substr(trim((string) ($spec['yLabel'] ?? '')), 0, 50);
        if ($xl !== '') {
            $g .= sprintf('<text x="%g" y="%d" text-anchor="middle" font-family="Arial" font-size="12" font-weight="700" fill="#475569">%s</text>', ($x0 + $x1) / 2, $H - 12, h($xl));
        }
        if ($yl !== '') {
            $g .= sprintf('<text transform="translate(16 %g) rotate(-90)" text-anchor="middle" font-family="Arial" font-size="12" font-weight="700" fill="#475569">%s</text>', ($y0 + $y1) / 2, h($yl));
        }
        return self::wrap($W, $H, $g);
    }

    private static function pie(string $title, array $labels, array $s): string
    {
        $W = 540;
        $H = 320;
        $cx = 150;
        $cy = 170;
        $r = 100;
        $total = array_sum($s['values']);
        $g = sprintf('<text x="%g" y="28" text-anchor="middle" font-family="Arial" font-size="15" font-weight="700" fill="#0f172a">%s</text>', $W / 2, h($title));
        $a = -M_PI / 2;
        foreach ($s['values'] as $i => $v) {
            $col = self::PALETTE[$i % count(self::PALETTE)];
            if ($v <= 0) {
                continue;
            }
            $sweep = $v / $total * 2 * M_PI;
            if ($sweep >= 2 * M_PI - 1e-9) {
                $g .= sprintf('<circle cx="%d" cy="%d" r="%d" fill="%s" stroke="#fff" stroke-width="2"/>', $cx, $cy, $r, $col);
            } else {
                $b = $a + $sweep;
                $g .= sprintf('<path d="M%d %d L%.1f %.1f A%d %d 0 %d 1 %.1f %.1f Z" fill="%s" stroke="#fff" stroke-width="2"/>', $cx, $cy, $cx + $r * cos($a), $cy + $r * sin($a), $r, $r, $sweep > M_PI ? 1 : 0, $cx + $r * cos($b), $cy + $r * sin($b), $col);
            }
            $a += $sweep;
        }
        foreach ($labels as $i => $l) {
            $y = 70 + $i * 22;
            $g .= sprintf('<rect x="300" y="%d" width="13" height="13" fill="%s" rx="2"/><text x="320" y="%d" font-family="Arial" font-size="13" fill="#0f172a">%s: <tspan font-weight="700">%s</tspan></text>', $y, self::PALETTE[$i % count(self::PALETTE)], $y + 11, h($l), h(self::fmt($s['values'][$i])));
        }
        return self::wrap($W, $H, $g);
    }
}
