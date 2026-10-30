<?php
declare(strict_types=1);

/**
 * Draws skeletal structural formulas from a small, validated spec, so AI-generated chemistry
 * questions get geometrically correct diagrams (the model only says WHAT the molecule is, never
 * where to put lines).
 *
 * Spec:
 *   kind               "benzene" | "naphthalene" | "chain"
 *   ringSubstituents   [{position, group}]  benzene: 1-6 ring positions; naphthalene: IUPAC 1-8
 *   chainBonds         [1|2|3, ...]         bond orders between consecutive carbons (n bonds = n+1 carbons)
 *   chainBranches      [{carbon, group}]    group on carbon 1..n; "=O" is a double bond; "C" is a bare methyl line
 *
 * Groups are condensed labels such as OH, NH2, NO2, CH3, COOH, Cl, OCH3.
 */
final class MoleculeSvg
{
    private const B = 44.0;
    private const INK = '#0f172a';
    private const ELEMENT_COLORS = ['O' => '#b91c1c', 'N' => '#1d4ed8', 'Cl' => '#15803d', 'Br' => '#9a3412', 'F' => '#15803d', 'S' => '#a16207'];

    /** @var string[] */
    private array $parts = [];
    /** @var array<int,array{0:float,1:float}> */
    private array $pts = [];

    /** @return ?string finished <svg>, or null when the spec is missing/invalid */
    public static function fromSpec(mixed $spec): ?string
    {
        if (!is_array($spec)) {
            return null;
        }
        try {
            if (($spec['kind'] ?? '') === 'compare') {
                return self::compare($spec);
            }
            $m = new self();
            $m->build($spec);
            return $m->svg();
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function build(array $spec): void
    {
        match ($spec['kind'] ?? '') {
            'benzene'     => $this->benzene($spec),
            'naphthalene' => $this->naphthalene($spec),
            'chain'       => $this->chain($spec),
            'ring'        => $this->cycloRing($spec),
            default       => throw new InvalidArgumentException('kind'),
        };
    }

    /** Two or three structures side by side, captioned I, II, III (isomer / comparison questions). */
    private static function compare(array $spec): string
    {
        $items = array_values(array_slice((array) ($spec['items'] ?? []), 0, 3));
        if (count($items) < 2) {
            throw new InvalidArgumentException('compare needs 2-3 items');
        }
        $out = new self();
        $cursor = 0.0;
        $centres = [];
        $bottom = -INF;
        foreach ($items as $item) {
            if (!is_array($item) || ($item['kind'] ?? '') === 'compare') {
                throw new InvalidArgumentException('item');
            }
            $m = new self();
            $m->build($item);
            $xs = array_column($m->pts, 0);
            $ys = array_column($m->pts, 1);
            $dx = $cursor - min($xs);
            $dy = -(min($ys) + max($ys)) / 2;
            $out->parts[] = sprintf('<g transform="translate(%.1f %.1f)">%s</g>', $dx, $dy, implode('', $m->parts));
            $out->pts[] = [min($xs) + $dx, min($ys) + $dy];
            $out->pts[] = [max($xs) + $dx, max($ys) + $dy];
            $centres[] = $cursor + (max($xs) - min($xs)) / 2;
            $bottom = max($bottom, max($ys) + $dy);
            $cursor += (max($xs) - min($xs)) + 80;
        }
        foreach ($centres as $i => $cx) {
            $out->badge(['I', 'II', 'III'][$i], $cx, $bottom + 34);
        }
        return $out->svg();
    }

    private function badge(string $l, float $x, float $y): void
    {
        $this->pts[] = [$x - 14, $y - 14];
        $this->pts[] = [$x + 14, $y + 14];
        $this->parts[] = sprintf('<circle cx="%.1f" cy="%.1f" r="13" fill="#fff" stroke="#b91c1c" stroke-width="2.2"/><text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="central" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#b91c1c">%s</text>', $x, $y, $x, $y + 0.5, $l);
    }

    /** Cycloalkane / cycloalkene: regular n-gon (3-8), optional double bonds, substituents. */
    private function cycloRing(array $s): void
    {
        $n = (int) ($s['ringSize'] ?? 0);
        if ($n < 3 || $n > 8) {
            throw new InvalidArgumentException('ring size');
        }
        $R = self::B / (2 * sin(M_PI / $n));
        $c = [0.0, 0.0];
        $v = [];
        for ($k = 0; $k < $n; $k++) {
            $v[] = $this->polar($c, -90 + 360 * $k / $n, $R);
        }
        $doubles = [];
        foreach (array_slice((array) ($s['ringBonds'] ?? []), 0, 4) as $e) {
            $e = (int) $e;
            if ($e < 1 || $e > $n) {
                throw new InvalidArgumentException('ring bond');
            }
            $doubles[$e] = true;
        }
        for ($k = 0; $k < $n; $k++) {
            $this->bond($v[$k], $v[($k + 1) % $n], ['order' => isset($doubles[$k + 1]) ? 2 : 1, 'inward' => $c]);
        }
        foreach ($this->subs($s['ringSubstituents'] ?? [], $n) as $pos => $group) {
            $this->attach($v[$pos - 1], -90 + 360 * ($pos - 1) / $n, $group);
        }
    }

    // ------------------------------------------------------------ molecules

    private function benzene(array $s): void
    {
        $c = [0.0, 0.0];
        $v = $this->ring($c, -90);
        foreach ($this->subs($s['ringSubstituents'] ?? [], 6) as $pos => $group) {
            $k = $pos - 1;
            $this->attach($v[$k], -90 + 60 * $k, $group);
        }
    }

    private function naphthalene(array $s): void
    {
        $c1 = [0.0, 0.0];
        $c2 = [2 * self::B * cos(deg2rad(30)), 0.0];
        $L = $this->hexagon($c1, -90);
        $R = $this->hexagon($c2, -90);
        for ($k = 0; $k < 6; $k++) {
            $this->bond($L[$k], $L[($k + 1) % 6], ['order' => $k % 2 === 0 ? 2 : 1, 'inward' => $c1]);
        }
        for ($k = 0; $k < 6; $k++) {
            if ($k === 4) {
                continue; // the shared edge belongs to the left ring
            }
            $this->bond($R[$k], $R[($k + 1) % 6], ['order' => $k % 2 === 0 ? 2 : 1, 'inward' => $c2]);
        }
        // IUPAC numbering: 1-4 round the right ring from the top, 5-8 round the left ring from the bottom.
        $where = [1 => [$R, 0, -90], 2 => [$R, 1, -30], 3 => [$R, 2, 30], 4 => [$R, 3, 90],
                  5 => [$L, 3, 90], 6 => [$L, 4, 150], 7 => [$L, 5, -150], 8 => [$L, 0, -90]];
        foreach ($this->subs($s['ringSubstituents'] ?? [], 8) as $pos => $group) {
            [$ring, $k, $angle] = $where[$pos];
            $this->attach($ring[$k], $angle, $group);
        }
    }

    private function chain(array $s): void
    {
        $bonds = array_values((array) ($s['chainBonds'] ?? []));
        if (count($bonds) < 1 || count($bonds) > 11) {
            throw new InvalidArgumentException('chain length');
        }
        $n = count($bonds) + 1;
        $c = [];
        for ($i = 0; $i < $n; $i++) {
            $c[$i] = [38.1 * $i, $i % 2 === 0 ? 22.0 : 0.0];
        }
        foreach ($bonds as $i => $order) {
            $order = (int) $order;
            if ($order < 1 || $order > 3) {
                throw new InvalidArgumentException('bond order');
            }
            $this->bond($c[$i], $c[$i + 1], ['order' => $order]);
        }
        // Up to two groups per carbon (e.g. =O and OH on a carboxyl carbon, or two methyls on a quaternary carbon).
        $byCarbon = [];
        foreach (array_slice((array) ($s['chainBranches'] ?? []), 0, 12) as $item) {
            $p = (int) ($item['carbon'] ?? 0);
            $g = trim((string) ($item['group'] ?? ''));
            if ($p < 1 || $p > $n || !preg_match('/^=?[A-Za-z0-9()]{1,12}$/', $g)) {
                throw new InvalidArgumentException('branch');
            }
            $byCarbon[$p][] = $g;
        }
        foreach ($byCarbon as $carbon => $groups) {
            $i = $carbon - 1;
            $isEnd = $i === 0 || $i === $n - 1;
            if (count($groups) > 2) {
                throw new InvalidArgumentException('too many groups on one carbon');
            }
            if (count($groups) === 1) {
                $this->attach($c[$i], $i % 2 === 0 ? 90 : -90, $groups[0], true);
                continue;
            }
            if (!$isEnd) {
                // internal carbon: its two free valences point 45 degrees either side of straight out of the zig-zag
                $dir = $i % 2 === 0 ? [45, 135] : [-45, -135];
                $this->attach($c[$i], $dir[0], $groups[0], true);
                $this->attach($c[$i], $dir[1], $groups[1], true);
                continue;
            }
            // end carbon: the two free valences sit 120 degrees either side of the chain bond
            $nb = $c[$i === 0 ? 1 : $n - 2];
            $d = rad2deg(atan2($nb[1] - $c[$i][1], $nb[0] - $c[$i][0]));
            $this->attach($c[$i], $d + 120, $groups[0], true);
            $this->attach($c[$i], $d - 120, $groups[1], true);
        }
    }

    /** Validates [{position|carbon, group}] into [index => group]. */
    private function subs(mixed $list, int $max, string $key = 'position'): array
    {
        $out = [];
        foreach (array_slice((array) $list, 0, 12) as $item) {
            $p = (int) ($item[$key] ?? 0);
            $g = trim((string) ($item['group'] ?? ''));
            if ($p < 1 || $p > $max || isset($out[$p]) || !preg_match('/^=?[A-Za-z0-9()]{1,12}$/', $g)) {
                throw new InvalidArgumentException('substituent');
            }
            $out[$p] = $g;
        }
        return $out;
    }

    // ------------------------------------------------------------ drawing

    private function polar(array $p, float $deg, float $len = self::B): array
    {
        return [$p[0] + $len * cos(deg2rad($deg)), $p[1] + $len * sin(deg2rad($deg))];
    }

    private function hexagon(array $c, float $start): array
    {
        $v = [];
        for ($k = 0; $k < 6; $k++) {
            $v[] = $this->polar($c, $start + 60 * $k);
        }
        return $v;
    }

    private function ring(array $c, float $start): array
    {
        $v = $this->hexagon($c, $start);
        for ($k = 0; $k < 6; $k++) {
            $this->bond($v[$k], $v[($k + 1) % 6], ['order' => $k % 2 === 0 ? 2 : 1, 'inward' => $c]);
        }
        return $v;
    }

    /** Bond from $from outwards at $angle to a group (label), or a bare vertex when the group is "C". */
    private function attach(array $from, float $angle, string $group, bool $allowDouble = false): void
    {
        $order = 1;
        if ($group[0] === '=') {
            if (!$allowDouble) {
                throw new InvalidArgumentException('double bond on ring');
            }
            $order = 2;
            $group = substr($group, 1);
            if ($group === '') {
                throw new InvalidArgumentException('empty group');
            }
        }
        $to = $this->polar($from, $angle);
        if ($group === 'C') {
            $this->bond($from, $to, ['order' => $order]);
            return;
        }
        $this->bond($from, $to, ['order' => $order, 'cutB' => 10]);
        $this->label($to, $group, cos(deg2rad($angle)) < -0.2);
    }

    private function line(array $a, array $b): void
    {
        $this->pts[] = $a;
        $this->pts[] = $b;
        $this->parts[] = sprintf(
            '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2.4" stroke-linecap="round"/>',
            $a[0], $a[1], $b[0], $b[1], self::INK
        );
    }

    private function bond(array $a, array $b, array $o = []): void
    {
        $order = $o['order'] ?? 1;
        $cutA = $o['cutA'] ?? 0;
        $cutB = $o['cutB'] ?? 0;
        $len = hypot($b[0] - $a[0], $b[1] - $a[1]);
        $u = [($b[0] - $a[0]) / $len, ($b[1] - $a[1]) / $len];
        $n = [-$u[1], $u[0]];
        $A = [$a[0] + $u[0] * $cutA, $a[1] + $u[1] * $cutA];
        $Bp = [$b[0] - $u[0] * $cutB, $b[1] - $u[1] * $cutB];
        if ($order === 1) {
            $this->line($A, $Bp);
        } elseif (isset($o['inward'])) {
            $mid = [($a[0] + $b[0]) / 2, ($a[1] + $b[1]) / 2];
            $s = (($o['inward'][0] - $mid[0]) * $n[0] + ($o['inward'][1] - $mid[1]) * $n[1]) >= 0 ? 1 : -1;
            $this->line($A, $Bp);
            $this->line(
                [$a[0] + $u[0] * ($cutA + 8) + $n[0] * 5.5 * $s, $a[1] + $u[1] * ($cutA + 8) + $n[1] * 5.5 * $s],
                [$b[0] - $u[0] * ($cutB + 8) + $n[0] * 5.5 * $s, $b[1] - $u[1] * ($cutB + 8) + $n[1] * 5.5 * $s]
            );
        } else {
            $offsets = $order === 2 ? [-2.8, 2.8] : [-4.5, 0, 4.5];
            foreach ($offsets as $d) {
                $this->line([$A[0] + $n[0] * $d, $A[1] + $n[1] * $d], [$Bp[0] + $n[0] * $d, $Bp[1] + $n[1] * $d]);
            }
        }
    }

    /** Condensed label. Groups on the left are written backwards (HO, H3C, O2N) with the attached atom at $p. */
    private function label(array $p, string $group, bool $leftward): void
    {
        preg_match_all('/[A-Z][a-z]?\d*|\(|\)\d*/', $group, $m);
        $atoms = $m[0] ?: [$group];
        $isPlain = implode('', $atoms) === $group;
        if ($leftward && $isPlain) {
            $atoms = array_reverse($atoms);
        }
        $size = 17;
        $color = self::INK;
        foreach (self::ELEMENT_COLORS as $el => $col) {
            if (str_starts_with($group, $el)) {
                $color = $col;
            }
        }
        $out = '';
        $width = 0.0;
        foreach ($atoms as $atom) {
            if (preg_match('/^(.*?)(\d+)$/', $atom, $mm) && $mm[1] !== '') {
                $out .= h($mm[1]) . '<tspan dy="4" font-size="12">' . h($mm[2]) . '</tspan><tspan dy="-4">&#8203;</tspan>';
                $width += strlen($mm[1]) + 0.6;
            } else {
                $out .= h($atom);
                $width += strlen($atom);
            }
        }
        $x = $leftward ? $p[0] + $size * 0.34 : $p[0] - $size * 0.34;
        $this->pts[] = [$leftward ? $p[0] - $width * $size * 0.62 : $p[0] - $size * 0.6, $p[1] - $size * 0.7];
        $this->pts[] = [$leftward ? $p[0] + $size * 0.6 : $p[0] + $width * $size * 0.62, $p[1] + $size * 0.7];
        $this->parts[] = sprintf(
            '<text x="%.1f" y="%.1f" dominant-baseline="central" text-anchor="%s" font-family="Arial, sans-serif" font-size="%d" font-weight="600" fill="%s" stroke="#f8fafc" stroke-width="4" paint-order="stroke" stroke-linejoin="round">%s</text>',
            $x, $p[1], $leftward ? 'end' : 'start', $size, $color, $out
        );
    }

    private function svg(): string
    {
        $xs = array_column($this->pts, 0);
        $ys = array_column($this->pts, 1);
        $pad = 30;
        $grow = function (float $lo, float $hi, float $min): array {
            return $hi - $lo >= $min ? [$lo, $hi] : [($lo + $hi) / 2 - $min / 2, ($lo + $hi) / 2 + $min / 2];
        };
        [$x0, $x1] = $grow(min($xs) - $pad, max($xs) + $pad, 300);
        [$y0, $y1] = $grow(min($ys) - $pad, max($ys) + $pad, 170);
        $w = $x1 - $x0;
        $h = $y1 - $y0;
        return sprintf(
            '<svg viewBox="%.1f %.1f %.1f %.1f" xmlns="http://www.w3.org/2000/svg"><rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="#f8fafc" rx="12"/>%s</svg>',
            $x0, $y0, $w, $h, $x0, $y0, $w, $h, implode('', $this->parts)
        );
    }
}
