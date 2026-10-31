<?php
// Builds database/gnm_anm.sql: a GNM & ANM (General Nursing & Midwifery / Auxiliary Nurse
// Midwifery) Admission Test style practice paper + the GNM & ANM preset. Mirrors the real exam's
// syllabus split: General Science, Arithmetic, General Knowledge & Current Affairs, and English —
// all at a Class X/XII level, simpler and broader than the B.Sc Nursing-level JENPAS-UG paper.
//
//   C:\xampp\php\php.exe tools\build-gnm-anm.php   -> writes database/gnm_anm.sql
declare(strict_types=1);

const SCIENCE = 'General Science';
const ARITH = 'Arithmetic';
const GK = 'General Knowledge & Current Affairs';
const ENGLISH = 'English';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ GENERAL SCIENCE
    [SCIENCE, 'Human Body', 'Easy', 'Which organ in the human body is primarily responsible for pumping blood throughout the body?',
        ['Liver', 'Heart', 'Lungs', 'Kidney'], 'B',
        'The heart is a muscular organ that pumps blood through the circulatory system, supplying oxygen and nutrients to the body.'],
    [SCIENCE, 'Human Body', 'Easy', 'The process by which plants prepare their own food using sunlight is called:',
        ['Respiration', 'Photosynthesis', 'Transpiration', 'Digestion'], 'B',
        'Photosynthesis is the process by which green plants use sunlight, water and carbon dioxide to produce glucose and oxygen.'],
    [SCIENCE, 'Physics Basics', 'Easy', 'Which of the following is used to measure body temperature?',
        ['Barometer', 'Thermometer', 'Stethoscope', 'Sphygmomanometer'], 'B',
        'A thermometer measures temperature; in clinical settings a clinical thermometer is used to measure human body temperature.'],
    [SCIENCE, 'Chemistry Basics', 'Easy', 'Common salt used in cooking is chemically known as:',
        ['Calcium carbonate', 'Sodium chloride', 'Sodium bicarbonate', 'Potassium nitrate'], 'B',
        'Common table salt is sodium chloride (NaCl), formed from the ionic bond between sodium and chlorine.'],
    [SCIENCE, 'Nutrition', 'Medium', 'Which vitamin is mainly obtained by the human body through exposure to sunlight?',
        ['Vitamin A', 'Vitamin C', 'Vitamin D', 'Vitamin K'], 'C',
        'Vitamin D is synthesized in the skin when it is exposed to ultraviolet (UVB) rays from sunlight.'],
    [SCIENCE, 'Microbiology & Hygiene', 'Medium', 'Washing hands with soap before meals primarily helps prevent the spread of:',
        ['Genetic disorders', 'Infectious diseases caused by germs', 'Vitamin deficiencies', 'Allergic reactions'], 'B',
        'Soap breaks down the lipid membrane of many germs and helps physically remove them from the skin, reducing the spread of infectious disease.'],
    [SCIENCE, 'Human Body', 'Medium', 'The gas that humans breathe in and is essential for respiration is:',
        ['Carbon dioxide', 'Nitrogen', 'Oxygen', 'Hydrogen'], 'C',
        'Oxygen is inhaled and used by cells during aerobic respiration to release energy from food.'],
    [SCIENCE, 'Diseases', 'Medium', 'Which of the following diseases is caused by a deficiency of iodine in the diet?',
        ['Scurvy', 'Goitre', 'Rickets', 'Night blindness'], 'B',
        'Iodine deficiency impairs thyroid hormone production, causing the thyroid gland to enlarge — a condition known as goitre.'],
    [SCIENCE, 'Physics Basics', 'Medium', 'Which simple machine is used in a pair of scissors?',
        ['Pulley', 'Lever', 'Inclined plane', 'Screw'], 'B',
        'A pair of scissors works as a pair of first-class levers, with the pivot (fulcrum) at the point where the blades cross.'],
    [SCIENCE, 'Human Body', 'Medium', 'Which blood cells help in clotting of blood when a person is injured?',
        ['Red blood cells', 'White blood cells', 'Platelets', 'Plasma'], 'C',
        'Platelets (thrombocytes) aggregate at the site of a wound and work with clotting factors to form a blood clot and stop bleeding.'],

    // ------------------------------------------------------------ ARITHMETIC
    [ARITH, 'Basic Arithmetic', 'Easy', 'What is the value of 15% of 200?',
        ['20', '25', '30', '35'], 'C',
        '15% of 200 = (15/100) × 200 = 30.'],
    [ARITH, 'Basic Arithmetic', 'Easy', 'Add: 248 + 365',
        ['603', '613', '623', '633'], 'B',
        '248 + 365 = 613.'],
    [ARITH, 'Fractions', 'Easy', 'Simplify: 3/4 + 1/4',
        ['1/2', '1', '4/8', '3/8'], 'B',
        '3/4 + 1/4 = 4/4 = 1.'],
    [ARITH, 'Ratio & Proportion', 'Medium', 'If 8 workers can complete a job in 12 days, how many days will 12 workers take to complete the same job (assuming equal work rate)?',
        ['6 days', '8 days', '9 days', '10 days'], 'B',
        'Total work = 8 × 12 = 96 worker-days. With 12 workers: 96/12 = 8 days.'],
    [ARITH, 'Average', 'Medium', 'Find the average of the numbers: 12, 18, 24, 30, 36',
        ['20', '22', '24', '26'], 'C',
        'Sum = 12+18+24+30+36 = 120. Average = 120/5 = 24.'],
    [ARITH, 'Simple Interest', 'Medium', 'Find the simple interest on ₹2,000 at 5% per annum for 2 years.',
        ['₹100', '₹150', '₹200', '₹250'], 'C',
        'SI = (P × R × T)/100 = (2000 × 5 × 2)/100 = ₹200.'],
    [ARITH, 'Percentage', 'Medium', 'A number increased by 20% becomes 120. What is the original number?',
        ['90', '96', '100', '110'], 'C',
        'Let the number be x. x × 1.20 = 120 → x = 120/1.2 = 100.'],
    [ARITH, 'Basic Arithmetic', 'Easy', 'What is the value of 7 × 8 − 6 ÷ 2?',
        ['53', '55', '56', '59'], 'A',
        'Following order of operations: 7×8=56, 6÷2=3, so 56−3=53.'],
    [ARITH, 'Time & Work', 'Medium', 'A tap can fill a tank in 6 hours. What fraction of the tank is filled in 2 hours?',
        ['1/6', '1/3', '1/2', '2/3'], 'B',
        'Rate = 1/6 of the tank per hour. In 2 hours: 2 × 1/6 = 1/3 of the tank.'],
    [ARITH, 'Unit Conversion', 'Easy', 'How many millilitres are there in 2.5 litres?',
        ['250 mL', '2,500 mL', '25,000 mL', '25 mL'], 'B',
        '1 litre = 1,000 mL, so 2.5 litres = 2.5 × 1000 = 2,500 mL.'],

    // ------------------------------------------------------------ GENERAL KNOWLEDGE & CURRENT AFFAIRS
    [GK, 'National Symbols', 'Easy', 'What is the national bird of India?',
        ['Parrot', 'Peacock', 'Sparrow', 'Eagle'], 'B',
        'The Indian peacock (Pavo cristatus) was declared the national bird of India in 1963.'],
    [GK, 'Geography', 'Easy', 'Kolkata is the capital city of which Indian state?',
        ['Bihar', 'Odisha', 'West Bengal', 'Jharkhand'], 'C',
        'Kolkata is the capital of West Bengal and one of India\'s major metropolitan cities.'],
    [GK, 'Polity', 'Medium', 'Who is the head of state of India?',
        ['Prime Minister', 'President', 'Chief Justice', 'Speaker of Lok Sabha'], 'B',
        'The President of India is the constitutional head of state, while the Prime Minister is the head of government.'],
    [GK, 'Sports', 'Easy', 'In which sport is the term "century" commonly used?',
        ['Football', 'Cricket', 'Hockey', 'Badminton'], 'B',
        'In cricket, a "century" refers to a batsman scoring 100 or more runs in a single innings.'],
    [GK, 'Health Schemes', 'Medium', 'Which government programme in India focuses on immunizing children against preventable diseases?',
        ['Mid-Day Meal Scheme', 'Universal Immunization Programme', 'Beti Bachao Beti Padhao', 'Swachh Bharat Abhiyan'], 'B',
        'The Universal Immunization Programme (UIP) is India\'s nationwide initiative to vaccinate children and pregnant women against vaccine-preventable diseases.'],
    [GK, 'Static GK', 'Medium', 'The Howrah Bridge, a famous cantilever bridge, is located over which river?',
        ['Ganga', 'Yamuna', 'Hooghly', 'Brahmaputra'], 'C',
        'The Howrah Bridge (Rabindra Setu) spans the Hooghly River, connecting Kolkata and Howrah in West Bengal.'],
    [GK, 'Current Affairs', 'Medium', 'The "Beti Bachao Beti Padhao" scheme in India primarily aims to:',
        ['Promote girl child education and prevent gender-biased sex selection', 'Provide free housing to women', 'Fund women entrepreneurs', 'Reserve seats for women in Parliament'], 'A',
        'Beti Bachao Beti Padhao aims to address the declining child sex ratio and promote the education and welfare of girls across India.'],
    [GK, 'National Symbols', 'Easy', 'What is the national emblem of India adapted from?',
        ['The Ashoka Chakra', 'The Lion Capital of Ashoka at Sarnath', 'The Red Fort', 'The Taj Mahal'], 'B',
        'India\'s national emblem is adapted from the Lion Capital of Ashoka at Sarnath, featuring four lions standing back to back.'],
    [GK, 'Important Days', 'Medium', 'International Nurses Day is observed every year on:',
        ['5th September', '12th May', '1st December', '7th April'], 'B',
        'International Nurses Day is celebrated on 12th May, the birth anniversary of Florence Nightingale, the founder of modern nursing.'],
    [GK, 'Important Personalities', 'Medium', 'Florence Nightingale, regarded as the founder of modern nursing, is also known by which nickname?',
        ['The Iron Lady', 'The Lady with the Lamp', 'The Maid of Orleans', 'The Great Soul'], 'B',
        'Florence Nightingale earned the nickname "The Lady with the Lamp" for her nightly rounds tending wounded soldiers during the Crimean War.'],

    // ------------------------------------------------------------ ENGLISH
    [ENGLISH, 'Grammar', 'Easy', 'Choose the correct form of the verb: She _____ to the hospital every morning.',
        ['go', 'goes', 'going', 'gone'], 'B',
        'With a third-person singular subject ("She") in the simple present tense, the verb takes an -s/-es ending: "goes".'],
    [ENGLISH, 'Vocabulary', 'Easy', 'Choose the word nearest in meaning to "CAREFUL":',
        ['Careless', 'Cautious', 'Hasty', 'Reckless'], 'B',
        '"Careful" means taking care to avoid mistakes or harm, which is closest in meaning to "cautious".'],
    [ENGLISH, 'Antonyms', 'Easy', 'Choose the word opposite in meaning to "HEALTHY":',
        ['Fit', 'Sick', 'Strong', 'Active'], 'B',
        '"Healthy" means being in good physical condition; its opposite is "sick" (being ill or unwell).'],
    [ENGLISH, 'Grammar', 'Medium', 'Fill in the blank with the correct article: She is _____ honest nurse.',
        ['a', 'an', 'the', 'no article needed'], 'B',
        '"Honest" begins with a silent "h", so the word is pronounced starting with a vowel sound, requiring the article "an".'],
    [ENGLISH, 'Spelling', 'Easy', 'Choose the correctly spelled word:',
        ['Recieve', 'Receive', 'Receve', 'Receeve'], 'B',
        'The correct spelling follows the rule "i before e except after c" is reversed here since it follows "c": "receive" is correct.'],
    [ENGLISH, 'Comprehension / Usage', 'Medium', 'Choose the sentence that is grammatically correct:',
        ['He don\'t like medicine.', 'He doesn\'t likes medicine.', 'He doesn\'t like medicine.', 'He not like medicine.'], 'C',
        'With the third-person singular subject "He" and the auxiliary "doesn\'t", the main verb stays in its base form: "doesn\'t like".'],
    [ENGLISH, 'One Word Substitution', 'Medium', 'A person who treats sick people is called a:',
        ['Patient', 'Physician', 'Pharmacist', 'Pathologist'], 'B',
        'A "physician" is a qualified practitioner of medicine who treats illness and disease in patients.'],
    [ENGLISH, 'Vocabulary', 'Medium', 'Choose the word nearest in meaning to "ASSIST":',
        ['Oppose', 'Help', 'Ignore', 'Delay'], 'B',
        '"Assist" means to help someone, making "help" the closest synonym.'],
    [ENGLISH, 'Grammar', 'Medium', 'Choose the correct plural form of "CHILD":',
        ['Childs', 'Childes', 'Children', 'Childrens'], 'C',
        '"Child" has an irregular plural form: "children", not formed by simply adding -s or -es.'],
    [ENGLISH, 'Prepositions', 'Medium', 'The nurse administered the injection _____ the patient\'s arm.',
        ['in', 'on', 'at', 'into'], 'B',
        'The standard preposition used for a location on the surface of the body, such as an arm, is "on": "on the patient\'s arm".'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-gnm-anm.php. Re-importing replaces the GNM & ANM paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'GNM_ANM';";
$out[] = "DELETE FROM exams WHERE slug = 'gnm-anm';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('GNM_ANM', 'GNM & ANM', 'Nursing Admission Test (WB)', 90, 1, 0, 40, 13);";

$sections = [
    [SCIENCE, 'Human Body, Nutrition, Diseases, Hygiene, basic Physics and Chemistry relevant to daily life and nursing care', 30],
    [ARITH, 'Basic Arithmetic, Fractions, Percentage, Ratio & Proportion, Average, Simple Interest, Time & Work, Unit Conversion', 25],
    [GK, 'National Symbols, Geography, Polity, Sports, Health Schemes, Static GK, Current Affairs, Important Days & Personalities in Nursing', 25],
    [ENGLISH, 'Grammar, Vocabulary, Antonyms & Synonyms, Spelling, One Word Substitution, Prepositions, Basic Comprehension & Usage', 20],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('GNM_ANM', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('gnm-anm', 'GNM & ANM Admission Test Mock', 'demo', 'ready', 60, 1, 0, 0);";
$out[] = 'SET @gnm_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@gnm_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/gnm_anm.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + GNM_ANM preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
