<?php
// Builds database/wbjee.sql: a WBJEE (West Bengal Joint Entrance Examination) style practice
// paper + the WBJEE preset. Mirrors the real WBJEE syllabus split: Mathematics, Physics, Chemistry
// (Mathematics carries the heaviest weight in the real exam, as Paper-I is Mathematics-only).
//
//   C:\xampp\php\php.exe tools\build-wbjee.php   -> writes database/wbjee.sql
declare(strict_types=1);

const MATHS = 'Mathematics';
const PHYSICS = 'Physics';
const CHEMISTRY = 'Chemistry';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ MATHEMATICS
    [MATHS, 'Complex Numbers', 'Medium', 'What is the modulus of the complex number z = 3 + 4i?',
        ['5', '7', '12', '25'], 'A',
        '|z| = √(3² + 4²) = √(9+16) = √25 = 5.'],
    [MATHS, 'Quadratic Equations', 'Medium', 'If the roots of x² − 6x + k = 0 are equal, what is the value of k?',
        ['6', '9', '12', '36'], 'B',
        'For equal roots, discriminant = 0: 36 − 4k = 0 → k = 9.'],
    [MATHS, 'Matrices & Determinants', 'Medium', 'If A is a 3×3 matrix with |A| = 5, what is |2A|?',
        ['10', '20', '40', '25'], 'C',
        'For an n×n matrix, |kA| = kⁿ|A|. Here n=3, k=2: |2A| = 2³ × 5 = 8 × 5 = 40.'],
    [MATHS, 'Permutations & Combinations', 'Medium', 'In how many ways can 5 distinct books be arranged on a shelf?',
        ['60', '100', '120', '720'], 'C',
        'The number of arrangements of 5 distinct items is 5! = 120.'],
    [MATHS, 'Binomial Theorem', 'Medium', 'What is the coefficient of x² in the expansion of (1 + x)⁵?',
        ['5', '10', '15', '20'], 'B',
        'The coefficient of xʳ in (1+x)ⁿ is ⁿCᵣ. Here n=5, r=2: ⁵C₂ = 10.'],
    [MATHS, 'Sequences & Series', 'Easy', 'Find the sum of the first 20 terms of the arithmetic progression 3, 7, 11, 15, ...',
        ['780', '800', '820', '840'], 'A',
        'Sₙ = n/2 [2a + (n−1)d] = 20/2 [2(3) + 19(4)] = 10 [6 + 76] = 10 × 82 = 820.'],
    [MATHS, 'Straight Lines', 'Medium', 'Find the slope of the line passing through the points (2, 3) and (6, 11).',
        ['1', '2', '3', '4'], 'B',
        'Slope = (y2−y1)/(x2−x1) = (11−3)/(6−2) = 8/4 = 2.'],
    [MATHS, 'Circles', 'Medium', 'Find the radius of the circle given by the equation x² + y² − 6x + 8y = 0.',
        ['3', '4', '5', '7'], 'C',
        'Comparing with x² + y² + 2gx + 2fy + c = 0: g=−3, f=4, c=0. Radius = √(g²+f²−c) = √(9+16) = √25 = 5.'],
    [MATHS, 'Limits & Continuity', 'Medium', 'Evaluate: lim(x→0) (sin 3x)/x',
        ['0', '1', '3', '1/3'], 'C',
        'Using the standard limit lim(x→0) sin(kx)/x = k, here k = 3, so the limit is 3.'],
    [MATHS, 'Differentiation', 'Medium', 'If y = x³ − 3x² + 2x, find dy/dx at x = 1.',
        ['-1', '0', '1', '2'], 'A',
        "dy/dx = 3x² − 6x + 2. At x=1: 3(1) − 6(1) + 2 = 3 − 6 + 2 = −1."],
    [MATHS, 'Integration', 'Medium', 'Evaluate ∫(2x + 3) dx.',
        ['x² + 3x + C', 'x² + C', '2x² + 3x + C', 'x² + 3x² + C'], 'A',
        '∫2x dx = x² and ∫3 dx = 3x, so the integral is x² + 3x + C.'],
    [MATHS, 'Vectors & 3D Geometry', 'Medium', 'Find the magnitude of the vector a = 2i + 3j + 6k.',
        ['5', '6', '7', '9'], 'C',
        '|a| = √(2² + 3² + 6²) = √(4+9+36) = √49 = 7.'],
    [MATHS, 'Probability', 'Easy', 'Two dice are thrown together. What is the probability of getting a sum of 7?',
        ['1/6', '1/9', '5/36', '1/12'], 'A',
        'There are 6 favourable outcomes (1,6)(2,5)(3,4)(4,3)(5,2)(6,1) out of 36 total outcomes: 6/36 = 1/6.'],
    [MATHS, 'Trigonometric Equations', 'Medium', 'Find the general solution of the equation 2sinθ − 1 = 0.',
        ['θ = nπ + (−1)ⁿ π/6', 'θ = nπ + π/6', 'θ = 2nπ ± π/6', 'θ = nπ/6'], 'A',
        'sinθ = 1/2, and the general solution for sinθ = sinα is θ = nπ + (−1)ⁿα, with α = π/6.'],

    // ------------------------------------------------------------ PHYSICS
    [PHYSICS, 'Kinematics', 'Medium', 'A body starts from rest and accelerates uniformly at 2 m/s² for 5 seconds. What distance does it cover?',
        ['10 m', '20 m', '25 m', '50 m'], 'C',
        's = ut + ½at² = 0 + ½(2)(5²) = ½ × 2 × 25 = 25 m.'],
    [PHYSICS, 'Laws of Motion', 'Medium', 'A force of 10 N acts on a mass of 2 kg initially at rest. What is its velocity after 4 seconds?',
        ['5 m/s', '10 m/s', '20 m/s', '40 m/s'], 'C',
        'a = F/m = 10/2 = 5 m/s². v = u + at = 0 + 5×4 = 20 m/s.'],
    [PHYSICS, 'Work, Energy & Power', 'Medium', 'A body of mass 4 kg moving with a velocity of 5 m/s has kinetic energy of:',
        ['20 J', '50 J', '100 J', '10 J'], 'B',
        'KE = ½mv² = ½ × 4 × 5² = ½ × 4 × 25 = 50 J.'],
    [PHYSICS, 'Rotational Mechanics', 'Medium', 'The moment of inertia of a solid sphere of mass M and radius R about its diameter is:',
        ['MR²', '(2/5)MR²', '(1/2)MR²', '(7/5)MR²'], 'B',
        'The standard moment of inertia of a solid sphere about an axis through its diameter (centre) is (2/5)MR².'],
    [PHYSICS, 'Gravitation', 'Medium', 'The escape velocity of a body from the Earth\'s surface is approximately:',
        ['7.9 km/s', '9.8 km/s', '11.2 km/s', '15.0 km/s'], 'C',
        'The escape velocity from Earth\'s surface is approximately 11.2 km/s, derived from v_e = √(2GM/R).'],
    [PHYSICS, 'Thermodynamics', 'Medium', 'In an isothermal process, which of the following remains constant?',
        ['Pressure', 'Volume', 'Temperature', 'Entropy'], 'C',
        'An isothermal process occurs at constant temperature; pressure and volume can both change according to Boyle\'s Law.'],
    [PHYSICS, 'Electrostatics', 'Medium', 'Two point charges of +2μC and +3μC are placed 1 m apart. What is the approximate electrostatic force between them? (k = 9×10⁹ Nm²/C²)',
        ['0.054 N', '0.54 N', '5.4 N', '54 N'], 'A',
        'F = kq1q2/r² = (9×10⁹ × 2×10⁻⁶ × 3×10⁻⁶)/1² = 9×10⁹ × 6×10⁻¹² = 0.054 N.'],
    [PHYSICS, 'Current Electricity', 'Medium', 'Three resistors of 2Ω, 3Ω and 5Ω are connected in series. What is the equivalent resistance?',
        ['10 Ω', '0.97 Ω', '15 Ω', '1.03 Ω'], 'A',
        'In series, resistances simply add: 2 + 3 + 5 = 10 Ω.'],
    [PHYSICS, 'Magnetism', 'Medium', 'A current-carrying straight conductor produces a magnetic field whose field lines are:',
        ['Straight lines parallel to the conductor', 'Concentric circles around the conductor', 'Radial lines pointing outward', 'Zero everywhere'], 'B',
        'By the right-hand thumb rule, the magnetic field around a straight current-carrying conductor forms concentric circles centred on the wire.'],
    [PHYSICS, 'Optics', 'Medium', 'A convex lens of focal length 20 cm forms a real image at 60 cm from the lens. What is the object distance?',
        ['15 cm', '20 cm', '30 cm', '40 cm'], 'C',
        'Using 1/f = 1/v − 1/u (with sign convention u negative): 1/20 = 1/60 − 1/u → 1/u = 1/60 − 1/20 = (1−3)/60 = −1/30, so u = −30, meaning the object distance is 30 cm.'],
    [PHYSICS, 'Modern Physics', 'Medium', 'According to Einstein\'s photoelectric equation, the maximum kinetic energy of emitted electrons depends on:',
        ['Intensity of incident light only', 'Frequency of incident light and the work function', 'Wavelength of incident light only', 'Distance of the light source'], 'B',
        'Einstein\'s photoelectric equation KEmax = hν − φ shows that maximum kinetic energy depends on the frequency of incident light and the work function of the metal, not on intensity.'],
    [PHYSICS, 'Semiconductor Devices', 'Medium', 'In a p-n junction diode, the depletion region is formed due to:',
        ['Diffusion of majority carriers across the junction', 'Thermal agitation only', 'External applied voltage only', 'Recombination of photons'], 'A',
        'Near the junction, majority carriers diffuse across it and recombine, leaving a region depleted of free charge carriers — the depletion region.'],

    // ------------------------------------------------------------ CHEMISTRY
    [CHEMISTRY, 'Atomic Structure', 'Medium', 'What is the maximum number of electrons that can be accommodated in the M-shell (n=3)?',
        ['8', '10', '18', '32'], 'C',
        'The maximum number of electrons in a shell is 2n². For n=3: 2(3²) = 18.'],
    [CHEMISTRY, 'Chemical Bonding', 'Medium', 'What is the shape of the methane (CH4) molecule according to VSEPR theory?',
        ['Trigonal planar', 'Tetrahedral', 'Linear', 'Octahedral'], 'B',
        'Carbon in CH4 has four bond pairs and no lone pairs, giving a tetrahedral geometry with bond angles of 109.5°.'],
    [CHEMISTRY, 'States of Matter', 'Medium', 'According to the ideal gas equation PV = nRT, if temperature and number of moles are constant, pressure and volume are:',
        ['Directly proportional', 'Inversely proportional', 'Unrelated', 'Equal'], 'B',
        'At constant T and n, PV = constant (Boyle\'s Law), so pressure and volume are inversely proportional.'],
    [CHEMISTRY, 'Thermodynamics', 'Medium', 'For an exothermic reaction, the enthalpy change (ΔH) is:',
        ['Positive', 'Negative', 'Zero', 'Undefined'], 'B',
        'An exothermic reaction releases heat to the surroundings, so the enthalpy of the system decreases, giving a negative ΔH.'],
    [CHEMISTRY, 'Equilibrium', 'Medium', 'According to Le Chatelier\'s principle, increasing pressure on a gaseous equilibrium shifts the reaction towards the side with:',
        ['More moles of gas', 'Fewer moles of gas', 'No change', 'Equal moles always'], 'B',
        'Increasing pressure favours the side of the reaction that occupies less volume, i.e. the side with fewer moles of gas.'],
    [CHEMISTRY, 'Electrochemistry', 'Medium', 'In a galvanic cell, oxidation occurs at the:',
        ['Cathode', 'Anode', 'Salt bridge', 'External circuit'], 'B',
        'In any electrochemical cell, oxidation (loss of electrons) occurs at the anode, while reduction occurs at the cathode.'],
    [CHEMISTRY, 'Chemical Kinetics', 'Medium', 'For a first-order reaction, the unit of the rate constant k is:',
        ['mol L⁻¹ s⁻¹', 's⁻¹', 'L mol⁻¹ s⁻¹', 'mol² L⁻² s⁻¹'], 'B',
        'For a first-order reaction, rate = k[A], so k has units of (concentration/time)/concentration = time⁻¹, i.e. s⁻¹.'],
    [CHEMISTRY, 'Periodic Table', 'Easy', 'Across a period from left to right, atomic radius generally:',
        ['Increases', 'Decreases', 'Remains constant', 'First increases then decreases'], 'B',
        'Across a period, effective nuclear charge increases while the same shell is being filled, pulling electrons closer and decreasing atomic radius.'],
    [CHEMISTRY, 'Coordination Compounds', 'Medium', 'What is the coordination number of Co in [Co(NH3)6]Cl3?',
        ['3', '4', '6', '9'], 'C',
        'All six NH3 ligands are directly bonded to the central Co atom, giving a coordination number of 6.'],
    [CHEMISTRY, 'Organic Chemistry (GOC)', 'Medium', 'Which of the following is an example of a nucleophilic substitution reaction?',
        ['Addition of HBr to ethene', 'Hydrolysis of an alkyl halide by OH⁻', 'Combustion of methane', 'Dehydration of ethanol'], 'B',
        'Hydrolysis of an alkyl halide involves OH⁻ (a nucleophile) displacing the halide leaving group, a classic SN1/SN2 nucleophilic substitution.'],
    [CHEMISTRY, 'Hydrocarbons', 'Medium', 'Which reagent is used to distinguish alkenes from alkanes via a decolourisation test?',
        ['Bromine water', 'Dilute HCl', 'Sodium metal', 'Dilute NaOH'], 'A',
        'Alkenes decolourise bromine water rapidly due to addition reaction across the double bond, while alkanes do not react under these mild conditions.'],
    [CHEMISTRY, 'Biomolecules', 'Medium', 'Which of the following is a reducing sugar?',
        ['Sucrose', 'Glucose', 'Starch', 'Cellulose'], 'B',
        'Glucose has a free aldehyde group that can reduce Tollens\' or Fehling\'s reagent, making it a reducing sugar; sucrose, starch and cellulose do not show this free reactive group readily.'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-wbjee.php. Re-importing replaces the WBJEE paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'WBJEE';";
$out[] = "DELETE FROM exams WHERE slug = 'wbjee';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('WBJEE', 'WBJEE (PCM)', 'West Bengal Engineering Entrance', 90, 1, 0.25, 40, 10);";

$sections = [
    [MATHS, 'Complex Numbers, Quadratic Equations, Matrices & Determinants, Permutations & Combinations, Binomial Theorem, Sequences & Series, Coordinate Geometry, Calculus (Limits, Differentiation, Integration), Vectors & 3D Geometry, Probability, Trigonometry', 45],
    [PHYSICS, 'Kinematics, Laws of Motion, Work Energy & Power, Rotational Mechanics, Gravitation, Thermodynamics, Electrostatics, Current Electricity, Magnetism, Optics, Modern Physics, Semiconductor Devices', 28],
    [CHEMISTRY, 'Atomic Structure, Chemical Bonding, States of Matter, Thermodynamics, Equilibrium, Electrochemistry, Chemical Kinetics, Periodic Table, Coordination Compounds, General Organic Chemistry, Hydrocarbons, Biomolecules', 27],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBJEE', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('wbjee', 'WBJEE Mock Test (PCM)', 'demo', 'ready', 60, 1, 0.25, 0);";
$out[] = 'SET @wbjee_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@wbjee_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/wbjee.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + WBJEE preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
