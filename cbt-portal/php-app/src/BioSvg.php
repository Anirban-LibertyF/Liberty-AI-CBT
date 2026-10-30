<?php
declare(strict_types=1);

/**
 * Biology diagrams for NEET papers. Language models draw anatomy badly, so the AI never draws:
 *   - "catalog"  a pre-drawn labelled diagram (letters A, B, C ... point at known parts)
 *   - "punnett"  a Punnett square computed from the parents' genotypes
 *   - "pyramid"  an energy / biomass pyramid from a list of levels
 *   - pedigree() is available to hand-written questions.
 * Letters on catalog diagrams always map to the names in CATALOG, which the question writer
 * (human or AI) is given up front.
 */
final class BioSvg
{
    public const CATALOG = [
        'animal_cell' => ['title' => 'Animal cell', 'parts' => ['A' => 'Nucleus', 'B' => 'Mitochondrion', 'C' => 'Golgi apparatus', 'D' => 'Rough endoplasmic reticulum', 'E' => 'Lysosome', 'F' => 'Plasma membrane']],
        'plant_cell' => ['title' => 'Plant cell', 'parts' => ['A' => 'Cell wall', 'B' => 'Central vacuole', 'C' => 'Chloroplast', 'D' => 'Nucleus', 'E' => 'Mitochondrion']],
        'dna_ladder' => ['title' => 'DNA segment (antiparallel strands, 5\' and 3\' ends marked)', 'parts' => ['A' => 'Phosphate group', 'B' => 'Deoxyribose sugar', 'C' => 'A-T base pair (2 hydrogen bonds)', 'D' => 'G-C base pair (3 hydrogen bonds)', 'E' => 'Nitrogenous base (thymine)']],
        'mitosis' => ['title' => 'Four stages of mitosis (cells shown out of order)', 'parts' => ['P' => 'Metaphase', 'Q' => 'Prophase', 'R' => 'Telophase', 'S' => 'Anaphase']],
        'human_heart' => ['title' => 'Human heart (blue = deoxygenated, red = oxygenated; drawn in front view)', 'parts' => ['A' => 'Right atrium', 'B' => 'Left ventricle', 'C' => 'Aorta', 'D' => 'Pulmonary artery', 'E' => 'Bicuspid (mitral) valve', 'F' => 'Superior vena cava']],
        'neuron' => ['title' => 'Myelinated neuron', 'parts' => ['A' => 'Dendrites', 'B' => 'Nucleus', 'C' => 'Myelin sheath', 'D' => 'Node of Ranvier', 'E' => 'Axon terminal (synaptic knob)', 'F' => 'Axon hillock']],
        'flower_ls' => ['title' => 'Longitudinal section of a flower', 'parts' => ['A' => 'Stigma', 'B' => 'Anther', 'C' => 'Ovary', 'D' => 'Petal', 'E' => 'Sepal', 'F' => 'Ovule']],
        'synapse' => ['title' => 'Chemical synapse', 'parts' => ['A' => 'Synaptic vesicle', 'B' => 'Synaptic cleft', 'C' => 'Neurotransmitter receptor', 'D' => 'Presynaptic axon terminal', 'E' => 'Mitochondrion', 'F' => 'Postsynaptic neuron']],
        'lac_operon' => ['title' => 'lac operon of E. coli', 'parts' => ['A' => 'Regulator gene (lac i)', 'B' => 'Promoter', 'C' => 'Operator', 'D' => 'lac Z gene (beta-galactosidase)', 'E' => 'lac Y gene (permease)', 'F' => 'lac A gene (transacetylase)']],
        'replication_fork' => ['title' => 'DNA replication fork (red = leading strand, blue = lagging strand fragments; arrows show direction of new-strand synthesis)', 'parts' => ['A' => 'Helicase', 'B' => 'Leading strand', 'C' => 'Okazaki fragment (lagging strand)', 'D' => 'RNA primer', 'E' => 'DNA polymerase', 'F' => 'Parental DNA']],
        'mitochondrion' => ['title' => 'Mitochondrion (section)', 'parts' => ['A' => 'Outer membrane', 'B' => 'Inner membrane', 'C' => 'Cristae', 'D' => 'Matrix', 'E' => 'Intermembrane space', 'F' => 'Mitochondrial DNA']],
        'chloroplast' => ['title' => 'Chloroplast (section)', 'parts' => ['A' => 'Outer membrane', 'B' => 'Granum', 'C' => 'Stroma lamella', 'D' => 'Stroma', 'E' => 'Thylakoid']],
        'bacteriophage' => ['title' => 'T4 bacteriophage', 'parts' => ['A' => 'Head (capsid)', 'B' => 'Collar', 'C' => 'Tail sheath', 'D' => 'Base plate', 'E' => 'Tail fibre', 'F' => 'Genetic material (DNA)']],
        'human_eye' => ['title' => 'Human eye (horizontal section)', 'parts' => ['A' => 'Cornea', 'B' => 'Iris', 'C' => 'Lens', 'D' => 'Retina', 'E' => 'Optic nerve', 'F' => 'Sclera']],
        'sarcomere' => ['title' => 'Sarcomere of a striated muscle fibre (relaxed)', 'parts' => ['A' => 'Z line', 'B' => 'Thin filament (actin)', 'C' => 'Thick filament (myosin)', 'D' => 'H zone', 'E' => 'I band', 'F' => 'A band']],
        'nephron' => ['title' => 'Nephron (cortex above, medulla below)', 'parts' => ['A' => 'Bowman\'s capsule with glomerulus', 'B' => 'Proximal convoluted tubule (PCT)', 'C' => 'Loop of Henle', 'D' => 'Distal convoluted tubule (DCT)', 'E' => 'Collecting duct']],
    ];

    /** Letter that marks $part on diagram $id (used by hand-written questions so letters can't drift). */
    public static function letter(string $id, string $part): string
    {
        $letter = array_search($part, self::CATALOG[$id]['parts'], true);
        if ($letter === false) {
            throw new InvalidArgumentException("No part '$part' in $id");
        }
        return (string) $letter;
    }

    /** One line per diagram, for the AI prompt. */
    public static function legend(?array $ids = null): string
    {
        $out = [];
        foreach (self::CATALOG as $id => $d) {
            if ($ids !== null && !in_array($id, $ids, true)) {
                continue;
            }
            $parts = [];
            foreach ($d['parts'] as $l => $name) {
                $parts[] = "$l=$name";
            }
            $out[] = "$id ({$d['title']}): " . implode('; ', $parts);
        }
        return implode("\n", $out);
    }

    public static function fromSpec(mixed $spec): ?string
    {
        if (!is_array($spec)) {
            return null;
        }
        try {
            return match ($spec['kind'] ?? '') {
                'catalog' => self::catalog((string) ($spec['diagramId'] ?? '')),
                'punnett' => self::punnett((string) ($spec['parent1'] ?? ''), (string) ($spec['parent2'] ?? '')),
                'pyramid' => self::pyramid((array) ($spec['levels'] ?? [])),
                'pedigree' => self::pedigreeFromSpec((array) ($spec['people'] ?? [])),
                'flow'     => self::flow((string) ($spec['layout'] ?? ''), (array) ($spec['nodes'] ?? []), (array) ($spec['edgeLabels'] ?? []), (string) ($spec['title'] ?? '')),
                'dna_seq'  => self::dnaSequence((string) ($spec['template'] ?? ''), (string) ($spec['show'] ?? 'protein')),
                default   => null,
            };
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** The plain diagram (seed 0: letters exactly as listed in CATALOG). */
    public static function catalog(string $id): string
    {
        return self::render($id, 0)['svg'];
    }

    // ------------------------------------------------------------ helpers

    private static function wrap(int $w, int $h, string $inner): string
    {
        return "<svg viewBox=\"0 0 $w $h\" xmlns=\"http://www.w3.org/2000/svg\"><rect width=\"$w\" height=\"$h\" fill=\"#f8fafc\" rx=\"12\"/>$inner</svg>";
    }

    /** Lettered marker with a leader line to the part at ($px,$py). */
    private static function tag(string $l, float $tx, float $ty, float $px, float $py): string
    {
        return sprintf('<g class="tg"><line x1="%g" y1="%g" x2="%g" y2="%g" stroke="#334155" stroke-width="1.5"/><circle cx="%g" cy="%g" r="3" fill="#334155"/></g>', $px, $py, $tx, $ty, $px, $py)
            . self::badge($l, $tx, $ty);
    }

    private static function badge(string $l, float $x, float $y): string
    {
        return sprintf('<g class="tg"><circle cx="%g" cy="%g" r="12" fill="#fff" stroke="#b91c1c" stroke-width="2.2"/><text x="%g" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial, sans-serif" font-size="14" font-weight="700" fill="#b91c1c">%s</text></g>', $x, $y, $x, $y + 0.5, h($l));
    }

    /** Horizontal squiggle of $n half-waves; use $vertical for a top-to-bottom one. */
    private static function wave(float $x, float $y, int $n, float $half, float $amp, string $stroke, float $sw, bool $vertical = false): string
    {
        $d = $vertical ? sprintf('M%g %g q%g %g %g %g', $x, $y, $amp, $half / 2, 0, $half) : sprintf('M%g %g q%g %g %g %g', $x, $y, $half / 2, -$amp, $half, 0);
        for ($i = 1; $i < $n; $i++) {
            $d .= $vertical ? sprintf(' t0 %g', $half) : sprintf(' t%g 0', $half);
        }
        return sprintf('<path d="%s" fill="none" stroke="%s" stroke-width="%g" stroke-linecap="round"/>', $d, $stroke, $sw);
    }

    private static function mito(float $x, float $y, float $rot, float $s): string
    {
        return sprintf('<g transform="translate(%g %g) rotate(%g) scale(%g)"><ellipse rx="34" ry="17" fill="#fde68a" stroke="#b45309" stroke-width="2.5"/><path d="M-24 0 l6 -10 l6 20 l6 -20 l6 20 l6 -20 l6 20 l6 -10" fill="none" stroke="#b45309" stroke-width="1.7"/></g>', $x, $y, $rot, $s);
    }

    // ------------------------------------------------------------ cells

    private static function animalCell(): string
    {
        $g = '<ellipse cx="220" cy="145" rx="185" ry="118" fill="#fff7ed" stroke="#c2410c" stroke-width="4"/>';
        $g .= '<circle cx="190" cy="135" r="50" fill="#e0e7ff" stroke="#4338ca" stroke-width="3"/><circle cx="190" cy="135" r="44" fill="none" stroke="#818cf8" stroke-width="1.5" stroke-dasharray="3 3"/>';
        $g .= '<circle cx="200" cy="128" r="14" fill="#4338ca"/><path d="M162 152 q8 -10 16 0 t16 0 t16 0" fill="none" stroke="#6366f1" stroke-width="1.7"/>';
        $g .= self::mito(310, 92, -22, 1) . self::mito(92, 178, 18, 0.8);
        foreach ([0, 1, 2] as $i) {
            $y = 160 + 13 * $i;
            $g .= self::wave(255, $y, 4, 24, 8, '#0f766e', 3.5);
            foreach ([262, 286, 310, 334] as $x) {
                $g .= sprintf('<circle cx="%d" cy="%d" r="2.3" fill="#0f766e"/>', $x, $y - 7);
            }
        }
        foreach ([0, 1, 2, 3] as $i) {
            $g .= sprintf('<path d="M%d %d q%d -14 %d 0" fill="none" stroke="#7c3aed" stroke-width="5.5" stroke-linecap="round"/>', 240 + $i, 212 + 10 * $i, 38 - $i * 2, 76 - $i * 4);
        }
        $g .= '<circle cx="135" cy="215" r="12" fill="#86efac" stroke="#15803d" stroke-width="2"/><circle cx="131" cy="212" r="2" fill="#15803d"/><circle cx="139" cy="219" r="2" fill="#15803d"/>';
        foreach ([[110, 110], [135, 85], [75, 135], [160, 215], [250, 120], [340, 150], [300, 135], [215, 215], [200, 70], [120, 160]] as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="2.2" fill="#0f766e"/>', $x, $y);
        }
        $g .= self::tag('A', 95, 42, 165, 118) . self::tag('B', 395, 38, 322, 86) . self::tag('C', 335, 270, 280, 232)
            . self::tag('D', 428, 175, 330, 173) . self::tag('E', 62, 262, 133, 216) . self::tag('F', 36, 42, 78, 69);
        return self::wrap(480, 295, '<g transform="translate(20 0)">' . $g . '</g>');
    }

    private static function plantCell(): string
    {
        $g = '<rect x="40" y="25" width="360" height="235" rx="14" fill="#dcfce7" stroke="#15803d" stroke-width="10"/>';
        $g .= '<rect x="49" y="34" width="342" height="217" rx="9" fill="#f7fee7" stroke="#84cc16" stroke-width="2"/>';
        $g .= '<rect x="95" y="72" width="170" height="140" rx="32" fill="#bae6fd" stroke="#0369a1" stroke-width="2.5"/>';
        $g .= '<circle cx="330" cy="100" r="36" fill="#e0e7ff" stroke="#4338ca" stroke-width="3"/><circle cx="338" cy="94" r="10" fill="#4338ca"/>';
        foreach ([[322, 200], [168, 236]] as [$x, $y]) {
            $g .= sprintf('<g transform="translate(%d %d)"><ellipse rx="36" ry="17" fill="#4ade80" stroke="#166534" stroke-width="2.5"/>', $x, $y);
            foreach ([-16, 0, 16] as $dx) {
                $g .= sprintf('<rect x="%d" y="-9" width="9" height="18" rx="2" fill="#166534"/>', $dx - 4);
            }
            $g .= '</g>';
        }
        $g .= self::mito(285, 238, -8, 0.7);
        $g .= self::tag('A', 12, 130, 44, 150) . self::tag('B', 180, -2, 180, 120) . self::tag('C', 428, 218, 345, 203)
            . self::tag('D', 428, 60, 352, 88) . self::tag('E', 380, 285, 295, 240);
        return self::wrap(480, 316, '<g transform="translate(20 14)">' . $g . '</g>');
    }

    private static function dnaLadder(): string
    {
        $top = ['A', 'G', 'T', 'C'];
        $bottom = ['T', 'C', 'A', 'G'];
        $xs = [110, 210, 310, 410];
        $col = ['A' => '#2563eb', 'T' => '#dc2626', 'G' => '#16a34a', 'C' => '#d97706'];
        $g = '<line x1="45" y1="62" x2="475" y2="62" stroke="#0f766e" stroke-width="5"/><line x1="45" y1="228" x2="475" y2="228" stroke="#0f766e" stroke-width="5"/>';
        foreach ([160, 260, 360, 460, 60] as $x) {
            foreach ([62, 228] as $y) {
                $g .= sprintf('<circle cx="%d" cy="%d" r="10" fill="#fecaca" stroke="#b91c1c" stroke-width="2"/><text x="%d" y="%d" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="11" font-weight="700" fill="#7f1d1d">P</text>', $x, $y, $x, $y);
            }
        }
        foreach ($xs as $i => $x) {
            foreach ([[62, 112, $top[$i], 97], [228, 178, $bottom[$i], 163]] as [$sy, $by, $base, $ry]) {
                $pts = [];
                for ($k = 0; $k < 5; $k++) {
                    $pts[] = sprintf('%.1f,%.1f', $x + 12 * cos(deg2rad(-90 + 72 * $k)), $sy + 12 * sin(deg2rad(-90 + 72 * $k)));
                }
                $g .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#0f766e" stroke-width="3"/>', $x, $sy, $x, $by);
                $g .= sprintf('<polygon points="%s" fill="#ccfbf1" stroke="#0f766e" stroke-width="2"/>', implode(' ', $pts));
                $g .= sprintf('<rect x="%d" y="%d" width="32" height="30" rx="6" fill="%s"/><text x="%d" y="%d" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="18" font-weight="700" fill="#fff">%s</text>', $x - 16, $ry, $col[$base], $x, $by + 0.5, $base);
            }
            $offsets = in_array($top[$i], ['G', 'C'], true) ? [-8, 0, 8] : [-5, 5];
            foreach ($offsets as $dx) {
                $g .= sprintf('<line x1="%d" y1="128" x2="%d" y2="162" stroke="#475569" stroke-width="2.2" stroke-dasharray="3 3"/>', $x + $dx, $x + $dx);
            }
        }
        $g .= '<g font-family="Arial" font-size="15" font-weight="700" fill="#0f172a"><text x="14" y="66">5\'</text><text x="482" y="66">3\'</text><text x="14" y="232">3\'</text><text x="482" y="232">5\'</text></g>';
        $g .= self::tag('A', 160, 22, 160, 54) . self::tag('B', 232, 22, 214, 54) . self::tag('C', 52, 145, 105, 145)
            . self::tag('D', 258, 145, 218, 145) . self::tag('E', 362, 145, 318, 118);
        return self::wrap(510, 262, $g);
    }

    // ------------------------------------------------------------ mitosis

    private static function chromosomeX(float $x, float $y, float $rot): string
    {
        return sprintf('<path transform="translate(%g %g) rotate(%g)" d="M-8 -10 L8 10 M8 -10 L-8 10" stroke="#7c3aed" stroke-width="4.5" stroke-linecap="round"/>', $x, $y, $rot);
    }

    private static function mitosis(): string
    {
        $g = '';
        $cy = 92;
        $cell = fn (float $cx) => sprintf('<circle cx="%g" cy="%d" r="58" fill="#fff7ed" stroke="#c2410c" stroke-width="3.5"/>', $cx, $cy);
        // P: metaphase
        $cx = 70;
        $g .= $cell($cx) . sprintf('<circle cx="%g" cy="%d" r="2.5" fill="#64748b"/><circle cx="%g" cy="%d" r="2.5" fill="#64748b"/>', $cx, $cy - 54, $cx, $cy + 54);
        foreach ([-27, -9, 9, 27] as $dx) {
            $g .= sprintf('<line x1="%g" y1="%d" x2="%g" y2="%d" stroke="#94a3b8" stroke-width="1.2"/><line x1="%g" y1="%d" x2="%g" y2="%d" stroke="#94a3b8" stroke-width="1.2"/>', $cx, $cy - 54, $cx + $dx, $cy, $cx, $cy + 54, $cx + $dx, $cy);
            $g .= self::chromosomeX($cx + $dx, $cy, 0);
        }
        // Q: prophase
        $cx = 205;
        $g .= $cell($cx) . sprintf('<circle cx="%g" cy="%d" r="38" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="4 4"/>', $cx, $cy);
        foreach ([[-18, -14, 30], [14, -20, -20], [20, 8, 60], [-8, 14, 10], [-24, 10, -40], [4, -2, 80]] as [$dx, $dy, $r]) {
            $g .= self::chromosomeX($cx + $dx, $cy + $dy, $r);
        }
        // R: telophase (two daughter lobes)
        $cx = 340;
        $lobes = sprintf('<circle cx="%g" cy="%d" r="33" /><circle cx="%g" cy="%d" r="33" />', $cx, $cy - 28, $cx, $cy + 28);
        $g .= sprintf('<g fill="#fff7ed" stroke="#c2410c" stroke-width="3.5">%s</g><g fill="#fff7ed">%s</g>', $lobes, str_replace('r="33"', 'r="31.5"', $lobes));
        foreach ([-28, 28] as $dy) {
            $g .= sprintf('<circle cx="%g" cy="%d" r="17" fill="#e0e7ff" stroke="#4338ca" stroke-width="2.5"/>', $cx, $cy + $dy);
        }
        // S: anaphase
        $cx = 475;
        $g .= sprintf('<ellipse cx="%g" cy="%d" rx="56" ry="60" fill="#fff7ed" stroke="#c2410c" stroke-width="3.5"/>', $cx, $cy);
        foreach ([-27, -9, 9, 27] as $dx) {
            $g .= sprintf('<line x1="%g" y1="%d" x2="%g" y2="%d" stroke="#94a3b8" stroke-width="1.2"/><line x1="%g" y1="%d" x2="%g" y2="%d" stroke="#94a3b8" stroke-width="1.2"/>', $cx, $cy - 56, $cx + $dx, $cy - 24, $cx, $cy + 56, $cx + $dx, $cy + 24);
            $g .= sprintf('<path d="M%g %d l6 11 M%g %d l-6 11" stroke="#7c3aed" stroke-width="4.5" stroke-linecap="round"/>', $cx + $dx, $cy - 24, $cx + $dx, $cy - 24);
            $g .= sprintf('<path d="M%g %d l6 -11 M%g %d l-6 -11" stroke="#7c3aed" stroke-width="4.5" stroke-linecap="round"/>', $cx + $dx, $cy + 24, $cx + $dx, $cy + 24);
        }
        foreach ([70 => 'P', 205 => 'Q', 340 => 'R', 475 => 'S'] as $x => $l) {
            $g .= self::badge($l, $x, 178);
        }
        return self::wrap(545, 200, $g);
    }

    // ------------------------------------------------------------ organ systems

    private static function heart(): string
    {
        $blue = 'fill="#bfdbfe" stroke="#1d4ed8" stroke-width="3"';
        $red = 'fill="#fecaca" stroke="#b91c1c" stroke-width="3"';
        $g = '';
        // great vessels first, so the chambers sit on top of their roots
        $g .= '<path d="M135 125 V52" stroke="#1d4ed8" stroke-width="22" stroke-linecap="butt" fill="none"/><path d="M135 125 V52" stroke="#93c5fd" stroke-width="14" fill="none"/>';
        $g .= '<path d="M222 192 V84 M222 84 q-8 -24 -52 -28 M222 84 q8 -26 50 -30" stroke="#1d4ed8" stroke-width="18" fill="none" stroke-linecap="round"/><path d="M222 192 V84 M222 84 q-8 -24 -52 -28 M222 84 q8 -26 50 -30" stroke="#93c5fd" stroke-width="11" fill="none" stroke-linecap="round"/>';
        $g .= '<path d="M300 112 H362 M300 146 H362" stroke="#b91c1c" stroke-width="16" fill="none"/><path d="M300 112 H362 M300 146 H362" stroke="#fca5a5" stroke-width="9" fill="none"/>';
        $g .= "<rect x=\"112\" y=\"108\" width=\"92\" height=\"84\" rx=\"16\" $blue/>";
        $g .= "<rect x=\"262\" y=\"98\" width=\"92\" height=\"84\" rx=\"16\" $red/>";
        $g .= "<path d=\"M112 196 H214 V262 Q170 306 112 252 Z\" $blue/>";
        $g .= "<path d=\"M230 188 H352 V250 Q310 318 238 280 Q226 270 230 188 Z\" $red/>";
        // aorta in front of the pulmonary branches
        $g .= '<path d="M246 190 V92 Q246 40 300 40 Q372 40 372 104 V210" stroke="#b91c1c" stroke-width="18" fill="none" stroke-linecap="round"/><path d="M246 190 V92 Q246 40 300 40 Q372 40 372 104 V210" stroke="#fca5a5" stroke-width="11" fill="none" stroke-linecap="round"/>';
        // valves
        $g .= '<path d="M124 194 l14 17 M198 194 l-14 17" stroke="#334155" stroke-width="3.5" stroke-linecap="round"/><path d="M276 184 l13 17 M340 184 l-13 17" stroke="#334155" stroke-width="3.5" stroke-linecap="round"/>';
        $g .= '<g font-family="Arial" font-size="11" fill="#334155"><rect x="22" y="288" width="10" height="10" fill="#bfdbfe" stroke="#1d4ed8"/><text x="38" y="297">deoxygenated</text><rect x="118" y="288" width="10" height="10" fill="#fecaca" stroke="#b91c1c"/><text x="134" y="297">oxygenated</text></g>';
        $g .= self::tag('A', 52, 150, 130, 150) . self::tag('B', 420, 262, 320, 238) . self::tag('C', 420, 70, 372, 100)
            . self::tag('D', 190, 22, 205, 70) . self::tag('E', 428, 190, 340, 198) . self::tag('F', 76, 36, 135, 62);
        return self::wrap(460, 312, $g);
    }

    private static function neuron(): string
    {
        $g = '';
        foreach (['M84 100 L48 70 L22 60 M48 70 L38 38', 'M80 128 L38 132 L14 120 M38 132 L18 150', 'M92 148 L62 182 L36 192 M62 182 L58 210'] as $d) {
            $g .= sprintf('<path d="%s" fill="none" stroke="#b45309" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>', $d);
        }
        $g .= '<circle cx="112" cy="124" r="36" fill="#fde68a" stroke="#b45309" stroke-width="3.5"/><circle cx="112" cy="124" r="15" fill="#c7d2fe" stroke="#4338ca" stroke-width="2.5"/><circle cx="116" cy="121" r="4.5" fill="#4338ca"/>';
        $g .= '<path d="M144 112 L176 120 V128 L144 136 Z" fill="#fde68a" stroke="#b45309" stroke-width="3"/>';
        $g .= '<line x1="176" y1="124" x2="410" y2="124" stroke="#b45309" stroke-width="4.5"/>';
        foreach ([[190, 242], [252, 304], [314, 366]] as [$a, $b]) {
            $g .= sprintf('<rect x="%d" y="110" width="%d" height="28" rx="12" fill="#e2e8f0" stroke="#64748b" stroke-width="2.5"/><path d="M%d 118 V130 M%d 118 V130 M%d 118 V130" stroke="#94a3b8" stroke-width="2"/>', $a, $b - $a, $a + 18, ($a + $b) / 2, $b - 18);
        }
        foreach ([[455, 86], [466, 124], [455, 162]] as [$x, $y]) {
            $g .= sprintf('<path d="M410 124 L%d %d" stroke="#b45309" stroke-width="3.5" fill="none"/><circle cx="%d" cy="%d" r="9" fill="#fca5a5" stroke="#b91c1c" stroke-width="2.5"/>', $x - 9, $y, $x, $y);
        }
        $g .= self::tag('A', 40, 24, 33, 62) . self::tag('B', 90, 218, 106, 128) . self::tag('C', 277, 46, 277, 112)
            . self::tag('D', 247, 205, 247, 125) . self::tag('E', 500, 70, 462, 90) . self::tag('F', 160, 66, 160, 117);
        return self::wrap(530, 240, $g);
    }

    private static function nephron(): string
    {
        $g = '<rect x="0" y="0" width="500" height="196" rx="12" fill="#fdf2f8"/><rect x="0" y="196" width="500" height="144" fill="#fee2e2"/>';
        $g .= '<g font-family="Arial" font-size="12" font-weight="700" fill="#9d174d"><text x="372" y="22">CORTEX</text><text x="372" y="218">MEDULLA</text></g>';
        $tube = 'fill="none" stroke="#0369a1" stroke-linecap="round" stroke-linejoin="round"';
        $g .= '<path d="M30 60 H64 M30 92 H64" stroke="#dc2626" stroke-width="5" fill="none"/>';
        $g .= '<path d="M96 100 V104" ' . $tube . ' stroke-width="9"/>';
        $g .= '<circle cx="96" cy="80" r="32" fill="#fef3c7" stroke="#b45309" stroke-width="4"/>';
        $g .= '<path d="M78 82 q8 -22 18 -4 t16 -2 q-6 24 -22 12 t-12 -6" fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linecap="round"/>';
        $g .= '<path d="M96 112 q-16 12 0 24 t0 24 t0 24 t0 24" ' . $tube . ' stroke-width="9"/>';
        $g .= '<path d="M96 208 V296 Q128 336 160 296 V208" ' . $tube . ' stroke-width="8"/>';
        $g .= '<path d="M160 208 V196 q16 -12 0 -24 t0 -24 t0 -24 V112 H336" ' . $tube . ' stroke-width="9"/>';
        $g .= '<path d="M336 40 V320" stroke="#0284c7" stroke-width="17" stroke-linecap="round" fill="none"/><path d="M336 40 V320" stroke="#7dd3fc" stroke-width="10" stroke-linecap="round" fill="none"/>';
        $g .= self::tag('A', 40, 24, 82, 66) . self::tag('B', 44, 150, 90, 150) . self::tag('C', 54, 300, 100, 296)
            . self::tag('D', 218, 152, 160, 152) . self::tag('E', 430, 230, 340, 230);
        return self::wrap(500, 340, $g);
    }

    private static function synapse(): string
    {
        $g = '<rect x="250" y="40" width="50" height="220" fill="#f1f5f9"/>';
        $g .= '<path d="M300 40 H500 Q520 40 520 62 V238 Q520 260 500 260 H300 Z" fill="#c7d2fe" stroke="#4338ca" stroke-width="3"/>';
        $g .= '<path d="M10 100 Q10 40 70 40 H200 Q250 40 250 90 V210 Q250 260 200 260 H70 Q10 260 10 200 Z" fill="#fde68a" stroke="#b45309" stroke-width="3"/>';
        $g .= self::mito(88, 208, 8, 0.9);
        foreach ([[150, 95], [112, 142], [192, 150], [168, 205], [208, 98]] as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="14" fill="#fecaca" stroke="#b91c1c" stroke-width="2"/><circle cx="%d" cy="%d" r="2.5" fill="#b91c1c"/><circle cx="%d" cy="%d" r="2.5" fill="#b91c1c"/><circle cx="%d" cy="%d" r="2.5" fill="#b91c1c"/>', $x, $y, $x - 5, $y - 3, $x + 4, $y - 4, $x, $y + 5);
        }
        $g .= '<circle cx="246" cy="165" r="13" fill="#fecaca" stroke="#b91c1c" stroke-width="2"/>';
        foreach ([[264, 150], [277, 166], [270, 184], [284, 150], [286, 182], [262, 168]] as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="3.2" fill="#b91c1c"/>', $x, $y);
        }
        foreach ([88, 138, 190, 236] as $y) {
            $g .= sprintf('<path d="M300 %d H288 L277 %d M288 %d L277 %d" fill="none" stroke="#15803d" stroke-width="4.5" stroke-linecap="round"/>', $y, $y - 10, $y, $y + 10);
        }
        $g .= self::tag('A', 150, 20, 150, 81) . self::tag('B', 270, 284, 272, 228) . self::tag('C', 410, 284, 280, 190)
            . self::tag('D', 52, 20, 60, 70) . self::tag('E', 40, 284, 90, 212) . self::tag('F', 480, 20, 440, 110);
        return self::wrap(540, 300, $g);
    }

    private static function lacOperon(): string
    {
        $g = '<rect x="30" y="118" width="520" height="48" rx="6" fill="#e2e8f0" stroke="#64748b" stroke-width="2"/>';
        $boxes = [['lac i', 40, 72, '#fde68a'], ['P', 120, 44, '#bfdbfe'], ['O', 170, 38, '#fecaca'], ['lac Z', 216, 104, '#bbf7d0'], ['lac Y', 326, 86, '#c7d2fe'], ['lac A', 418, 92, '#fbcfe8']];
        foreach ($boxes as [$t, $x, $w, $fill]) {
            $g .= sprintf('<rect x="%d" y="124" width="%d" height="36" rx="4" fill="%s" stroke="#334155" stroke-width="2"/><text x="%g" y="142.5" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="15" font-weight="700" font-style="italic" fill="#0f172a">%s</text>', $x, $w, $fill, $x + $w / 2, h($t));
        }
        $g .= '<path d="M216 96 H500" stroke="#dc2626" stroke-width="3.5" fill="none"/><polygon points="512,96 498,88 498,104" fill="#dc2626"/><text x="364" y="84" text-anchor="middle" font-family="Arial" font-size="12" fill="#7f1d1d">single polycistronic mRNA</text>';
        $g .= '<path d="M76 118 V90" stroke="#64748b" stroke-width="2" stroke-dasharray="4 3" fill="none"/><circle cx="76" cy="58" r="30" fill="#fca5a5" stroke="#b91c1c" stroke-width="2.5"/><text x="76" y="58.5" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="12" font-weight="700" fill="#7f1d1d">protein</text>';
        foreach ([['A', 76], ['B', 142], ['C', 189], ['D', 268], ['E', 369], ['F', 464]] as [$l, $x]) {
            $g .= self::tag($l, $x, 232, $x, 167);
        }
        return self::wrap(580, 262, $g);
    }

    private static function replicationFork(): string
    {
        $du = [0.93, -0.37];
        $dl = [0.93, 0.37];
        $nu = [0.37 * 14, 0.93 * 14];   // towards the axis of the fork (below the upper arm)
        $nl = [0.37 * 14, -0.93 * 14];  // towards the axis (above the lower arm)
        $at = fn (array $p, array $d, float $t, array $n = [0, 0]) => [$p[0] + $d[0] * $t + $n[0], $p[1] + $d[1] * $t + $n[1]];
        $head = fn (array $tip, array $d, string $col) => sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="%s"/>', $tip[0], $tip[1], $tip[0] - $d[0] * 14 + $d[1] * 6, $tip[1] - $d[1] * 14 - $d[0] * 6, $tip[0] - $d[0] * 14 - $d[1] * 6, $tip[1] - $d[1] * 14 + $d[0] * 6, $col);
        $U0 = [190, 155];
        $L0 = [190, 175];
        $g = '<path d="M20 155 H190 M20 175 H190" stroke="#0f172a" stroke-width="4.5" stroke-linecap="round" fill="none"/>';
        $e = $at($U0, $du, 300);
        $g .= sprintf('<path d="M190 155 L%.1f %.1f" stroke="#0f172a" stroke-width="4.5" stroke-linecap="round"/>', $e[0], $e[1]);
        $e = $at($L0, $dl, 300);
        $g .= sprintf('<path d="M190 175 L%.1f %.1f" stroke="#0f172a" stroke-width="4.5" stroke-linecap="round"/>', $e[0], $e[1]);
        // leading strand: continuous, synthesised towards the fork
        $p1 = $at($U0, $du, 40, $nu);
        $p2 = $at($U0, $du, 270, $nu);
        $g .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#dc2626" stroke-width="4.5" stroke-linecap="round"/>', $p1[0], $p1[1], $p2[0], $p2[1]);
        $g .= $head([$p1[0] - $du[0] * 12, $p1[1] - $du[1] * 12], [-$du[0], -$du[1]], '#dc2626');
        // lagging strand: Okazaki fragments, each made away from the fork, each started by an RNA primer
        foreach ([0, 1, 2, 3] as $k) {
            $a = $at($L0, $dl, 40 + 68 * $k, $nl);
            $b = $at($L0, $dl, 40 + 68 * $k + 50, $nl);
            $g .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#2563eb" stroke-width="4.5" stroke-linecap="round"/>', $a[0], $a[1], $b[0], $b[1]);
            $g .= $head([$b[0] + $dl[0] * 12, $b[1] + $dl[1] * 12], $dl, '#2563eb');
            $g .= sprintf('<rect x="%.1f" y="%.1f" width="10" height="10" rx="2" fill="#f59e0b" stroke="#92400e" stroke-width="1.5"/>', $a[0] - 5, $a[1] - 5);
        }
        // enzymes
        $ex = $at($U0, $du, 110, [$nu[0] * 1.7, $nu[1] * 1.7]);
        $g .= sprintf('<ellipse cx="%.1f" cy="%.1f" rx="28" ry="15" transform="rotate(-20 %.1f %.1f)" fill="#bae6fd" fill-opacity="0.9" stroke="#0369a1" stroke-width="2.5"/>', $ex[0], $ex[1], $ex[0], $ex[1]);
        $g .= '<circle cx="196" cy="165" r="15" fill="#fde68a" stroke="#b45309" stroke-width="3"/>';
        $g .= '<path d="M150 308 H40" stroke="#475569" stroke-width="3"/><polygon points="30,308 44,300 44,316" fill="#475569"/><text x="40" y="327" font-family="Arial" font-size="12" fill="#475569">direction of fork movement</text>';
        $bx = $at($U0, $du, 205, $nu);
        $cx = $at($L0, $dl, 40 + 68 * 2 + 25, $nl);
        $dx = $at($L0, $dl, 40 + 68, $nl);
        $g .= self::tag('A', 118, 92, 196, 165) . self::tag('B', 452, 22, $bx[0], $bx[1]) . self::tag('C', 500, 296, $cx[0], $cx[1])
            . self::tag('D', 250, 290, $dx[0], $dx[1]) . self::tag('E', 232, 40, $ex[0], $ex[1]) . self::tag('F', 62, 108, 90, 155);
        return self::wrap(560, 336, $g);
    }

    /**
     * Pedigree from an AI/user spec. Each person: id, gen (1-4), sex M|F, affected, mate (id or 0),
     * father / mother (ids or 0). Listed left to right within a generation; couples must sit side by side.
     */
    public static function pedigreeFromSpec(array $people): string
    {
        if (count($people) < 2 || count($people) > 16) {
            throw new InvalidArgumentException('people');
        }
        $byId = [];
        $order = [];
        foreach ($people as $p) {
            $id = (int) ($p['id'] ?? 0);
            $gen = (int) ($p['gen'] ?? 0);
            if ($id < 1 || $id > 99 || isset($byId[$id]) || $gen < 1 || $gen > 4 || !in_array($p['sex'] ?? '', ['M', 'F'], true)) {
                throw new InvalidArgumentException('person');
            }
            $byId[$id] = ['id' => $id, 'gen' => $gen, 'sex' => $p['sex'], 'affected' => !empty($p['affected']),
                'mate' => (int) ($p['mate'] ?? 0), 'father' => (int) ($p['father'] ?? 0), 'mother' => (int) ($p['mother'] ?? 0)];
            $order[$gen][] = $id;
        }
        ksort($order);
        if (array_keys($order) !== range(1, count($order))) {
            throw new InvalidArgumentException('generations must be consecutive from 1');
        }
        $pos = [];
        foreach ($order as $ids) {
            foreach ($ids as $i => $id) {
                $pos[$id] = $i;
            }
        }
        foreach ($byId as $id => $p) {
            if ($p['mate'] > 0) {
                $m = $byId[$p['mate']] ?? null;
                if (!$m || $m['gen'] !== $p['gen'] || abs($pos[$id] - $pos[$m['id']]) !== 1) {
                    throw new InvalidArgumentException('mates must be adjacent in the same generation');
                }
                $byId[$m['id']]['mate'] = $id;
            }
        }
        $out = [];
        foreach ($byId as $id => $p) {
            $row = ['id' => $id, 'gen' => $p['gen'], 'sex' => $p['sex'], 'affected' => $p['affected']];
            if ($byId[$id]['mate'] > 0) {
                $row['mate'] = $byId[$id]['mate'];
            }
            if ($p['father'] > 0 || $p['mother'] > 0) {
                $f = $byId[$p['father']] ?? null;
                $m = $byId[$p['mother']] ?? null;
                if (!$f || !$m || $f['sex'] !== 'M' || $m['sex'] !== 'F' || $f['gen'] !== $p['gen'] - 1 || $m['gen'] !== $p['gen'] - 1
                    || abs($pos[$f['id']] - $pos[$m['id']]) !== 1) {
                    throw new InvalidArgumentException('parents must be an adjacent father and mother in the previous generation');
                }
                $row['parents'] = [$f['id'], $m['id']];
            }
            $out[] = $row;
        }
        // keep the generation / left-to-right order the author gave
        usort($out, fn ($a, $b) => [$a['gen'], $pos[$a['id']]] <=> [$b['gen'], $pos[$b['id']]]);
        return self::pedigree($out);
    }

    // ------------------------------------------------------------ variety: seeds, shuffled labels, colour variants

    /** Deterministic shuffle (own LCG so the global RNG state is untouched). */
    private static function shuffled(array $items, int $seed): array
    {
        $state = ($seed * 2654435761 + 12345) & 0x7fffffff;
        $items = array_values($items);
        for ($i = count($items) - 1; $i > 0; $i--) {
            $state = ($state * 1103515245 + 12345) & 0x7fffffff;
            $j = $state % ($i + 1);
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
        }
        return $items;
    }

    private static function rotateHue(string $hex, int $deg): string
    {
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split($hex, 2));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        if ($max === $min) {
            return '#' . $hex;
        }
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        $h = match (true) {
            $max === $r => (($g - $b) / $d + ($g < $b ? 6 : 0)),
            $max === $g => (($b - $r) / $d + 2),
            default     => (($r - $g) / $d + 4),
        } * 60;
        $h = fmod($h + $deg, 360);
        $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $f = function (float $t) use ($p, $q): float {
            $t = fmod($t + 1, 1);
            return match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default    => $p,
            };
        };
        return sprintf('#%02x%02x%02x', round($f($h / 360 + 1 / 3) * 255), round($f($h / 360) * 255), round($f($h / 360 - 1 / 3) * 255));
    }

    /** New colour scheme for diagrams whose colours carry no meaning. Label badges are left alone. */
    private static function recolor(string $svg, int $seed): string
    {
        $deg = [0, 55, 110, 200, 260, 310][$seed % 6];
        if ($seed === 0 || $deg === 0) {
            return $svg;
        }
        $parts = preg_split('/(<g class="tg">.*?<\/g>)/s', $svg, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $p) {
            if ($i % 2 === 0) {
                $parts[$i] = preg_replace_callback('/#([0-9a-fA-F]{6})\b/', fn ($m) => self::rotateHue($m[1], $deg), $p);
            }
        }
        return implode('', $parts);
    }

    /**
     * A catalog diagram with a seed-dependent look and a seed-dependent assignment of letters to parts.
     * Seed 0 is the plain diagram with the letters listed in CATALOG.
     *
     * @return array{svg:string,letters:array<string,string>} letters maps part name => letter shown on the picture
     */
    public static function render(string $id, int $seed = 0): array
    {
        if (!isset(self::CATALOG[$id])) {
            throw new InvalidArgumentException('unknown diagram');
        }
        $svg = self::draw($id, $seed);
        $parts = self::CATALOG[$id]['parts'];
        $keys = array_keys($parts);
        $map = array_combine($keys, $seed === 0 ? $keys : self::shuffled($keys, $seed));
        $svg = preg_replace_callback('/(fill="#b91c1c">)([A-Z])(<\/text>)/', fn ($m) => $m[1] . ($map[$m[2]] ?? $m[2]) . $m[3], $svg);
        $letters = [];
        foreach ($parts as $base => $name) {
            $letters[$name] = $map[$base];
        }
        return ['svg' => $svg, 'letters' => $letters];
    }

    /** @return ?array{svg:string,letters:?array<string,string>} a figure from an AI spec; $seed shuffles catalog labels */
    public static function figure(mixed $spec, int $seed = 0): ?array
    {
        if (is_array($spec) && ($spec['kind'] ?? '') === 'catalog') {
            try {
                return self::render((string) ($spec['diagramId'] ?? ''), $seed);
            } catch (InvalidArgumentException) {
                return null;
            }
        }
        $svg = self::fromSpec($spec);
        return $svg === null ? null : ['svg' => $svg, 'letters' => null];
    }

    /** Looks up the letter for a part name written by the question author ("Ovary", "bowman's capsule" ...). */
    public static function resolvePart(array $letters, string $name): ?string
    {
        $norm = fn (string $s) => trim(preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower($s)) ?? '');
        $want = $norm($name);
        if ($want === '') {
            return null;
        }
        $hits = [];
        foreach ($letters as $part => $letter) {
            $p = $norm($part);
            if ($p === $want) {
                return $letter;
            }
            if (str_contains($p, $want) || str_contains($want, $p)) {
                $hits[$letter] = true;
            }
        }
        return count($hits) === 1 ? array_key_first($hits) : null;
    }

    private static function draw(string $id, int $seed): string
    {
        $svg = match ($id) {
            'animal_cell' => self::animalCell(),
            'plant_cell'  => self::plantCell(),
            'dna_ladder'  => self::dnaLadder(),
            'mitosis'     => self::mitosis(),
            'human_heart' => self::heart(),
            'neuron'      => self::neuron(),
            'flower_ls'   => self::flower($seed),
            'nephron'     => self::nephron(),
            'synapse'     => self::synapse(),
            'lac_operon'  => self::lacOperon(),
            'replication_fork' => self::replicationFork(),
            'mitochondrion' => self::mitochondrion(),
            'chloroplast' => self::chloroplast(),
            'bacteriophage' => self::bacteriophage(),
            'human_eye'   => self::humanEye(),
            'sarcomere'   => self::sarcomere(),
            default       => throw new InvalidArgumentException('unknown diagram'),
        };
        return in_array($id, ['animal_cell', 'neuron', 'synapse', 'mitochondrion', 'sarcomere', 'bacteriophage', 'nephron', 'mitosis', 'lac_operon'], true) ? self::recolor($svg, $seed) : $svg;
    }

    // ------------------------------------------------------------ more labelled diagrams

    private static function mitochondrion(): string
    {
        $g = '<ellipse cx="240" cy="140" rx="195" ry="104" fill="#fde68a" stroke="#b45309" stroke-width="4.5"/>';
        $g .= '<ellipse cx="240" cy="140" rx="172" ry="82" fill="#fef3c7" stroke="#b45309" stroke-width="3.5"/>';
        foreach ([130, 180, 230, 280, 330] as $i => $x) {
            $up = $i % 2 === 1; // cristae fold in from alternate sides of the inner membrane
            $edge = 82 * sqrt(1 - (($x - 240) / 172) ** 2);
            $y0 = $up ? 140 + $edge : 140 - $edge;
            $g .= $up
                ? sprintf('<path d="M%d %.1f V%.1f a6 6 0 0 1 12 0 V%.1f" fill="none" stroke="#b45309" stroke-width="3.2"/>', $x - 6, $y0, $y0 - 44, $y0)
                : sprintf('<path d="M%d %.1f V%.1f a6 6 0 0 0 12 0 V%.1f" fill="none" stroke="#b45309" stroke-width="3.2"/>', $x - 6, $y0, $y0 + 44, $y0);
        }
        $g .= '<circle cx="240" cy="140" r="14" fill="none" stroke="#4338ca" stroke-width="3.2"/>';
        foreach ([[205, 150], [270, 125], [300, 160], [175, 118], [262, 168]] as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="2.8" fill="#78350f"/>', $x, $y);
        }
        $g .= self::tag('A', 30, 34, 80, 80) . self::tag('B', 32, 246, 91, 181) . self::tag('C', 332, 22, 230, 84)
            . self::tag('D', 448, 190, 300, 138) . self::tag('E', 452, 56, 399, 94) . self::tag('F', 240, 270, 240, 154);
        return self::wrap(480, 290, $g);
    }

    private static function chloroplast(): string
    {
        $g = '<ellipse cx="240" cy="145" rx="205" ry="108" fill="#bbf7d0" stroke="#166534" stroke-width="4.5"/>';
        $g .= '<ellipse cx="240" cy="145" rx="195" ry="98" fill="#dcfce7" stroke="#15803d" stroke-width="2.5"/>';
        $stacks = [[115, 122], [205, 168], [300, 112], [372, 160], [180, 92]];
        $g .= '<g stroke="#166534" stroke-width="2.4" fill="none"><path d="M141 126 L180 164"/><path d="M231 164 L274 118"/><path d="M326 116 L347 156"/><path d="M206 92 L276 108"/><path d="M141 120 L156 95"/></g>';
        foreach ($stacks as [$x, $y]) {
            foreach ([-20, -10, 0, 10, 20] as $dy) {
                $g .= sprintf('<ellipse cx="%d" cy="%d" rx="27" ry="6.5" fill="#4ade80" stroke="#166534" stroke-width="1.8"/>', $x, $y + $dy);
            }
        }
        $g .= '<ellipse cx="268" cy="205" rx="15" ry="9" fill="#fff" stroke="#94a3b8" stroke-width="2"/>';
        foreach ([[70, 160], [330, 205], [230, 80], [420, 135], [150, 195]] as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="4" fill="#facc15" stroke="#a16207"/>', $x, $y);
        }
        $g .= self::tag('A', 28, 52, 48, 108) . self::tag('B', 205, 272, 205, 188) . self::tag('C', 108, 250, 160, 146)
            . self::tag('D', 440, 52, 345, 130) . self::tag('E', 440, 235, 300, 102);
        return self::wrap(480, 290, $g);
    }

    private static function bacteriophage(): string
    {
        $g = '<polygon points="240,22 292,50 292,114 240,142 188,114 188,50" fill="#fde68a" stroke="#b45309" stroke-width="3.5"/>';
        $g .= '<path d="M214 60 q10 -12 20 0 t20 0 M210 84 q10 -12 20 0 t20 0 M214 108 q10 -12 20 0 t20 0" fill="none" stroke="#4338ca" stroke-width="3" stroke-linecap="round"/>';
        $g .= '<rect x="220" y="142" width="40" height="14" rx="3" fill="#fca5a5" stroke="#b91c1c" stroke-width="2"/>';
        $g .= '<rect x="226" y="156" width="28" height="92" fill="#bae6fd" stroke="#0369a1" stroke-width="2.5"/>';
        for ($y = 166; $y < 246; $y += 10) {
            $g .= sprintf('<line x1="226" y1="%d" x2="254" y2="%d" stroke="#0369a1" stroke-width="1.6"/>', $y, $y);
        }
        $g .= '<polygon points="200,248 280,248 294,264 280,280 200,280 186,264" fill="#c7d2fe" stroke="#4338ca" stroke-width="3"/>';
        foreach ([-1, 1] as $s) {
            foreach ([[0, 0], [1, 1], [2, 2]] as [$k]) {
                $x = 240 + $s * (46 + $k * 10);
                $g .= sprintf('<path d="M%d 268 L%d 318 L%d 308" fill="none" stroke="#475569" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>', 240 + $s * (46 + $k * 4), $x + $s * 22, $x + $s * 52);
            }
        }
        $g .= self::tag('A', 90, 40, 190, 70) . self::tag('F', 400, 60, 262, 84) . self::tag('B', 380, 138, 260, 149)
            . self::tag('C', 400, 205, 254, 200) . self::tag('D', 400, 266, 292, 264) . self::tag('E', 70, 300, 148, 306);
        return self::wrap(480, 340, $g);
    }

    private static function humanEye(): string
    {
        $g = '<circle cx="280" cy="150" r="108" fill="#f0f9ff" stroke="#94a3b8" stroke-width="9"/>';
        $g .= '<path d="M330 63.4 A100 100 0 0 1 330 236.6" fill="none" stroke="#dc2626" stroke-width="7"/>';
        $g .= '<path d="M196 88 Q116 150 196 212" fill="rgba(186,230,253,0.55)" stroke="#38bdf8" stroke-width="8" stroke-linecap="round"/>';
        $g .= '<ellipse cx="236" cy="150" rx="16" ry="34" fill="#bae6fd" stroke="#0369a1" stroke-width="3"/>';
        $g .= '<path d="M196 96 L218 100 L214 136 L204 136 Z M196 204 L218 200 L214 164 L204 164 Z" fill="#92400e" stroke="#78350f" stroke-width="2"/>';
        $g .= '<path d="M222 100 L240 120 M222 200 L240 180" stroke="#94a3b8" stroke-width="1.6"/>';
        $g .= '<rect x="384" y="138" width="92" height="24" rx="4" fill="#fde68a" stroke="#b45309" stroke-width="2.5"/>';
        $g .= self::tag('A', 60, 50, 160, 140) . self::tag('B', 150, 24, 207, 112) . self::tag('C', 224, 286, 236, 168)
            . self::tag('D', 470, 66, 366.6, 100) . self::tag('E', 500, 210, 440, 160) . self::tag('F', 130, 270, 226, 243);
        return self::wrap(540, 310, $g);
    }

    private static function sarcomere(): string
    {
        $g = '';
        foreach ([95, 130, 165, 200] as $y) {
            $g .= sprintf('<line x1="80" y1="%d" x2="268" y2="%d" stroke="#2563eb" stroke-width="4"/><line x1="500" y1="%d" x2="314" y2="%d" stroke="#2563eb" stroke-width="4"/>', $y, $y, $y, $y);
        }
        foreach ([112, 147, 182] as $y) {
            $g .= sprintf('<line x1="172" y1="%d" x2="408" y2="%d" stroke="#dc2626" stroke-width="9" stroke-linecap="round"/>', $y, $y);
            foreach (array_merge(range(180, 262, 13), range(318, 400, 13)) as $x) {
                $g .= sprintf('<path d="M%d %d l5 -7 M%d %d l5 7" stroke="#7f1d1d" stroke-width="2"/>', $x, $y, $x, $y);
            }
        }
        $g .= '<line x1="290" y1="92" x2="290" y2="214" stroke="#7c3aed" stroke-width="2.5" stroke-dasharray="5 4"/>';
        $g .= '<line x1="80" y1="66" x2="80" y2="224" stroke="#0f172a" stroke-width="7"/><line x1="500" y1="66" x2="500" y2="224" stroke="#0f172a" stroke-width="7"/>';
        $g .= '<g stroke="#475569" stroke-width="2.4" fill="none"><path d="M172 252 H408 M172 246 V258 M408 246 V258"/><path d="M80 252 H168 M80 246 V258 M168 246 V258"/><path d="M412 252 H500 M412 246 V258 M500 246 V258"/><path d="M270 236 H312 M270 230 V242 M312 230 V242"/></g>';
        $g .= self::tag('A', 40, 40, 80, 78) . self::tag('B', 150, 36, 150, 94) . self::tag('C', 340, 36, 330, 111)
            . self::tag('D', 400, 288, 291, 238) . self::tag('E', 62, 288, 124, 254) . self::tag('F', 230, 288, 230, 254);
        return self::wrap(580, 310, $g);
    }

    /** Flower in longitudinal section. $v changes petal colour, extra petals and the number of ovules and stamens. */
    private static function flower(int $v = 0): string
    {
        $palette = [['#f9a8d4', '#be185d'], ['#fde047', '#a16207'], ['#c4b5fd', '#6d28d9'], ['#fdba74', '#c2410c'], ['#f1f5f9', '#64748b']];
        [$petal, $petalEdge] = $palette[$v % 5];
        $extra = intdiv($v, 5) % 2 === 1;
        $stamens = intdiv($v, 10) % 2 === 1;
        $ovules = intdiv($v, 20) % 2 === 1 ? [[217, 228], [243, 228], [217, 250], [243, 250], [217, 272], [243, 272]] : [[217, 236], [243, 236], [217, 262], [243, 262]];
        $mirror = fn (string $inner) => $inner . '<g transform="translate(460 0) scale(-1 1)">' . $inner . '</g>';
        $g = '<rect x="221" y="296" width="18" height="44" fill="#4d7c0f"/>';
        $g .= $mirror('<path d="M205 288 Q146 286 128 236 Q172 258 208 268 Z" fill="#4ade80" stroke="#166534" stroke-width="2.5"/>');
        $g .= $mirror(sprintf('<path d="M208 268 Q104 250 90 142 Q156 184 216 238 Z" fill="%s" stroke="%s" stroke-width="2.5"/>', $petal, $petalEdge));
        if ($extra) {
            $g .= $mirror(sprintf('<path d="M212 250 Q150 200 170 110 Q206 170 222 232 Z" fill="%s" stroke="%s" stroke-width="2.2" fill-opacity="0.85"/>', $petal, $petalEdge));
        }
        $g .= $mirror('<path d="M214 276 L176 150" stroke="#65a30d" stroke-width="3.5" fill="none"/><ellipse cx="173" cy="132" rx="10" ry="20" transform="rotate(8 173 132)" fill="#fde047" stroke="#a16207" stroke-width="2.5"/>');
        if ($stamens) {
            $g .= $mirror('<path d="M210 278 L200 170" stroke="#65a30d" stroke-width="3" fill="none"/><ellipse cx="199" cy="152" rx="8" ry="16" transform="rotate(3 199 152)" fill="#fde047" stroke="#a16207" stroke-width="2.3"/>');
        }
        $g .= '<ellipse cx="230" cy="294" rx="40" ry="14" fill="#a3e635" stroke="#4d7c0f" stroke-width="2.5"/>';
        $g .= '<path d="M230 192 V100" stroke="#65a30d" stroke-width="7" fill="none"/><ellipse cx="230" cy="90" rx="15" ry="10" fill="#fb923c" stroke="#c2410c" stroke-width="2.5"/>';
        $g .= '<path d="M198 288 Q192 196 230 190 Q268 196 262 288 Z" fill="#bbf7d0" stroke="#15803d" stroke-width="3"/>';
        foreach ($ovules as [$x, $y]) {
            $g .= sprintf('<circle cx="%d" cy="%d" r="9" fill="#fde68a" stroke="#b45309" stroke-width="2"/><circle cx="%d" cy="%d" r="3" fill="#b45309"/>', $x, $y, $x, $y);
        }
        $ovuleTip = $ovules[1];
        $g .= self::tag('A', 330, 52, 238, 86) . self::tag('B', 405, 100, 289, 128) . self::tag('C', 400, 215, 258, 225)
            . self::tag('D', 56, 120, 120, 175) . self::tag('E', 56, 290, 150, 262) . self::tag('F', 400, 285, $ovuleTip[0] + 9, $ovuleTip[1]);
        return self::wrap(460, 345, $g);
    }

    // ------------------------------------------------------------ flow / cycle diagrams

    /** Point where the line from a box centre towards ($tx,$ty) leaves the box. */
    private static function boxEdge(float $cx, float $cy, float $hw, float $hh, float $tx, float $ty): array
    {
        $dx = $tx - $cx;
        $dy = $ty - $cy;
        $t = min($dx != 0 ? $hw / abs($dx) : INF, $dy != 0 ? $hh / abs($dy) : INF);
        return [$cx + $dx * $t, $cy + $dy * $t];
    }

    private static function arrow(array $a, array $b, string $label = '', string $color = '#475569'): string
    {
        $len = hypot($b[0] - $a[0], $b[1] - $a[1]);
        if ($len < 4) {
            return '';
        }
        $u = [($b[0] - $a[0]) / $len, ($b[1] - $a[1]) / $len];
        $s = sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="2.6"/>', $a[0], $a[1], $b[0] - $u[0] * 8, $b[1] - $u[1] * 8, $color);
        $s .= sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="%s"/>', $b[0], $b[1], $b[0] - $u[0] * 13 - $u[1] * 6, $b[1] - $u[1] * 13 + $u[0] * 6, $b[0] - $u[0] * 13 + $u[1] * 6, $b[1] - $u[1] * 13 - $u[0] * 6, $color);
        if ($label !== '') {
            $s .= sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="12" font-weight="700" fill="#7f1d1d" stroke="#f8fafc" stroke-width="4" paint-order="stroke">%s</text>', ($a[0] + $b[0]) / 2, ($a[1] + $b[1]) / 2 - 11, h($label));
        }
        return $s;
    }

    private static function flowBox(float $cx, float $cy, string $label): array
    {
        $words = explode(' ', $label);
        $lines = [$label];
        if (mb_strlen($label) > 15 && count($words) > 1) {
            $best = 0;
            $bestDiff = INF;
            for ($i = 1; $i < count($words); $i++) {
                $d = abs(mb_strlen(implode(' ', array_slice($words, 0, $i))) - mb_strlen(implode(' ', array_slice($words, $i))));
                if ($d < $bestDiff) {
                    $bestDiff = $d;
                    $best = $i;
                }
            }
            $lines = [implode(' ', array_slice($words, 0, $best)), implode(' ', array_slice($words, $best))];
        }
        $w = max(86, 8.4 * max(array_map('mb_strlen', $lines)) + 26);
        $hgt = count($lines) === 1 ? 42 : 58;
        $unknown = trim($label) === '?';
        $svg = sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%d" rx="10" fill="%s" stroke="%s" stroke-width="2.5"/>', $cx - $w / 2, $cy - $hgt / 2, $w, $hgt, $unknown ? '#fef9c3' : '#fee2e2', $unknown ? '#ca8a04' : '#b91c1c');
        foreach ($lines as $i => $ln) {
            $svg .= sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="%d" font-weight="700" fill="#0f172a">%s</text>', $cx, $cy + ($i - (count($lines) - 1) / 2) * 17, $unknown ? 22 : 14, h($ln));
        }
        return [$svg, $w / 2, $hgt / 2];
    }

    /**
     * Cycle or left-to-right chain of labelled boxes joined by arrows (food chains, nutrient cycles, pathways).
     * A node or edge label of "?" is drawn highlighted so questions can ask what is missing.
     *
     * @param string[] $nodes
     * @param string[] $edgeLabels one per arrow (a cycle has as many arrows as nodes; a chain one fewer)
     */
    public static function flow(string $layout, array $nodes, array $edgeLabels = [], string $title = ''): string
    {
        $nodes = array_values(array_map(fn ($n) => mb_substr(trim((string) $n), 0, 30), $nodes));
        $n = count($nodes);
        if ($n < 2 || $n > 8 || in_array('', $nodes, true) || ($layout === 'cycle' && $n < 3) || !in_array($layout, ['cycle', 'chain'], true)) {
            throw new InvalidArgumentException('flow');
        }
        $edgeLabels = array_map(fn ($e) => mb_substr(trim((string) $e), 0, 24), array_values($edgeLabels));
        $g = '';
        $top = $title !== '' ? 40 : 10;
        if ($title !== '') {
            $g .= sprintf('<text x="290" y="26" text-anchor="middle" font-family="Arial" font-size="15" font-weight="700" fill="#0f172a">%s</text>', h(mb_substr($title, 0, 70)));
        }
        $box = [];
        if ($layout === 'cycle') {
            $W = 580;
            $H = 400 + $top - 10;
            $cx = 290;
            $cy = $top + 190;
            foreach ($nodes as $i => $label) {
                $a = deg2rad(-90 + 360 * $i / $n);
                $box[$i] = array_merge([$cx + 215 * cos($a), $cy + 135 * sin($a)], self::flowBox($cx + 215 * cos($a), $cy + 135 * sin($a), $label));
            }
            $edges = $n;
        } else {
            $perRow = $n <= 4 ? $n : (int) ceil($n / 2);
            $rows = (int) ceil($n / $perRow);
            $W = max(420, 40 + $perRow * 170);
            $H = $top + 60 + $rows * 120;
            foreach ($nodes as $i => $label) {
                $r = intdiv($i, $perRow);
                $c = $i % $perRow;
                $x = 20 + 85 + $c * 170;
                $y = $top + 50 + $r * 120;
                $box[$i] = array_merge([$x, $y], self::flowBox($x, $y, $label));
            }
            $edges = $n - 1;
        }
        for ($i = 0; $i < $edges; $i++) {
            $j = ($i + 1) % $n;
            [$ax, $ay, $asvg, $ahw, $ahh] = $box[$i];
            [$bx, $by, , $bhw, $bhh] = $box[$j];
            $label = $edgeLabels[$i] ?? '';
            if ($layout === 'chain' && $by > $ay + 5) {
                // wrap to the second row: down, across, down
                $midY = ($ay + $by) / 2;
                $g .= sprintf('<path d="M%.1f %.1f V%.1f H%.1f V%.1f" fill="none" stroke="#475569" stroke-width="2.6"/>', $ax, $ay + $ahh, $midY, $bx, $by - $bhh - 10);
                $g .= sprintf('<polygon points="%.1f,%.1f %.1f,%.1f %.1f,%.1f" fill="#475569"/>', $bx, $by - $bhh, $bx - 6, $by - $bhh - 13, $bx + 6, $by - $bhh - 13);
                if ($label !== '') {
                    $g .= sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" font-family="Arial" font-size="12" font-weight="700" fill="#7f1d1d" stroke="#f8fafc" stroke-width="4" paint-order="stroke">%s</text>', ($ax + $bx) / 2, $midY - 7, h($label));
                }
                continue;
            }
            $g .= self::arrow(self::boxEdge($ax, $ay, $ahw + 3, $ahh + 3, $bx, $by), self::boxEdge($bx, $by, $bhw + 3, $bhh + 3, $ax, $ay), $label);
        }
        foreach ($box as $b) {
            $g .= $b[2];
        }
        return self::wrap((int) $W, (int) $H, $g);
    }

    // ------------------------------------------------------------ DNA -> mRNA -> protein

    private const CODON_AA = 'FFLLSSSSYY**CC*WLLLLPPPPHHQQRRRRIIIMTTTTNNKKSSRRVVVVAAAADDEEGGGG';
    private const AA_NAME = ['F' => 'Phe', 'L' => 'Leu', 'S' => 'Ser', 'Y' => 'Tyr', '*' => 'Stop', 'C' => 'Cys', 'W' => 'Trp', 'P' => 'Pro', 'H' => 'His', 'Q' => 'Gln', 'R' => 'Arg', 'I' => 'Ile', 'M' => 'Met', 'T' => 'Thr', 'N' => 'Asn', 'K' => 'Lys', 'V' => 'Val', 'A' => 'Ala', 'D' => 'Asp', 'E' => 'Glu', 'G' => 'Gly'];

    /** Amino acid (3-letter) for a codon written with T or U. */
    public static function aminoAcid(string $codon): string
    {
        $order = 'TCAG';
        $c = strtoupper(str_replace('U', 'T', $codon));
        return self::AA_NAME[self::CODON_AA[16 * strpos($order, $c[0]) + 4 * strpos($order, $c[1]) + strpos($order, $c[2])]];
    }

    /**
     * Transcription / translation strip. $template is the TEMPLATE strand written 3' to 5'; the coding
     * strand, the mRNA and (for "protein") the amino acids are computed here, so they are always right.
     */
    public static function dnaSequence(string $template, string $show = 'protein'): string
    {
        $template = strtoupper(trim($template));
        $n = strlen($template);
        if (!preg_match('/^[ACGT]{6,24}$/', $template) || !in_array($show, ['mrna', 'protein'], true) || ($show === 'protein' && $n % 3 !== 0)) {
            throw new InvalidArgumentException('sequence');
        }
        $comp = ['A' => 'T', 'T' => 'A', 'C' => 'G', 'G' => 'C'];
        $coding = implode('', array_map(fn ($b) => $comp[$b], str_split($template)));
        $mrna = str_replace('T', 'U', $coding);
        $col = ['A' => '#2563eb', 'T' => '#dc2626', 'U' => '#dc2626', 'G' => '#16a34a', 'C' => '#d97706'];
        $x0 = 150;
        $cw = 28;
        $cell = function (string $b, int $i, int $y) use ($col, $x0, $cw): string {
            return sprintf('<rect x="%d" y="%d" width="%d" height="28" rx="5" fill="%s"/><text x="%g" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="16" font-weight="700" fill="#fff">%s</text>', $x0 + $i * $cw, $y, $cw - 3, $col[$b], $x0 + $i * $cw + ($cw - 3) / 2, $y + 14.5, $b);
        };
        $g = '<g font-family="Arial" font-size="13" font-weight="700" fill="#334155">'
            . '<text x="14" y="63">Coding strand</text><text x="14" y="79" font-weight="400">5\' → 3\'</text>'
            . '<text x="14" y="133">Template strand</text><text x="14" y="149" font-weight="400">3\' → 5\'</text>'
            . '<text x="14" y="215">mRNA</text><text x="14" y="231" font-weight="400">5\' → 3\'</text></g>';
        for ($i = 0; $i < $n; $i++) {
            $g .= $cell($coding[$i], $i, 56) . $cell($template[$i], $i, 124) . $cell($mrna[$i], $i, 206);
            $g .= sprintf('<line x1="%g" y1="86" x2="%g" y2="122" stroke="#64748b" stroke-width="2" stroke-dasharray="3 3"/>', $x0 + $i * $cw + 12.5, $x0 + $i * $cw + 12.5);
            $g .= sprintf('<line x1="%g" y1="154" x2="%g" y2="204" stroke="#94a3b8" stroke-width="1.5"/>', $x0 + $i * $cw + 12.5, $x0 + $i * $cw + 12.5);
        }
        $height = 262;
        if ($show === 'protein') {
            $g .= '<text x="14" y="284" font-family="Arial" font-size="13" font-weight="700" fill="#334155">Amino acids</text>';
            $stopped = false;
            for ($k = 0; $k < $n / 3; $k++) {
                $codon = substr($mrna, 3 * $k, 3);
                $aa = self::aminoAcid($codon);
                $x = $x0 + 3 * $k * $cw;
                $g .= sprintf('<path d="M%g 238 v8 h%g v-8" fill="none" stroke="#475569" stroke-width="2"/>', $x, 3 * $cw - 3);
                $isStop = $aa === 'Stop';
                $g .= sprintf('<rect x="%g" y="252" width="%g" height="Z" rx="6" fill="%s" stroke="%s" stroke-width="2"/>', $x, 3 * $cw - 3, $isStop ? '#fecaca' : ($stopped ? '#e2e8f0' : '#fee2e2'), $isStop ? '#b91c1c' : '#b91c1c');
                $g = str_replace('height="Z"', 'height="30"', $g);
                $g .= sprintf('<text x="%g" y="267.5" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="14" font-weight="700" fill="%s">%s</text>', $x + (3 * $cw - 3) / 2, $stopped ? '#94a3b8' : '#7f1d1d', $stopped ? '–' : h($aa));
                $stopped = $stopped || $isStop;
            }
            $height = 312;
        }
        return self::wrap(max(440, $x0 + $n * $cw + 24), $height, $g);
    }

    // ------------------------------------------------------------ genetics: Punnett square

    /** @return string[] gametes, e.g. "RrYy" -> RY Ry rY ry */
    private static function gametes(string $genotype): array
    {
        $pairs = str_split($genotype, 2);
        $gametes = [''];
        foreach ($pairs as $p) {
            $next = [];
            foreach ($gametes as $g) {
                $next[] = $g . $p[0];
                $next[] = $g . $p[1];
            }
            $gametes = $next;
        }
        return $gametes;
    }

    private static function validGenotype(string $g): bool
    {
        if (!preg_match('/^([A-Za-z]{2}){1,2}$/', $g)) {
            return false;
        }
        $pairs = str_split($g, 2);
        foreach ($pairs as $p) {
            if (strcasecmp($p[0], $p[1]) !== 0) {
                return false;
            }
        }
        return count($pairs) === 1 || strcasecmp($pairs[0][0], $pairs[1][0]) !== 0;
    }

    /** Offspring genotype with each gene pair written dominant allele first (Tt, never tT). */
    private static function combine(string $a, string $b): string
    {
        $out = '';
        foreach (str_split($a) as $i => $allele) {
            $pair = [$allele, $b[$i]];
            usort($pair, fn ($x, $y) => ctype_upper($y) <=> ctype_upper($x));
            $out .= implode('', $pair);
        }
        return $out;
    }

    public static function punnett(string $p1, string $p2): string
    {
        if (!self::validGenotype($p1) || !self::validGenotype($p2) || strlen($p1) !== strlen($p2)
            || strcasecmp(substr($p1, 0, 1), substr($p2, 0, 1)) !== 0
            || (strlen($p1) === 4 && strcasecmp($p1[2], $p2[2]) !== 0)) {
            throw new InvalidArgumentException('genotype');
        }
        $top = self::gametes($p1);
        $side = self::gametes($p2);
        $n = count($top);
        $cw = $n === 2 ? 96 : 78;
        $ch = $n === 2 ? 62 : 48;
        $ox = 110;
        $oy = 70;
        $w = $ox + $n * $cw + 30;
        $h = $oy + $n * $ch + 24;
        $g = sprintf('<text x="%g" y="26" text-anchor="middle" font-family="Arial" font-size="13" font-weight="700" fill="#475569">Gametes of parent 1 (%s)</text>', $ox + $n * $cw / 2, h($p1));
        $g .= sprintf('<text x="12" y="%g" font-family="Arial" font-size="13" font-weight="700" fill="#475569">Parent 2</text><text x="12" y="%g" font-family="Arial" font-size="13" fill="#475569">(%s)</text>', $oy + $n * $ch / 2 - 6, $oy + $n * $ch / 2 + 12, h($p2));
        foreach ($top as $i => $gm) {
            $g .= sprintf('<rect x="%g" y="42" width="%g" height="%d" fill="#fee2e2" stroke="#b91c1c" stroke-width="1.5"/><text x="%g" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="17" font-weight="700" fill="#7f1d1d">%s</text>', $ox + $i * $cw, $cw, 28, $ox + $i * $cw + $cw / 2, 57, h($gm));
        }
        foreach ($side as $j => $gm) {
            $g .= sprintf('<rect x="%d" y="%g" width="%d" height="%g" fill="#fee2e2" stroke="#b91c1c" stroke-width="1.5"/><text x="%g" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="17" font-weight="700" fill="#7f1d1d">%s</text>', $ox - 44, $oy + $j * $ch, 44, $ch, $ox - 22, $oy + $j * $ch + $ch / 2, h($gm));
        }
        foreach ($side as $j => $sg) {
            foreach ($top as $i => $tg) {
                $g .= sprintf('<rect x="%g" y="%g" width="%g" height="%g" fill="#fff" stroke="#94a3b8" stroke-width="1.5"/><text x="%g" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="%d" fill="#0f172a">%s</text>', $ox + $i * $cw, $oy + $j * $ch, $cw, $ch, $ox + $i * $cw + $cw / 2, $oy + $j * $ch + $ch / 2, $n === 2 ? 20 : 17, h(self::combine($tg, $sg)));
            }
        }
        return self::wrap((int) $w, (int) $h, $g);
    }

    // ------------------------------------------------------------ ecology: pyramid

    /** @param array<int,array{name:string,value:string}> $levels bottom (producers) first */
    public static function pyramid(array $levels): string
    {
        $levels = array_values($levels);
        $n = count($levels);
        if ($n < 2 || $n > 6) {
            throw new InvalidArgumentException('levels');
        }
        $H = 44;
        $top = 16;
        $g = '';
        foreach ($levels as $i => $lv) {
            $name = trim((string) ($lv['name'] ?? ''));
            $value = trim((string) ($lv['value'] ?? ''));
            if ($name === '' || mb_strlen($name) > 40 || mb_strlen($value) > 20) {
                throw new InvalidArgumentException('level');
            }
            $row = $n - 1 - $i; // 0 = top of the picture
            $y = $top + $row * $H;
            $half0 = 20 + 110 * ($row / $n);
            $half1 = 20 + 110 * (($row + 1) / $n);
            $g .= sprintf('<polygon points="%g,%g %g,%g %g,%g %g,%g" fill="%s" stroke="#fff" stroke-width="3"/>', 160 - $half0, $y, 160 + $half0, $y, 160 + $half1, $y + $H, 160 - $half1, $y + $H, ['#b91c1c', '#dc2626', '#ef4444', '#f87171', '#fca5a5', '#fecaca'][$i]);
            $g .= sprintf('<text x="160" y="%g" text-anchor="middle" dominant-baseline="central" font-family="Arial" font-size="15" font-weight="700" fill="%s">%s</text>', $y + $H / 2, $i < 4 ? '#fff' : '#7f1d1d', h($value));
            $g .= sprintf('<text x="318" y="%g" dominant-baseline="central" font-family="Arial" font-size="14" fill="#0f172a">%s</text>', $y + $H / 2, h($name));
        }
        return self::wrap(520, $top * 2 + $n * $H, $g);
    }

    // ------------------------------------------------------------ genetics: pedigree

    /**
     * @param array<int,array{id:int,gen:int,sex:string,affected:bool,mate?:int,parents?:array{0:int,1:int}}> $people
     *        listed left to right within each generation; a married couple must be adjacent
     */
    public static function pedigree(array $people): string
    {
        $rows = [];
        foreach ($people as $p) {
            $rows[$p['gen']][] = $p;
        }
        ksort($rows);
        $sp = 86;
        $widest = max(array_map('count', $rows));
        $W = 90 + $widest * $sp;
        $pos = [];
        foreach ($rows as $gen => $row) {
            $start = 70 + (($widest - count($row)) * $sp) / 2 + $sp / 2;
            foreach ($row as $i => $p) {
                $pos[$p['id']] = [$start + $i * $sp, 56 + ($gen - 1) * 112];
            }
        }
        $g = '';
        foreach ($people as $p) {
            if (isset($p['mate']) && $p['id'] < $p['mate']) {
                [$x1, $y] = $pos[$p['id']];
                [$x2] = $pos[$p['mate']];
                $g .= sprintf('<line x1="%g" y1="%g" x2="%g" y2="%g" stroke="#0f172a" stroke-width="2.5"/>', $x1, $y, $x2, $y);
            }
        }
        $sibships = [];
        foreach ($people as $p) {
            if (isset($p['parents'])) {
                $sibships[implode('-', $p['parents'])][] = $p['id'];
            }
        }
        foreach ($sibships as $key => $kids) {
            [$f, $m] = array_map('intval', explode('-', $key));
            $mx = ($pos[$f][0] + $pos[$m][0]) / 2;
            $my = $pos[$f][1];
            $barY = $pos[$kids[0]][1] - 36;
            $xs = array_map(fn ($k) => $pos[$k][0], $kids);
            $g .= sprintf('<line x1="%g" y1="%g" x2="%g" y2="%g" stroke="#0f172a" stroke-width="2.5"/>', $mx, $my, $mx, $barY);
            $g .= sprintf('<line x1="%g" y1="%g" x2="%g" y2="%g" stroke="#0f172a" stroke-width="2.5"/>', min(array_merge($xs, [$mx])), $barY, max(array_merge($xs, [$mx])), $barY);
            foreach ($kids as $k) {
                $g .= sprintf('<line x1="%g" y1="%g" x2="%g" y2="%g" stroke="#0f172a" stroke-width="2.5"/>', $pos[$k][0], $barY, $pos[$k][0], $pos[$k][1] - 17);
            }
        }
        foreach ($people as $p) {
            [$x, $y] = $pos[$p['id']];
            $fill = $p['affected'] ? '#0f172a' : '#fff';
            $g .= $p['sex'] === 'M'
                ? sprintf('<rect x="%g" y="%g" width="32" height="32" fill="%s" stroke="#0f172a" stroke-width="2.5"/>', $x - 16, $y - 16, $fill)
                : sprintf('<circle cx="%g" cy="%g" r="17" fill="%s" stroke="#0f172a" stroke-width="2.5"/>', $x, $y, $fill);
            $g .= sprintf('<text x="%g" y="%g" text-anchor="middle" font-family="Arial" font-size="13" font-weight="700" fill="#475569">%d</text>', $x, $y + 36, $p['id']);
        }
        $numerals = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];
        foreach (array_keys($rows) as $gen) {
            $g .= sprintf('<text x="14" y="%g" font-family="Arial" font-size="15" font-weight="700" fill="#b91c1c">%s</text>', 61 + ($gen - 1) * 112, $numerals[$gen] ?? $gen);
        }
        $g .= '<g font-family="Arial" font-size="12" fill="#334155"><rect x="14" y="' . (56 + (count($rows) - 1) * 112 + 52) . '" width="12" height="12" fill="#0f172a"/><text x="32" y="' . (56 + (count($rows) - 1) * 112 + 62) . '">affected</text><rect x="100" y="' . (56 + (count($rows) - 1) * 112 + 52) . '" width="12" height="12" fill="#fff" stroke="#0f172a" stroke-width="2"/><text x="118" y="' . (56 + (count($rows) - 1) * 112 + 62) . '">unaffected   (square = male, circle = female)</text></g>';
        return self::wrap((int) max(380, $W), 56 + (count($rows) - 1) * 112 + 82, $g);
    }
}
