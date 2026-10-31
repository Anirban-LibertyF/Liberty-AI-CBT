<?php
// Builds database/jenpas.sql: a JENPAS-UG (Joint Entrance for Nursing & Paramedical Admission
// Services, West Bengal) style practice paper + the JENPAS-UG preset. Mirrors the real syllabus
// split: Physics, Chemistry, Biology — with Biology weighted heaviest, as in the real exam, and
// no negative marking (JENPAS-UG does not deduct marks for wrong answers).
//
//   C:\xampp\php\php.exe tools\build-jenpas.php   -> writes database/jenpas.sql
declare(strict_types=1);

const PHYSICS = 'Physics';
const CHEMISTRY = 'Chemistry';
const BIOLOGY = 'Biology';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ PHYSICS
    [PHYSICS, 'Units & Measurements', 'Easy', 'The SI unit of force is:',
        ['Joule', 'Newton', 'Pascal', 'Watt'], 'B',
        'Force is measured in Newtons (N), where 1 N = 1 kg·m/s², as defined by Newton\'s second law.'],
    [PHYSICS, 'Kinematics', 'Medium', 'A body falls freely from rest under gravity (g = 10 m/s²) for 4 seconds. How far does it fall?',
        ['40 m', '60 m', '80 m', '100 m'], 'C',
        's = ½gt² = ½ × 10 × 4² = ½ × 10 × 16 = 80 m.'],
    [PHYSICS, 'Laws of Motion', 'Easy', 'Which of Newton\'s laws explains why a passenger jerks backward when a bus suddenly accelerates forward?',
        ['First law (inertia)', 'Second law', 'Third law', 'Law of gravitation'], 'A',
        'The passenger\'s body tends to remain at rest due to inertia while the bus moves forward, so by Newton\'s First Law, the passenger jerks backward relative to the bus.'],
    [PHYSICS, 'Work, Energy & Power', 'Medium', 'A machine does 600 J of work in 5 seconds. What is its power?',
        ['100 W', '120 W', '3000 W', '600 W'], 'B',
        'Power = Work/Time = 600/5 = 120 W.'],
    [PHYSICS, 'Heat & Thermodynamics', 'Easy', 'The normal human body temperature, 37°C, is approximately equal to what temperature in Fahrenheit?',
        ['86°F', '98.6°F', '100°F', '212°F'], 'B',
        'Using F = (9/5)C + 32: (9/5)(37) + 32 = 66.6 + 32 = 98.6°F.'],
    [PHYSICS, 'Optics', 'Medium', 'Which type of lens is used to correct hypermetropia (far-sightedness)?',
        ['Concave lens', 'Convex lens', 'Cylindrical lens', 'Bifocal lens only'], 'B',
        'Hypermetropia occurs because the image forms behind the retina; a convex (converging) lens is used to converge light earlier and correct this.'],
    [PHYSICS, 'Electricity', 'Medium', 'According to Ohm\'s Law, if the voltage across a conductor is doubled while resistance remains constant, the current:',
        ['Halves', 'Remains the same', 'Doubles', 'Becomes zero'], 'C',
        'Ohm\'s Law states V = IR. If V doubles and R is constant, I must also double to maintain the relation.'],
    [PHYSICS, 'Magnetism', 'Easy', 'A device used to detect the presence of a magnetic field and its direction is called a:',
        ['Ammeter', 'Galvanometer', 'Compass', 'Voltmeter'], 'C',
        'A magnetic compass has a freely rotating needle that aligns with magnetic field lines, making it a simple magnetic field detector.'],
    [PHYSICS, 'Sound', 'Medium', 'The audible range of frequency for a normal human ear is approximately:',
        ['2 Hz to 200 Hz', '20 Hz to 20,000 Hz', '200 Hz to 2,00,000 Hz', '2,000 Hz to 2,00,000 Hz'], 'B',
        'The human ear can typically detect sound frequencies ranging from about 20 Hz to 20,000 Hz (20 kHz).'],
    [PHYSICS, 'Radioactivity', 'Medium', 'Which type of radioactive emission has the highest penetrating power?',
        ['Alpha particles', 'Beta particles', 'Gamma rays', 'All have equal penetrating power'], 'C',
        'Gamma rays are high-energy electromagnetic radiation with no mass or charge, giving them far greater penetrating power than alpha or beta particles.'],

    // ------------------------------------------------------------ CHEMISTRY
    [CHEMISTRY, 'Atomic Structure', 'Easy', 'The number of protons in the nucleus of an atom is called its:',
        ['Mass number', 'Atomic number', 'Valency', 'Isotope number'], 'B',
        'The atomic number (Z) is defined as the number of protons present in the nucleus of an atom.'],
    [CHEMISTRY, 'Chemical Bonding', 'Medium', 'Which type of bond is formed by the complete transfer of electrons from one atom to another?',
        ['Covalent bond', 'Ionic bond', 'Metallic bond', 'Hydrogen bond'], 'B',
        'An ionic bond forms when one atom completely transfers one or more electrons to another, creating oppositely charged ions that attract each other.'],
    [CHEMISTRY, 'Acids, Bases & Salts', 'Easy', 'A solution with a pH of 3 is:',
        ['Strongly basic', 'Neutral', 'Weakly basic', 'Acidic'], 'D',
        'The pH scale runs from 0 (strongly acidic) to 14 (strongly basic), with 7 being neutral. A pH of 3 is acidic.'],
    [CHEMISTRY, 'Chemical Reactions', 'Medium', 'The reaction Zn + CuSO4 → ZnSO4 + Cu is an example of a:',
        ['Combination reaction', 'Decomposition reaction', 'Displacement reaction', 'Neutralization reaction'], 'C',
        'Zinc displaces copper from copper sulfate solution because zinc is more reactive than copper, making this a displacement (specifically, a single displacement redox) reaction.'],
    [CHEMISTRY, 'Periodic Table', 'Medium', 'Elements in the same group of the periodic table have similar chemical properties because they have the same:',
        ['Atomic mass', 'Number of valence electrons', 'Number of neutrons', 'Number of shells'], 'B',
        'Elements in the same group share the same number of valence electrons, which largely determines their chemical reactivity and properties.'],
    [CHEMISTRY, 'Solutions', 'Medium', 'Molarity of a solution is defined as the number of moles of solute dissolved per:',
        ['Litre of solvent', 'Litre of solution', 'Kilogram of solvent', 'Kilogram of solution'], 'B',
        'Molarity (M) = moles of solute / volume of solution in litres.'],
    [CHEMISTRY, 'Organic Chemistry', 'Easy', 'Which of the following is the simplest hydrocarbon and the main constituent of natural gas?',
        ['Ethane', 'Methane', 'Propane', 'Butane'], 'B',
        'Methane (CH4) is the simplest alkane, consisting of one carbon bonded to four hydrogens, and is the primary component of natural gas.'],
    [CHEMISTRY, 'Biomolecules', 'Medium', 'Which element is essential in haemoglobin for the transport of oxygen in blood?',
        ['Calcium', 'Iron', 'Magnesium', 'Zinc'], 'B',
        'Haemoglobin contains an iron atom (Fe2+) in its heme group, which binds reversibly to oxygen molecules for transport in the blood.'],
    [CHEMISTRY, 'Electrochemistry', 'Medium', 'During electrolysis of water, oxygen gas is collected at the:',
        ['Cathode', 'Anode', 'Both electrodes equally', 'Neither electrode'], 'B',
        'In electrolysis of water, oxidation occurs at the anode, releasing oxygen gas, while reduction at the cathode releases hydrogen gas.'],
    [CHEMISTRY, 'Everyday Chemistry', 'Easy', 'Which gas is used in the sterilization of surgical instruments and medical equipment in hospitals?',
        ['Oxygen', 'Ethylene oxide', 'Nitrogen', 'Carbon dioxide'], 'B',
        'Ethylene oxide gas is widely used in hospitals to sterilize heat-sensitive medical equipment and surgical instruments.'],

    // ------------------------------------------------------------ BIOLOGY
    [BIOLOGY, 'Cell Biology', 'Easy', 'Which organelle is known as the "powerhouse of the cell"?',
        ['Nucleus', 'Ribosome', 'Mitochondrion', 'Golgi apparatus'], 'C',
        'Mitochondria carry out aerobic respiration to generate ATP, the cell\'s main energy currency, earning them the nickname "powerhouse of the cell".'],
    [BIOLOGY, 'Human Physiology — Circulation', 'Medium', 'How many chambers does the human heart have?',
        ['2', '3', '4', '5'], 'C',
        'The human heart has four chambers: two atria (upper) and two ventricles (lower), enabling separate pulmonary and systemic circulation.'],
    [BIOLOGY, 'Human Physiology — Respiration', 'Medium', 'The exchange of oxygen and carbon dioxide in the lungs occurs across the walls of the:',
        ['Bronchi', 'Trachea', 'Alveoli', 'Larynx'], 'C',
        'Alveoli are tiny thin-walled air sacs surrounded by capillaries, providing a large surface area for efficient gas exchange.'],
    [BIOLOGY, 'Human Physiology — Digestion', 'Medium', 'Which enzyme, secreted by the pancreas, digests proteins in the small intestine?',
        ['Amylase', 'Trypsin', 'Lipase', 'Pepsin'], 'B',
        'Trypsin is a proteolytic enzyme secreted by the pancreas (as inactive trypsinogen) that breaks down proteins into smaller peptides in the small intestine.'],
    [BIOLOGY, 'Human Physiology — Excretion', 'Medium', 'The functional and structural unit of the human kidney is the:',
        ['Neuron', 'Nephron', 'Alveolus', 'Villus'], 'B',
        'The nephron filters blood and forms urine, making it the basic functional unit of the kidney; each kidney contains about a million nephrons.'],
    [BIOLOGY, 'Human Physiology — Nervous System', 'Medium', 'Which part of the human brain is primarily responsible for coordinating balance and voluntary muscle movements?',
        ['Cerebrum', 'Cerebellum', 'Medulla oblongata', 'Hypothalamus'], 'B',
        'The cerebellum coordinates balance, posture, and fine control of voluntary movements, while the cerebrum handles higher cognitive functions.'],
    [BIOLOGY, 'Human Physiology — Endocrine System', 'Medium', 'Insulin, which regulates blood glucose levels, is secreted by which gland?',
        ['Thyroid', 'Pancreas', 'Adrenal gland', 'Pituitary gland'], 'B',
        'Insulin is produced by the beta cells of the islets of Langerhans in the pancreas, and it lowers blood glucose by promoting cellular uptake.'],
    [BIOLOGY, 'Genetics', 'Medium', 'A cross between a homozygous tall (TT) and a homozygous dwarf (tt) pea plant produces offspring that are:',
        ['All tall', 'All dwarf', 'Half tall, half dwarf', 'All of intermediate height'], 'A',
        'Since T (tall) is dominant over t (dwarf), all F1 offspring (Tt) will be tall, showing the dominant trait.'],
    [BIOLOGY, 'Reproduction', 'Medium', 'The process by which a sperm cell fuses with an egg cell to form a zygote is called:',
        ['Fission', 'Fertilization', 'Pollination', 'Implantation'], 'B',
        'Fertilization is the union of a sperm (male gamete) with an ovum (female gamete) to form a diploid zygote.'],
    [BIOLOGY, 'Microbiology', 'Medium', 'Which microorganism is responsible for causing tuberculosis in humans?',
        ['Plasmodium vivax', 'Mycobacterium tuberculosis', 'Vibrio cholerae', 'Salmonella typhi'], 'B',
        'Tuberculosis is caused by the bacterium Mycobacterium tuberculosis, which primarily affects the lungs.'],
    [BIOLOGY, 'Immunity & Vaccination', 'Medium', 'Which blood cells are primarily responsible for fighting infections in the human body?',
        ['Red blood cells', 'White blood cells', 'Platelets', 'Plasma proteins'], 'B',
        'White blood cells (leukocytes) are the primary components of the immune system, responsible for detecting and fighting pathogens.'],
    [BIOLOGY, 'Nutrition & Vitamins', 'Easy', 'Deficiency of Vitamin D in children leads to which bone disease?',
        ['Scurvy', 'Rickets', 'Osteoporosis only in adults', 'Anaemia'], 'B',
        'Vitamin D deficiency impairs calcium absorption, leading to soft, weak bones in children, a condition known as rickets.'],
    [BIOLOGY, 'Animal Kingdom', 'Medium', 'Which of the following is an example of a cold-blooded (poikilothermic) vertebrate?',
        ['Dog', 'Sparrow', 'Frog', 'Human'], 'C',
        'Frogs are amphibians and are cold-blooded (poikilothermic), meaning their body temperature varies with the environment, unlike warm-blooded mammals and birds.'],
    [BIOLOGY, 'Plant Physiology', 'Easy', 'The green pigment in plants that captures light energy for photosynthesis is called:',
        ['Xanthophyll', 'Chlorophyll', 'Carotene', 'Anthocyanin'], 'B',
        'Chlorophyll, located in chloroplasts, absorbs light energy (mainly red and blue wavelengths) and is essential for photosynthesis.'],
    [BIOLOGY, 'Ecology', 'Medium', 'In a food chain Grass → Grasshopper → Frog → Snake, the grasshopper is a:',
        ['Producer', 'Primary consumer', 'Secondary consumer', 'Decomposer'], 'B',
        'The grasshopper feeds directly on grass (the producer), making it a primary consumer (herbivore) in this food chain.'],
    [BIOLOGY, 'Human Health & Disease', 'Medium', 'Anaemia in humans is most commonly caused by a deficiency of:',
        ['Vitamin C', 'Iron', 'Calcium', 'Vitamin A'], 'B',
        'Iron is required for haemoglobin synthesis; its deficiency reduces the oxygen-carrying capacity of red blood cells, causing iron-deficiency anaemia, the most common form.'],
    [BIOLOGY, 'Blood Groups', 'Medium', 'A person with blood group AB is considered the "universal recipient" because their plasma lacks:',
        ['Both A and B antigens', 'Both anti-A and anti-B antibodies', 'Rh factor', 'White blood cells'], 'B',
        'AB blood group individuals have both A and B antigens on their red cells but no anti-A or anti-B antibodies in their plasma, so they can receive blood from any ABO group without an antibody reaction.'],
    [BIOLOGY, 'Skeletal System', 'Easy', 'What is the total number of bones in the adult human skeleton?',
        ['186', '206', '226', '246'], 'B',
        'The adult human skeleton typically consists of 206 bones (infants have more, around 270, which fuse together with growth).'],
    [BIOLOGY, 'Muscular System', 'Medium', 'Which type of muscle tissue is responsible for the involuntary, rhythmic contraction of the heart?',
        ['Skeletal muscle', 'Smooth muscle', 'Cardiac muscle', 'Voluntary striated muscle'], 'C',
        'Cardiac muscle is a specialized, striated, involuntary muscle type found only in the heart, enabling its continuous rhythmic contractions.'],
    [BIOLOGY, 'Senses', 'Medium', 'Which part of the human eye controls the amount of light entering it by adjusting the size of the pupil?',
        ['Cornea', 'Retina', 'Iris', 'Lens'], 'C',
        'The iris, the coloured part of the eye, contracts or dilates to control pupil size and regulate the amount of light entering the eye.'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-jenpas.php. Re-importing replaces the JENPAS-UG paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'JENPAS_UG';";
$out[] = "DELETE FROM exams WHERE slug = 'jenpas-ug';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('JENPAS_UG', 'JENPAS-UG', 'Nursing & Paramedical Entrance', 90, 1, 0, 40, 11);";

$sections = [
    [PHYSICS, 'Units & Measurements, Kinematics, Laws of Motion, Work Energy & Power, Heat & Thermodynamics, Optics, Electricity, Magnetism, Sound, Radioactivity', 25],
    [CHEMISTRY, 'Atomic Structure, Chemical Bonding, Acids Bases & Salts, Chemical Reactions, Periodic Table, Solutions, Organic Chemistry basics, Biomolecules, Electrochemistry, Everyday Chemistry', 25],
    [BIOLOGY, 'Cell Biology, Human Physiology (Circulation, Respiration, Digestion, Excretion, Nervous & Endocrine Systems), Genetics, Reproduction, Microbiology, Immunity, Nutrition, Animal Kingdom, Plant Physiology, Ecology, Human Health, Blood Groups, Skeletal & Muscular Systems, Senses', 50],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('JENPAS_UG', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('jenpas-ug', 'JENPAS-UG Nursing Entrance Mock Test', 'demo', 'ready', 60, 1, 0, 0);";
$out[] = 'SET @jenpas_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@jenpas_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/jenpas.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + JENPAS_UG preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
