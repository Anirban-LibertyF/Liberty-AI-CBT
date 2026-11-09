<?php
// Builds database/wbpsc_clerkship.sql: a WB PSC Clerkship (Prelims) style practice paper for the
// existing WBPSC_CLERK preset (created by presets_extra.sql). Sections follow that preset.
//
//   php tools/build-wbpsc-clerkship.php   -> writes database/wbpsc_clerkship.sql
declare(strict_types=1);

const GK = 'General Knowledge & Current Affairs';
const ARITH = 'Arithmetic & Numerical Ability';
const REASON = 'Reasoning';
const LANG = 'English & Bengali Language';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ GK
    [GK, 'West Bengal Geography', 'Easy', 'Which river is known as the "Sorrow of Bengal" because of its devastating floods?',
        ['Hooghly', 'Damodar', 'Teesta', 'Mayurakshi'], 'B',
        'The Damodar was historically called the "Sorrow of Bengal" for its frequent, destructive floods before the Damodar Valley Corporation built dams on it.'],
    [GK, 'Culture & Personalities', 'Easy', 'Who composed the Indian national anthem "Jana Gana Mana"?',
        ['Bankim Chandra Chattopadhyay', 'Rabindranath Tagore', 'Sarojini Naidu', 'Muhammad Iqbal'], 'B',
        'Jana Gana Mana was composed by Rabindranath Tagore; its first stanza was adopted as the national anthem in 1950.'],
    [GK, 'Modern History', 'Medium', 'The Battle of Plassey, which laid the foundation of British rule in Bengal, was fought in:',
        ['1707', '1757', '1764', '1857'], 'B',
        'The Battle of Plassey was fought on 23 June 1757 between Robert Clive\'s East India Company forces and Nawab Siraj-ud-Daulah.'],
    [GK, 'Culture & Personalities', 'Easy', 'Visva-Bharati University at Santiniketan was founded by:',
        ['Ishwar Chandra Vidyasagar', 'Swami Vivekananda', 'Rabindranath Tagore', 'Raja Ram Mohan Roy'], 'C',
        'Rabindranath Tagore founded Visva-Bharati at Santiniketan (established 1921), based on his idea of an open, universal education.'],
    [GK, 'West Bengal Geography', 'Medium', 'Which is the largest district of West Bengal by area?',
        ['Paschim Medinipur', 'South 24 Parganas', 'Jalpaiguri', 'Purulia'], 'B',
        'South 24 Parganas, with the Sundarbans delta area included, is the largest district of West Bengal by area.'],
    [GK, 'West Bengal Geography', 'Medium', 'The Sundarbans mangrove forest lies in the delta formed by which rivers?',
        ['Godavari and Krishna', 'Ganga, Brahmaputra and Meghna', 'Narmada and Tapti', 'Mahanadi and Brahmani'], 'B',
        'The Sundarbans is part of the Ganga–Brahmaputra–Meghna delta, the largest delta in the world, shared by India and Bangladesh.'],
    [GK, 'Indian Polity', 'Easy', 'Untouchability is abolished by which Article of the Indian Constitution?',
        ['Article 14', 'Article 15', 'Article 17', 'Article 21'], 'C',
        'Article 17 abolishes untouchability and forbids its practice in any form.'],
    [GK, 'Indian Polity', 'Easy', 'Who is the constitutional head of a State in India?',
        ['Chief Minister', 'Governor', 'Chief Justice of the High Court', 'Speaker of the Legislative Assembly'], 'B',
        'The Governor is the constitutional head of a State, while real executive power rests with the Chief Minister and Council of Ministers.'],
    [GK, 'Indian Polity', 'Medium', 'Who was the Chairman of the Drafting Committee of the Indian Constitution?',
        ['Jawaharlal Nehru', 'Dr. Rajendra Prasad', 'Dr. B. R. Ambedkar', 'Sardar Vallabhbhai Patel'], 'C',
        'Dr. B. R. Ambedkar chaired the Drafting Committee of the Constituent Assembly and is known as the Chief Architect of the Constitution.'],
    [GK, 'Modern History', 'Medium', 'Kolkata (Calcutta) remained the capital of British India until the year:',
        ['1905', '1911', '1920', '1935'], 'B',
        'The capital of British India was shifted from Calcutta to Delhi in 1911, announced at the Delhi Durbar of that year.'],
    [GK, 'General Science', 'Easy', 'Which gas is the most abundant in the Earth\'s atmosphere?',
        ['Oxygen', 'Carbon dioxide', 'Nitrogen', 'Argon'], 'C',
        'Nitrogen makes up about 78% of the atmosphere, oxygen about 21%.'],
    [GK, 'Static GK', 'Easy', 'Who is popularly known as the "Iron Man of India"?',
        ['Subhas Chandra Bose', 'Sardar Vallabhbhai Patel', 'Lal Bahadur Shastri', 'Bhagat Singh'], 'B',
        'Sardar Vallabhbhai Patel earned the title for his role in integrating the princely states into the Indian Union.'],

    // ------------------------------------------------------------ ARITHMETIC
    [ARITH, 'Percentage', 'Easy', 'What is 25% of 480?',
        ['100', '110', '120', '125'], 'C', '25% of 480 = 480 ÷ 4 = 120.'],
    [ARITH, 'Simple Interest', 'Easy', 'What is the simple interest on ₹5,000 at 8% per annum for 3 years?',
        ['₹1,000', '₹1,200', '₹1,400', '₹1,500'], 'B', 'SI = (P × R × T) / 100 = (5000 × 8 × 3) / 100 = ₹1,200.'],
    [ARITH, 'Average', 'Easy', 'The average of 12, 18, 24, 30 and 36 is:',
        ['22', '24', '26', '30'], 'B', 'Sum = 12 + 18 + 24 + 30 + 36 = 120; average = 120 ÷ 5 = 24.'],
    [ARITH, 'Time & Work', 'Medium', 'A can finish a piece of work in 10 days and B can finish it in 15 days. Working together, in how many days will they finish it?',
        ['5 days', '6 days', '8 days', '12.5 days'], 'B', 'Combined rate = 1/10 + 1/15 = 5/30 = 1/6 of the work per day, so they take 6 days.'],
    [ARITH, 'Time, Speed & Distance', 'Medium', 'A train 150 m long runs at 54 km/h. How long will it take to cross a pole?',
        ['8 seconds', '10 seconds', '12 seconds', '15 seconds'], 'B', '54 km/h = 54 × 5/18 = 15 m/s. Time = 150 ÷ 15 = 10 seconds.'],
    [ARITH, 'Profit & Loss', 'Easy', 'An article bought for ₹800 is sold for ₹960. The profit percentage is:',
        ['15%', '18%', '20%', '25%'], 'C', 'Profit = 960 − 800 = ₹160; profit % = (160 / 800) × 100 = 20%.'],
    [ARITH, 'Ratio & Proportion', 'Medium', 'If ₹720 is divided between two persons in the ratio 4 : 5, the larger share is:',
        ['₹320', '₹360', '₹400', '₹450'], 'C', 'Total parts = 9; one part = 720 ÷ 9 = 80; larger share = 5 × 80 = ₹400.'],
    [ARITH, 'Square Roots', 'Easy', 'The square root of 1764 is:',
        ['38', '42', '46', '48'], 'B', '42 × 42 = 1764, so √1764 = 42.'],
    [ARITH, 'LCM & HCF', 'Medium', 'The LCM of 12, 18 and 24 is:',
        ['36', '48', '72', '144'], 'C', '12 = 2²·3, 18 = 2·3², 24 = 2³·3. LCM = 2³ × 3² = 72.'],
    [ARITH, 'Percentage', 'Hard', 'The price of an item is first increased by 20% and then decreased by 20%. The net change in price is:',
        ['No change', '4% decrease', '4% increase', '2% decrease'], 'B', 'Net factor = 1.2 × 0.8 = 0.96, so the price falls by 4%.'],

    // ------------------------------------------------------------ REASONING
    [REASON, 'Number Series', 'Easy', 'Find the next number in the series: 2, 6, 12, 20, 30, ?',
        ['38', '40', '42', '44'], 'C', 'The differences are 4, 6, 8, 10, so the next difference is 12: 30 + 12 = 42. (The terms are n × (n + 1).)'],
    [REASON, 'Coding-Decoding', 'Easy', 'If CAT is coded as DBU, then DOG will be coded as:',
        ['EPH', 'EPG', 'FPH', 'DPH'], 'A', 'Each letter is shifted one place forward: D→E, O→P, G→H, giving EPH.'],
    [REASON, 'Odd One Out', 'Easy', 'Choose the odd one out: Apple, Mango, Potato, Banana',
        ['Apple', 'Mango', 'Potato', 'Banana'], 'C', 'Apple, mango and banana are fruits, while potato is a vegetable (a tuber).'],
    [REASON, 'Blood Relations', 'Medium', 'Pointing to a photograph, Ravi says, "She is the daughter of my father\'s only son." How is the girl related to Ravi?',
        ['Sister', 'Niece', 'Daughter', 'Cousin'], 'C', 'The only son of Ravi\'s father is Ravi himself, so the girl is Ravi\'s daughter.'],
    [REASON, 'Direction Sense', 'Medium', 'Rahul walks 5 km north, turns right and walks 3 km, then turns right again and walks 5 km. How far is he from his starting point?',
        ['3 km', '5 km', '8 km', '13 km'], 'A', 'After north 5, east 3 and south 5, he is back in line with the start but 3 km to the east: distance = 3 km.'],
    [REASON, 'Analogy', 'Easy', 'Doctor : Hospital :: Teacher : ?',
        ['Student', 'School', 'Book', 'Class'], 'B', 'A doctor works in a hospital, as a teacher works in a school.'],
    [REASON, 'Alphabet Series', 'Easy', 'Find the next letter in the series: A, C, E, G, ?',
        ['H', 'I', 'J', 'K'], 'B', 'The series moves two letters at a time: A, C, E, G, I.'],
    [REASON, 'Clocks', 'Medium', 'What is the angle between the hands of a clock at 3:00?',
        ['60°', '75°', '90°', '120°'], 'C', 'At 3:00 the hour hand is on 3 and the minute hand on 12, which are 3 × 30° = 90° apart.'],
    [REASON, 'Order & Ranking', 'Medium', 'In a row of 40 students, Ravi is 15th from the left. What is his position from the right?',
        ['24th', '25th', '26th', '27th'], 'C', 'Position from right = 40 − 15 + 1 = 26th.'],
    [REASON, 'Syllogism', 'Medium', 'Statements: All cats are animals. All animals are living beings. Which conclusion definitely follows?',
        ['All cats are living beings', 'Some living beings are not animals', 'All living beings are cats', 'No cat is a living being'], 'A',
        'Cats ⊂ animals ⊂ living beings, so all cats are living beings. The other conclusions do not necessarily follow.'],

    // ------------------------------------------------------------ LANGUAGE
    [LANG, 'Vocabulary', 'Easy', 'Choose the word closest in meaning to "ABUNDANT":',
        ['Scarce', 'Plentiful', 'Costly', 'Tiny'], 'B', '"Abundant" means existing in large quantities, i.e. plentiful.'],
    [LANG, 'Vocabulary', 'Easy', 'Choose the antonym of "ANCIENT":',
        ['Old', 'Historic', 'Modern', 'Classic'], 'C', '"Ancient" means very old; its opposite is "modern".'],
    [LANG, 'Spelling', 'Medium', 'Choose the correctly spelt word:',
        ['Acommodation', 'Accomodation', 'Accommodation', 'Acomodation'], 'C', 'The correct spelling is "accommodation", with double c and double m.'],
    [LANG, 'Grammar', 'Easy', 'Fill in the blank: She has been living in Kolkata ______ 2010.',
        ['for', 'since', 'from', 'by'], 'B', '"Since" is used with a fixed point in time (2010) in the present perfect continuous tense.'],
    [LANG, 'One Word Substitution', 'Medium', 'One word for "a person who loves books":',
        ['Bibliophile', 'Philanthropist', 'Misanthrope', 'Bibliographer'], 'A', 'A bibliophile is a lover of books.'],
    [LANG, 'Idioms & Phrases', 'Medium', 'The idiom "a blessing in disguise" means:',
        ['A curse that looks like luck', 'Something good that seems bad at first', 'A secret gift', 'An unexpected guest'], 'B',
        'It refers to something that appears unfortunate at first but later turns out to be beneficial.'],
    [LANG, 'Active & Passive Voice', 'Medium', 'Change to passive voice: "The teacher praised the student."',
        ['The student praised the teacher.', 'The student was praised by the teacher.', 'The student is praised by the teacher.', 'The student has been praised by the teacher.'], 'B',
        'The simple past active "praised" becomes "was praised" in the passive, with the agent introduced by "by".'],
    [LANG, 'Bengali Vocabulary', 'Easy', '‘পুষ্প’ শব্দের সমার্থক শব্দ কোনটি?',
        ['ফুল', 'পাখি', 'নদী', 'মেঘ'], 'A', '‘পুষ্প’ শব্দের অর্থ ফুল; তাই সমার্থক শব্দ ‘ফুল’।'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-wbpsc-clerkship.php. Re-importing replaces the WB PSC Clerkship paper.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM exams WHERE slug = 'wbpsc-clerkship';";
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('wbpsc-clerkship', 'WB PSC Clerkship Mock Test', 'demo', 'ready', 60, 1, 0.25, 0);";
$out[] = 'SET @wbc_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@wbc_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/wbpsc_clerkship.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions\n";
echo json_encode(array_count_values(array_column($Q, 0)), JSON_UNESCAPED_UNICODE), "\n";
