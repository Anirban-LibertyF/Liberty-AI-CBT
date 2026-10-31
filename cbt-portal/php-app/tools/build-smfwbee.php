<?php
// Builds database/smfwbee.sql: an SMFWBEE (State Medical Faculty of West Bengal Entrance Exam,
// for paramedical courses) style practice paper + the SMFWBEE preset. Mirrors the real syllabus
// split: Physics, Chemistry, Biology, and General Knowledge & Aptitude — unlike JENPAS-UG (pure
// PCB), SMFWBEE also tests GK/aptitude for its broader range of paramedical courses.
//
//   C:\xampp\php\php.exe tools\build-smfwbee.php   -> writes database/smfwbee.sql
declare(strict_types=1);

const PHYSICS = 'Physics';
const CHEMISTRY = 'Chemistry';
const BIOLOGY = 'Biology';
const GKAPT = 'General Knowledge & Aptitude';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ PHYSICS
    [PHYSICS, 'Units & Measurements', 'Easy', 'Which of the following is a fundamental (base) unit in the SI system?',
        ['Newton', 'Joule', 'Kilogram', 'Watt'], 'C',
        'Kilogram is one of the seven SI base units (for mass); Newton, Joule and Watt are all derived units.'],
    [PHYSICS, 'Kinematics', 'Medium', 'A car accelerates uniformly from 10 m/s to 30 m/s in 5 seconds. What is its acceleration?',
        ['2 m/s²', '4 m/s²', '5 m/s²', '8 m/s²'], 'B',
        'a = (v−u)/t = (30−10)/5 = 20/5 = 4 m/s².'],
    [PHYSICS, 'Pressure & Fluids', 'Medium', 'Blood pressure is commonly measured using which instrument?',
        ['Barometer', 'Sphygmomanometer', 'Thermometer', 'Hydrometer'], 'B',
        'A sphygmomanometer measures arterial blood pressure using an inflatable cuff and a pressure gauge.'],
    [PHYSICS, 'Heat & Thermometry', 'Easy', 'On the Celsius scale, the boiling point of water at standard atmospheric pressure is:',
        ['0°C', '37°C', '100°C', '212°C'], 'C',
        'Water boils at 100°C (212°F) at standard atmospheric pressure (1 atm).'],
    [PHYSICS, 'Electricity', 'Medium', 'The instrument used to measure electric current in a circuit is called:',
        ['Voltmeter', 'Ammeter', 'Galvanometer only', 'Ohmmeter'], 'B',
        'An ammeter is connected in series in a circuit to directly measure the current flowing through it.'],
    [PHYSICS, 'Optics', 'Medium', 'An X-ray machine primarily uses which type of radiation to produce images of internal body structures?',
        ['Ultraviolet rays', 'Infrared rays', 'X-rays (high-energy electromagnetic waves)', 'Radio waves'], 'C',
        'X-rays are high-energy, short-wavelength electromagnetic radiation that can penetrate soft tissue but are absorbed more by dense structures like bone, producing contrast images.'],
    [PHYSICS, 'Radioactivity & Nuclear Physics', 'Medium', 'Which radioactive isotope is commonly used in the medical treatment of thyroid disorders?',
        ['Cobalt-60', 'Iodine-131', 'Carbon-14', 'Uranium-235'], 'B',
        'Iodine-131 is selectively absorbed by the thyroid gland and is used both diagnostically and therapeutically in thyroid conditions.'],
    [PHYSICS, 'Sound & Ultrasound', 'Medium', 'Ultrasound imaging used in medical diagnostics (e.g. during pregnancy) uses sound waves of frequency:',
        ['Below 20 Hz', 'Between 20 Hz and 20,000 Hz (audible range)', 'Above 20,000 Hz (ultrasonic range)', 'Zero frequency (static)'], 'C',
        'Ultrasound imaging uses sound waves with frequencies above the human audible range (typically 2-18 MHz), which is why it is called "ultra" sound.'],

    // ------------------------------------------------------------ CHEMISTRY
    [CHEMISTRY, 'Atomic Structure', 'Easy', 'Isotopes of an element have the same number of protons but different numbers of:',
        ['Electrons', 'Neutrons', 'Protons', 'Valence electrons'], 'B',
        'Isotopes are atoms of the same element (same atomic number/protons) that differ in their number of neutrons, giving different mass numbers.'],
    [CHEMISTRY, 'Acids, Bases & Salts', 'Medium', 'An antacid tablet used to relieve acidity in the stomach works by:',
        ['Increasing stomach acid production', 'Neutralizing excess stomach acid (it is a mild base)', 'Converting acid into water only', 'Having no chemical effect'], 'B',
        'Antacids contain mild bases (like magnesium or aluminium hydroxide) that neutralize excess hydrochloric acid in the stomach, relieving acidity.'],
    [CHEMISTRY, 'Solutions & Concentration', 'Medium', 'Normal saline, commonly used in IV drips, is a solution of sodium chloride in water at a concentration of approximately:',
        ['0.9%', '5%', '9%', '90%'], 'A',
        'Normal saline is a 0.9% w/v solution of sodium chloride in water, isotonic with human blood plasma.'],
    [CHEMISTRY, 'Organic Chemistry', 'Medium', 'Which class of organic compound is the main component of table sugar (sucrose)?',
        ['Protein', 'Carbohydrate', 'Lipid', 'Nucleic acid'], 'B',
        'Sucrose is a disaccharide, a type of carbohydrate formed from the combination of glucose and fructose.'],
    [CHEMISTRY, 'Biomolecules', 'Medium', 'Which element, present in amino acids and proteins but typically absent in carbohydrates and fats, is used to test for protein content?',
        ['Carbon', 'Hydrogen', 'Nitrogen', 'Oxygen'], 'C',
        'Nitrogen is a characteristic constituent of amino acids (in the amine group) and is used in tests like the Kjeldahl method to estimate protein content.'],
    [CHEMISTRY, 'Electrochemistry & pH', 'Medium', 'The normal pH range of human blood is approximately:',
        ['6.0 to 6.5', '6.8 to 7.0', '7.35 to 7.45', '8.0 to 8.5'], 'C',
        'Human blood is slightly alkaline, normally maintained within a narrow range of 7.35 to 7.45 by buffer systems in the body.'],
    [CHEMISTRY, 'Everyday Chemistry', 'Easy', 'Chlorination is commonly used in water treatment plants primarily to:',
        ['Remove dissolved salts', 'Disinfect water by killing pathogenic microorganisms', 'Increase water hardness', 'Change the colour of water'], 'B',
        'Chlorine is a strong oxidizing disinfectant that kills bacteria and other pathogens in drinking water, making chlorination a standard water treatment step.'],

    // ------------------------------------------------------------ BIOLOGY
    [BIOLOGY, 'Cell Biology', 'Easy', 'Which cell organelle is responsible for protein synthesis?',
        ['Mitochondrion', 'Ribosome', 'Golgi apparatus', 'Lysosome'], 'B',
        'Ribosomes translate messenger RNA into polypeptide chains, making them the site of protein synthesis in the cell.'],
    [BIOLOGY, 'Human Physiology — Circulation', 'Medium', 'Which blood vessels carry oxygenated blood away from the heart to the rest of the body (except pulmonary circulation)?',
        ['Veins', 'Arteries', 'Capillaries', 'Venules'], 'B',
        'Arteries carry oxygenated blood away from the heart under high pressure, except for the pulmonary artery, which carries deoxygenated blood to the lungs.'],
    [BIOLOGY, 'Human Physiology — Respiration', 'Medium', 'The muscular, dome-shaped structure that separates the thoracic and abdominal cavities and assists in breathing is the:',
        ['Diaphragm', 'Pleura', 'Larynx', 'Epiglottis'], 'A',
        'The diaphragm contracts and flattens during inhalation, increasing thoracic volume and drawing air into the lungs.'],
    [BIOLOGY, 'Human Physiology — Digestion', 'Medium', 'Bile, which helps emulsify fats during digestion, is produced by the:',
        ['Pancreas', 'Liver', 'Stomach', 'Small intestine'], 'B',
        'Bile is produced by the liver and stored in the gallbladder; it emulsifies fats, increasing their surface area for enzymatic digestion.'],
    [BIOLOGY, 'Human Physiology — Excretory System', 'Medium', 'Urine is temporarily stored in which organ before being expelled from the body?',
        ['Kidney', 'Ureter', 'Urinary bladder', 'Urethra'], 'C',
        'The urinary bladder is a muscular sac that stores urine, which is periodically emptied through the urethra during urination.'],
    [BIOLOGY, 'Human Physiology — Nervous System', 'Medium', 'The basic structural and functional unit of the nervous system is the:',
        ['Nephron', 'Neuron', 'Axon only', 'Synapse only'], 'B',
        'A neuron (nerve cell) is the fundamental unit of the nervous system, specialized to transmit electrical and chemical signals.'],
    [BIOLOGY, 'Genetics', 'Medium', 'The genetic material DNA is primarily located within which part of a eukaryotic cell?',
        ['Cytoplasm', 'Nucleus', 'Cell membrane', 'Ribosome'], 'B',
        'In eukaryotic cells, DNA is organized into chromosomes located within the nucleus (with a small amount also in mitochondria).'],
    [BIOLOGY, 'Microbiology & Hygiene', 'Medium', 'Malaria, a disease transmitted through the bite of an infected female mosquito, is caused by which type of pathogen?',
        ['Bacterium', 'Virus', 'Protozoan parasite (Plasmodium)', 'Fungus'], 'C',
        'Malaria is caused by protozoan parasites of the genus Plasmodium, transmitted via the bite of infected female Anopheles mosquitoes.'],
    [BIOLOGY, 'Human Health', 'Medium', 'Which vitamin deficiency is commonly associated with poor blood clotting?',
        ['Vitamin A', 'Vitamin B12', 'Vitamin K', 'Vitamin E'], 'C',
        'Vitamin K is essential for the synthesis of several clotting factors in the liver; its deficiency leads to impaired blood clotting and excessive bleeding.'],

    // ------------------------------------------------------------ GENERAL KNOWLEDGE & APTITUDE
    [GKAPT, 'Static GK', 'Easy', 'The headquarters of the World Health Organization (WHO) is located in:',
        ['New York', 'Geneva', 'Paris', 'Vienna'], 'B',
        'The WHO, a specialized agency of the United Nations focused on international public health, is headquartered in Geneva, Switzerland.'],
    [GKAPT, 'Static GK', 'Medium', 'Which Indian state is known as the "Land of Five Rivers"?',
        ['Haryana', 'Punjab', 'Himachal Pradesh', 'Uttarakhand'], 'B',
        'Punjab, derived from Persian "panj" (five) and "aab" (water), is called the "Land of Five Rivers": Sutlej, Beas, Ravi, Chenab and Jhelum.'],
    [GKAPT, 'Health & Government Schemes', 'Medium', 'The Ayushman Bharat scheme, launched by the Government of India, primarily aims to provide:',
        ['Free higher education', 'Health insurance coverage to economically weaker families', 'Housing for the urban poor', 'Employment guarantee in rural areas'], 'B',
        'Ayushman Bharat (PM-JAY) provides health insurance cover of up to ₹5 lakh per family per year for secondary and tertiary hospitalization to economically vulnerable families.'],
    [GKAPT, 'Numerical Aptitude', 'Easy', 'If a worker is paid ₹450 for 6 hours of work, what is the hourly wage?',
        ['₹65', '₹70', '₹75', '₹80'], 'C',
        'Hourly wage = 450/6 = ₹75 per hour.'],
    [GKAPT, 'Numerical Aptitude', 'Medium', 'Find the missing number in the series: 4, 9, 16, 25, 36, ?',
        ['42', '45', '49', '54'], 'C',
        'The series consists of perfect squares: 2², 3², 4², 5², 6², 7² = 49.'],
    [GKAPT, 'Logical Reasoning', 'Medium', 'If all nurses are caregivers, and some caregivers are patient, which of the following must be true?',
        ['All nurses are patient', 'Some nurses may be patient', 'No nurses are patient', 'All caregivers are nurses'], 'B',
        'Since all nurses fall within the group of caregivers, and some caregivers are patient, it is possible (but not certain) that some nurses are among the patient caregivers — so "some nurses may be patient" is the only statement that must logically hold without overreaching.'],
    [GKAPT, 'Current Affairs — Health', 'Medium', 'The disease COVID-19 is primarily caused by which type of pathogen?',
        ['Bacterium', 'Virus (SARS-CoV-2)', 'Fungus', 'Parasite'], 'B',
        'COVID-19 is caused by the SARS-CoV-2 virus, a member of the coronavirus family, first identified in late 2019.'],
    [GKAPT, 'Abbreviations', 'Easy', 'In the medical context, what does the abbreviation "ICU" stand for?',
        ['Intensive Care Unit', 'Internal Care Unit', 'Immediate Care Unit', 'Isolated Care Unit'], 'A',
        'ICU stands for Intensive Care Unit, a specialized hospital department providing critical care for severely ill or injured patients.'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-smfwbee.php. Re-importing replaces the SMFWBEE paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'SMFWBEE';";
$out[] = "DELETE FROM exams WHERE slug = 'smfwbee';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('SMFWBEE', 'SMFWBEE', 'Paramedical Entrance (WB)', 90, 1, 0, 40, 12);";

$sections = [
    [PHYSICS, 'Units & Measurements, Kinematics, Pressure & Fluids, Heat & Thermometry, Electricity, Optics (X-rays), Radioactivity in Medicine, Ultrasound & Sound', 25],
    [CHEMISTRY, 'Atomic Structure, Acids Bases & Salts, Solutions & Concentration, Organic Chemistry, Biomolecules, Electrochemistry & pH, Everyday & Water Chemistry', 25],
    [BIOLOGY, 'Cell Biology, Human Physiology (Circulation, Respiration, Digestion, Excretion, Nervous System), Genetics, Microbiology & Hygiene, Human Health & Vitamins', 25],
    [GKAPT, 'Static GK, Health & Government Schemes, Numerical Aptitude, Logical Reasoning, Current Affairs in Health, Medical Abbreviations', 25],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SMFWBEE', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('smfwbee', 'SMFWBEE Paramedical Entrance Mock Test', 'demo', 'ready', 60, 1, 0, 0);";
$out[] = 'SET @smf_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@smf_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/smfwbee.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + SMFWBEE preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
