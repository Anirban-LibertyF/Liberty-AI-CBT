<?php
// Builds database/wbcs_prelims.sql: a WBCS (West Bengal Civil Service) Preliminary Examination
// style practice paper + the WBCS Prelims preset. Mirrors the real WBCS Prelims syllabus: English
// Composition, General Science, Current Events, History, Geography, Indian Polity & Economy,
// Indian National Movement, General Mental Ability (Reasoning), Arithmetic.
//
//   C:\xampp\php\php.exe tools\build-wbcs.php   -> writes database/wbcs_prelims.sql
declare(strict_types=1);

const ENGLISH = 'English Composition';
const SCIENCE = 'General Science';
const CURRENT = 'Current Events (National & International)';
const HISTORY = 'History of India';
const GEOGRAPHY = 'Geography (India & West Bengal)';
const POLITY = 'Indian Polity & Economy';
const MOVEMENT = 'Indian National Movement';
const REASONING = 'General Mental Ability & Reasoning';
const ARITH = 'Arithmetic';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ ENGLISH COMPOSITION
    [ENGLISH, 'Synonyms', 'Easy', 'Choose the word most nearly similar in meaning to "AUGMENT":',
        ['Diminish', 'Increase', 'Postpone', 'Negate'], 'B',
        '"Augment" means to make something greater in size, number, or strength — a synonym of "increase".'],
    [ENGLISH, 'Antonyms', 'Easy', 'Choose the word most nearly opposite in meaning to "BENEVOLENT":',
        ['Generous', 'Kind', 'Malevolent', 'Charitable'], 'C',
        '"Benevolent" means well-meaning and kindly; its opposite is "malevolent" (having or showing a wish to do evil).'],
    [ENGLISH, 'One Word Substitution', 'Medium', 'A person who is indifferent to pleasure or pain is called:',
        ['Stoic', 'Cynic', 'Pacifist', 'Skeptic'], 'A',
        'A "stoic" is a person who can endure pain or hardship without showing their feelings or complaining.'],
    [ENGLISH, 'Idioms & Phrases', 'Medium', 'The idiom "to bell the cat" means:',
        ['To take an impossible risk', 'To face danger bravely on behalf of others', 'To deceive someone cleverly', 'To waste time on trivial matters'], 'B',
        '"To bell the cat" means to undertake a risky task on behalf of a group, from the fable where the mice agree a cat should be belled, but none dares to do it.'],
    [ENGLISH, 'Error Spotting', 'Medium', 'Identify the part with an error: "Neither of the two candidates / have submitted / their application / before the deadline."',
        ['Neither of the two candidates', 'have submitted', 'their application', 'before the deadline'], 'B',
        '"Neither" is singular and takes a singular verb, so it should be "has submitted", not "have submitted".'],
    [ENGLISH, 'Fill in the Blanks', 'Easy', 'She has been working in this office _____ 2015.',
        ['for', 'since', 'from', 'at'], 'B',
        '"Since" is used with a specific point in time (2015) to show when a continuous action started; "for" is used with a duration.'],

    // ------------------------------------------------------------ GENERAL SCIENCE
    [SCIENCE, 'Physics', 'Easy', 'The SI unit of electric current is:',
        ['Volt', 'Ohm', 'Ampere', 'Watt'], 'C',
        'Electric current is measured in Amperes (A), one of the seven base SI units.'],
    [SCIENCE, 'Chemistry', 'Medium', 'Which gas is commonly known as "laughing gas"?',
        ['Nitrous oxide', 'Nitrogen dioxide', 'Carbon monoxide', 'Methane'], 'A',
        'Nitrous oxide (N2O) produces mild euphoria and is informally called "laughing gas"; it is used as an anaesthetic.'],
    [SCIENCE, 'Biology', 'Easy', 'The functional unit of the kidney is called the:',
        ['Neuron', 'Nephron', 'Alveolus', 'Axon'], 'B',
        'The nephron is the structural and functional unit of the kidney responsible for filtering blood and forming urine.'],
    [SCIENCE, 'Biology', 'Medium', 'Vitamin C deficiency in humans leads to which disease?',
        ['Rickets', 'Scurvy', 'Beriberi', 'Night blindness'], 'B',
        'Vitamin C (ascorbic acid) deficiency causes scurvy, characterised by bleeding gums and poor wound healing.'],
    [SCIENCE, 'Physics', 'Medium', 'Which mirror is used as a rear-view mirror in vehicles?',
        ['Concave mirror', 'Convex mirror', 'Plane mirror', 'Parabolic mirror'], 'B',
        'A convex mirror gives a wider, erect, diminished field of view, which is why it is used in vehicle rear-view mirrors.'],

    // ------------------------------------------------------------ CURRENT EVENTS
    [CURRENT, 'International Organizations', 'Medium', 'The headquarters of the United Nations is located in:',
        ['Geneva', 'New York', 'Paris', 'The Hague'], 'B',
        'The UN Headquarters is located in New York City, USA, on international territory.'],
    [CURRENT, 'Sports', 'Easy', 'The 2024 Summer Olympics were held in which city?',
        ['Tokyo', 'Paris', 'Los Angeles', 'London'], 'B',
        'The 2024 Summer Olympic Games were held in Paris, France.'],
    [CURRENT, 'Awards', 'Medium', 'The Nobel Prize is NOT awarded in which of the following fields?',
        ['Physics', 'Mathematics', 'Chemistry', 'Literature'], 'B',
        'The Nobel Prize is awarded in Physics, Chemistry, Medicine, Literature, Peace, and Economic Sciences — there is no Nobel Prize for Mathematics (the Fields Medal serves a similar role).'],
    [CURRENT, 'National Affairs', 'Medium', 'The "Digital India" programme was launched in which year?',
        ['2012', '2014', '2015', '2017'], 'C',
        'The Digital India programme was launched by the Government of India on 1 July 2015.'],

    // ------------------------------------------------------------ HISTORY OF INDIA
    [HISTORY, 'Ancient India', 'Medium', 'Which ruler is associated with the edicts inscribed on pillars and rocks across the Indian subcontinent?',
        ['Chandragupta Maurya', 'Ashoka', 'Samudragupta', 'Harshavardhana'], 'B',
        'Emperor Ashoka propagated Buddhist teachings and principles of Dhamma through edicts inscribed on rocks and pillars throughout his empire.'],
    [HISTORY, 'Medieval India', 'Medium', 'Who founded the Mughal Empire in India?',
        ['Akbar', 'Humayun', 'Babur', 'Sher Shah Suri'], 'C',
        'Babur defeated Ibrahim Lodi at the First Battle of Panipat in 1526 and founded the Mughal Empire in India.'],
    [HISTORY, 'Bengal History', 'Medium', 'The Battle of Plassey (1757), which established British control over Bengal, was fought between the British East India Company and:',
        ['Mir Jafar', 'Siraj-ud-Daulah', 'Mir Qasim', 'Shuja-ud-Daulah'], 'B',
        'The Battle of Plassey was fought between Robert Clive\'s forces and Siraj-ud-Daulah, the Nawab of Bengal, who was defeated partly due to the betrayal of his commander Mir Jafar.'],
    [HISTORY, 'Ancient India', 'Hard', 'The famous Nalanda University, an ancient centre of learning, was located in present-day:',
        ['West Bengal', 'Bihar', 'Uttar Pradesh', 'Odisha'], 'B',
        'Nalanda, one of the world\'s oldest residential universities, was located near Patna in present-day Bihar.'],

    // ------------------------------------------------------------ INDIAN NATIONAL MOVEMENT
    [MOVEMENT, 'Freedom Movement', 'Easy', 'The Indian National Congress was founded in the year:',
        ['1875', '1885', '1905', '1915'], 'B',
        'The Indian National Congress was founded in 1885, with its first session held in Bombay under the presidency of W. C. Bonnerjee.'],
    [MOVEMENT, 'Freedom Movement', 'Medium', 'The Partition of Bengal, which sparked the Swadeshi Movement, was carried out by Lord Curzon in the year:',
        ['1900', '1905', '1909', '1911'], 'B',
        'Lord Curzon partitioned Bengal in 1905, a move that triggered widespread protests and the Swadeshi Movement, and was later annulled in 1911.'],
    [MOVEMENT, 'Freedom Movement', 'Medium', 'The Non-Cooperation Movement (1920-22) was called off by Mahatma Gandhi following which incident?',
        ['Jallianwala Bagh massacre', 'Chauri Chaura incident', 'Salt Satyagraha', 'Quit India agitation'], 'B',
        'Gandhi withdrew the Non-Cooperation Movement in 1922 after violence erupted at Chauri Chaura, where protesters killed several policemen.'],
    [MOVEMENT, 'Freedom Movement', 'Medium', 'Netaji Subhas Chandra Bose founded the Indian National Army (Azad Hind Fauj) with support from:',
        ['The Soviet Union', 'Japan', 'Germany only', 'The United States'], 'B',
        'Subhas Chandra Bose reorganised and led the Indian National Army with support from Japan during the Second World War.'],

    // ------------------------------------------------------------ GEOGRAPHY
    [GEOGRAPHY, 'Physical Geography', 'Easy', 'Which is the highest peak in India?',
        ['Nanda Devi', 'Kangchenjunga', 'K2', 'Everest'], 'B',
        'Kangchenjunga, on the India-Nepal border in Sikkim, is the highest peak located within India (K2 and Everest lie outside Indian territory).'],
    [GEOGRAPHY, 'West Bengal Geography', 'Medium', 'The Darjeeling Himalayan Railway, a UNESCO World Heritage Site, is located in which part of West Bengal?',
        ['Southern plains', 'Sundarbans delta', 'Northern hill region', 'Rarh plateau'], 'C',
        'The Darjeeling Himalayan Railway runs through the hilly northern region of West Bengal, connecting New Jalpaiguri to Darjeeling.'],
    [GEOGRAPHY, 'West Bengal Geography', 'Medium', 'The Sundarbans, the world\'s largest mangrove forest and a tiger reserve, lies at the delta of which river system?',
        ['Ganga-Brahmaputra', 'Godavari-Krishna', 'Mahanadi', 'Narmada'], 'A',
        'The Sundarbans form at the delta where the Ganga and Brahmaputra river systems meet the Bay of Bengal, shared between India (West Bengal) and Bangladesh.'],
    [GEOGRAPHY, 'Physical Geography', 'Easy', 'Which of the following is a cold desert in India?',
        ['Thar Desert', 'Ladakh', 'Rann of Kutch', 'Rajasthan Desert'], 'B',
        'Ladakh, lying in the trans-Himalayan region, is classified as a cold desert due to its high altitude and extremely low precipitation.'],

    // ------------------------------------------------------------ INDIAN POLITY & ECONOMY
    [POLITY, 'Constitution', 'Easy', 'The Constitution of India was adopted on 26 November 1949 and came into effect on:',
        ['15 August 1947', '26 January 1950', '26 November 1950', '2 October 1950'], 'B',
        'The Constitution was adopted on 26 November 1949 but came into force on 26 January 1950, which is why that date is celebrated as Republic Day.'],
    [POLITY, 'Constitution', 'Medium', 'Who is known as the "Father of the Indian Constitution"?',
        ['Jawaharlal Nehru', 'Rajendra Prasad', 'B. R. Ambedkar', 'Sardar Vallabhbhai Patel'], 'C',
        'Dr. B. R. Ambedkar chaired the Drafting Committee of the Constituent Assembly and is regarded as the chief architect, or "Father", of the Indian Constitution.'],
    [POLITY, 'Government Structure', 'Medium', 'Panchayati Raj Institutions in India were given constitutional status by which amendment?',
        ['42nd Amendment', '44th Amendment', '73rd Amendment', '86th Amendment'], 'C',
        'The 73rd Constitutional Amendment Act, 1992, gave constitutional status to Panchayati Raj Institutions (rural local self-government).'],
    [POLITY, 'Economy', 'Medium', 'The Reserve Bank of India was established in the year:',
        ['1935', '1947', '1950', '1969'], 'A',
        'The RBI was established on 1 April 1935 under the Reserve Bank of India Act, 1934.'],
    [POLITY, 'Economy', 'Medium', 'Which Five-Year Plan of India is also known as the "Gadgil Yojana"?',
        ['Third', 'Fourth', 'Fifth', 'Sixth'], 'B',
        'The Fourth Five-Year Plan (1969-74) is known as the Gadgil Yojana, named after economist D. R. Gadgil, who devised the Gadgil formula for resource allocation to states.'],

    // ------------------------------------------------------------ GENERAL MENTAL ABILITY & REASONING
    [REASONING, 'Series', 'Easy', 'Find the next term: 5, 11, 23, 47, ?',
        ['93', '94', '95', '96'], 'A',
        'Each term is obtained by doubling the previous term and adding 1: 5×2+1=11, 11×2+1=23, 23×2+1=47, 47×2+1=95.'],
    [REASONING, 'Coding-Decoding', 'Medium', 'If WATER is coded as YCVGR, how is EARTH coded in the same pattern?',
        ['GCTVJ', 'GCTUJ', 'FCSVK', 'GCTVK'], 'A',
        'Each letter shifts forward by 2: W→Y, A→C, T→V, E→G, R→T. Applying the same shift to EARTH: E→G, A→C, R→T, T→V, H→J, giving GCTVJ.'],
    [REASONING, 'Analogy', 'Easy', 'Book is to Library as Painting is to:',
        ['Artist', 'Gallery', 'Canvas', 'Museum'], 'B',
        'A book is housed and displayed in a library; similarly, a painting is typically housed and displayed in a gallery.'],
    [REASONING, 'Blood Relations', 'Medium', "A is the brother of B. B is the sister of C. C is the father of D. How is A related to D?",
        ['Uncle', 'Father', 'Grandfather', 'Brother'], 'A',
        'A is the sibling of B and C (B and C are siblings), and C is D\'s father, so A (being C\'s sibling) is D\'s uncle.'],
    [REASONING, 'Non-Verbal / Classification', 'Easy', 'Find the odd one out: Triangle, Square, Circle, Length',
        ['Triangle', 'Square', 'Circle', 'Length'], 'D',
        'Triangle, Square and Circle are two-dimensional shapes; Length is a measure, not a shape.'],

    // ------------------------------------------------------------ ARITHMETIC
    [ARITH, 'Percentage', 'Easy', 'If 30% of a number is 90, what is the number?',
        ['250', '270', '300', '330'], 'C',
        'Let the number be x. 30% of x = 90 → x = 90 × 100/30 = 300.'],
    [ARITH, 'Simple Interest', 'Medium', 'A sum of ₹8,000 amounts to ₹9,440 in 3 years at simple interest. Find the rate of interest per annum.',
        ['5%', '6%', '6.5%', '7%'], 'B',
        'SI = 9440 − 8000 = ₹1,440. Rate = (SI × 100)/(P × T) = (1440 × 100)/(8000 × 3) = 6%.'],
    [ARITH, 'Time & Work', 'Medium', 'A and B together can complete a task in 10 days. A alone can complete it in 15 days. In how many days can B alone complete the task?',
        ['20 days', '25 days', '30 days', '35 days'], 'C',
        'Combined rate = 1/10. A\'s rate = 1/15. B\'s rate = 1/10 − 1/15 = (3−2)/30 = 1/30, so B alone takes 30 days.'],
    [ARITH, 'Averages', 'Medium', 'The average weight of 8 students is 45 kg. If a new student weighing 63 kg joins, what is the new average weight?',
        ['46 kg', '47 kg', '48 kg', '49 kg'], 'B',
        'Total weight of 8 students = 8 × 45 = 360 kg. New total = 360 + 63 = 423 kg for 9 students. New average = 423/9 = 47 kg.'],
    [ARITH, 'Ratio & Proportion', 'Easy', 'Divide ₹1,200 between A and B in the ratio 5:7. What is B\'s share?',
        ['₹500', '₹600', '₹700', '₹800'], 'C',
        'Total parts = 5+7 = 12. One part = 1200/12 = 100. B\'s share = 7 × 100 = ₹700.'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-wbcs.php. Re-importing replaces the WBCS Prelims paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'WBCS_PRE';";
$out[] = "DELETE FROM exams WHERE slug = 'wbcs-prelims';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('WBCS_PRE', 'WBCS Prelims', 'West Bengal Civil Service', 90, 1, 0.33, 44, 9);";

$sections = [
    [ENGLISH, 'Synonyms & Antonyms, One Word Substitution, Idioms & Phrases, Error Spotting, Fill in the Blanks, Comprehension', 10],
    [SCIENCE, 'Physics, Chemistry and Biology basics relevant to daily life and general awareness', 12],
    [CURRENT, 'National and international current affairs, sports, awards, important appointments and schemes', 10],
    [HISTORY, 'Ancient, Medieval and Modern India, with emphasis on Bengal\'s role in Indian history', 10],
    [GEOGRAPHY, 'Physical, economic and social geography of India, with emphasis on the geography of West Bengal', 10],
    [POLITY, 'Indian Constitution, government structure, Panchayati Raj, and basics of the Indian economy', 12],
    [MOVEMENT, 'Indian freedom struggle: 1857 to independence, with key leaders, movements and events', 10],
    [REASONING, 'Series, analogy, coding-decoding, blood relations, classification and non-verbal reasoning', 12],
    [ARITH, 'Percentage, Simple & Compound Interest, Time & Work, Averages, Ratio & Proportion, Profit & Loss', 14],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBCS_PRE', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('wbcs-prelims', 'WBCS Prelims Mock Test', 'demo', 'ready', 60, 1, 0.33, 0);";
$out[] = 'SET @wbcs_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@wbcs_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/wbcs_prelims.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + WBCS_PRE preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
