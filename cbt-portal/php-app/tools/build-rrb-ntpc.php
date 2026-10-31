<?php
// Builds database/rrb_ntpc.sql: an RRB NTPC (Railway Non-Technical Popular Categories) CBT-1 style
// practice paper + the RRB NTPC preset. Mirrors the real CBT-1 structure: Mathematics,
// General Intelligence & Reasoning, General Awareness (static GK + current affairs + general science).
//
//   C:\xampp\php\php.exe tools\build-rrb-ntpc.php   -> writes database/rrb_ntpc.sql
declare(strict_types=1);

const MATHS = 'Mathematics';
const REASON = 'General Intelligence & Reasoning';
const GA = 'General Awareness';

// [section, topic, difficulty, question, [4 options], correct letter, explanation]
$Q = [
    // ------------------------------------------------------------ MATHEMATICS
    [MATHS, 'Percentage', 'Easy', 'A student scores 462 marks out of 600 in an exam. What percentage of marks did the student get?',
        ['72%', '75%', '77%', '80%'], 'C',
        '462/600 × 100 = 77%.'],
    [MATHS, 'Profit & Loss', 'Medium', 'A trader buys an article for ₹840 and sells it at a loss of 15%. What is the selling price?',
        ['₹700', '₹714', '₹724', '₹966'], 'B',
        'Loss of 15% means SP = 85% of CP = 0.85 × 840 = ₹714.'],
    [MATHS, 'Simple Interest', 'Easy', 'Find the simple interest on ₹12,000 at 8% per annum for 3 years.',
        ['₹2,880', '₹2,480', '₹3,200', '₹2,640'], 'A',
        'SI = (P × R × T)/100 = (12000 × 8 × 3)/100 = ₹2,880.'],
    [MATHS, 'Time, Speed & Distance', 'Medium', 'A train 150 m long crosses a pole in 9 seconds. What is its speed in km/h?',
        ['54 km/h', '60 km/h', '64 km/h', '48 km/h'], 'B',
        'Speed = 150/9 m/s = 16.67 m/s = 16.67 × 18/5 = 60 km/h.'],
    [MATHS, 'Compound Interest', 'Medium', 'Find the compound interest on ₹10,000 at 10% per annum for 2 years, compounded annually.',
        ['₹2,000', '₹2,100', '₹2,200', '₹1,000'], 'B',
        'Amount = 10000 × (1.1)² = 10000 × 1.21 = ₹12,100. CI = 12,100 − 10,000 = ₹2,100.'],
    [MATHS, 'Time & Work', 'Medium', 'A can do a piece of work in 12 days and B can do it in 18 days. Working together, in how many days will they finish the work?',
        ['6 days', '7.2 days', '9 days', '7.5 days'], 'B',
        'Combined rate = 1/12 + 1/18 = 3/36 + 2/36 = 5/36 per day. Time = 36/5 = 7.2 days.'],
    [MATHS, 'Ratio & Proportion', 'Easy', 'Two numbers are in the ratio 3:5. If their sum is 96, find the larger number.',
        ['36', '48', '60', '72'], 'C',
        'Parts = 3+5 = 8. One part = 96/8 = 12. Larger number = 5 × 12 = 60.'],
    [MATHS, 'Average', 'Easy', 'The average of 5 consecutive odd numbers is 61. Find the largest number.',
        ['61', '63', '65', '69'], 'C',
        'Consecutive odd numbers around the average 61: 57, 59, 61, 63, 65. Largest = 65.'],
    [MATHS, 'Algebra', 'Medium', 'If x + 1/x = 5, what is the value of x² + 1/x²?',
        ['21', '23', '25', '27'], 'B',
        'x² + 1/x² = (x + 1/x)² − 2 = 25 − 2 = 23.'],
    [MATHS, 'Mensuration', 'Medium', 'Find the area of a rectangle whose length is 24 cm and whose perimeter is 68 cm.',
        ['240 cm²', '288 cm²', '408 cm²', '456 cm²'], 'A',
        'Perimeter = 2(l+b) = 68 → l+b = 34 → b = 34−24 = 10. Area = 24 × 10 = 240 cm².'],
    [MATHS, 'Number System', 'Medium', 'What is the smallest number which, when divided by 12, 15 and 20, leaves a remainder of 5 in each case?',
        ['60', '65', '55', '125'], 'B',
        'LCM(12,15,20) = 60. Required number = 60 + 5 = 65.'],
    [MATHS, 'Trigonometry', 'Medium', 'The angle of elevation of the top of a tower from a point 60 m away from its base is 45°. What is the height of the tower?',
        ['30 m', '60 m', '60√3 m', '30√3 m'], 'B',
        'tan45° = height/60 = 1, so height = 60 m.'],

    // ------------------------------------------------------------ GENERAL INTELLIGENCE & REASONING
    [REASON, 'Number Series', 'Easy', 'Find the next number in the series: 2, 6, 12, 20, 30, ?',
        ['36', '40', '42', '44'], 'C',
        'Differences are 4, 6, 8, 10, 12 (consecutive even numbers). 30 + 12 = 42.'],
    [REASON, 'Analogy', 'Easy', 'TEACHER is related to SCHOOL in the same way as DOCTOR is related to:',
        ['Patient', 'Hospital', 'Medicine', 'Clinic'], 'B',
        'A teacher works at a school; a doctor works at a hospital — place-of-work analogy.'],
    [REASON, 'Coding-Decoding', 'Medium', 'If in a code, BOOK is written as DQQM, how is PAGE written in the same code?',
        ['RCIG', 'RCIH', 'QBHF', 'RBIG'], 'A',
        'Each letter is shifted forward by 2: B→D, O→Q, O→Q, K→M. Applying the same shift: P→R, A→C, G→I, E→G, giving RCIG.'],
    [REASON, 'Blood Relations', 'Medium', "Pointing to a photograph, Rahul said, 'She is the daughter of my grandfather's only son.' How is the girl in the photograph related to Rahul?",
        ['Sister', 'Cousin', 'Daughter', 'Niece'], 'A',
        "Rahul's grandfather's only son is Rahul's father, so the girl is his father's daughter, i.e. Rahul's sister."],
    [REASON, 'Direction Sense', 'Medium', 'Rohan walks 8 km towards the North, then turns right and walks 6 km. How far is he from his starting point?',
        ['10 km', '12 km', '14 km', '2 km'], 'A',
        'This forms a right triangle with legs 8 km and 6 km. Distance = √(8²+6²) = √100 = 10 km.'],
    [REASON, 'Syllogism', 'Medium', "Statements: All pens are pencils. All pencils are erasers. Conclusions: I. All pens are erasers. II. Some erasers are pens. Which conclusion(s) follow?",
        ['Only I follows', 'Only II follows', 'Both I and II follow', 'Neither follows'], 'C',
        'From "All pens are pencils" and "All pencils are erasers", by transitivity all pens are erasers (I), and since all pens are erasers, some erasers are definitely pens (II).'],
    [REASON, 'Mirror Images', 'Easy', 'Which letter looks the same when reflected in a vertical mirror: F, A, P, R?',
        ['F', 'A', 'P', 'R'], 'B',
        'A has a vertical line of symmetry, so it looks identical in a vertical mirror; F, P and R do not.'],
    [REASON, 'Odd One Out', 'Easy', 'Find the one that does not belong with the others: Apple, Mango, Potato, Banana',
        ['Apple', 'Mango', 'Potato', 'Banana'], 'C',
        'Apple, Mango and Banana are fruits; Potato is a vegetable.'],
    [REASON, 'Ranking & Order', 'Medium', 'In a row of students, Meena is 7th from the left and 12th from the right. How many students are there in the row?',
        ['18', '19', '20', '17'], 'A',
        'Total = (7 − 1) + (12 − 1) + 1 = 6 + 11 + 1 = 18.'],
    [REASON, 'Age Problems', 'Medium', "The sum of the ages of a father and his son is 56 years. Four years ago, the father's age was three times the son's age. Find the son's present age.",
        ['12 years', '14 years', '16 years', '18 years'], 'B',
        "Let the son's present age be x, so the father's age is 56−x. Four years ago: 56−x−4 = 3(x−4) → 52−x = 3x−12 → 64 = 4x → x = 16."],
    [REASON, 'Venn Diagram / Classification', 'Medium', 'Which term logically follows the group: Village, Town, City, Metropolis?',
        ['Megacity', 'Street', 'District', 'Nation'], 'A',
        'The series shows increasing scale of settlement size, so the next larger unit would be a Megacity.'],
    [REASON, 'Calendar', 'Hard', 'If 1st March 2023 was a Wednesday, what day of the week was 1st March 2024?',
        ['Thursday', 'Friday', 'Saturday', 'Wednesday'], 'B',
        '2024 is a leap year, and the period from 1 Mar 2023 to 1 Mar 2024 spans 366 days (because it includes 29 Feb 2024), which is 52 weeks + 2 days, so the day advances by 2: Wednesday + 2 = Friday.'],

    // ------------------------------------------------------------ GENERAL AWARENESS
    [GA, 'Indian Polity', 'Easy', 'Which article of the Indian Constitution abolishes untouchability?',
        ['Article 15', 'Article 17', 'Article 21', 'Article 24'], 'B',
        'Article 17 abolishes "untouchability" and forbids its practice in any form.'],
    [GA, 'Indian Polity', 'Medium', 'The power to issue a writ of Habeas Corpus is vested in:',
        ['Only the Supreme Court', 'Only High Courts', 'Both the Supreme Court and High Courts', 'The President only'], 'C',
        'Under Articles 32 and 226, both the Supreme Court and the High Courts can issue writs, including Habeas Corpus.'],
    [GA, 'Geography', 'Easy', 'Which is the longest river in India?',
        ['Godavari', 'Yamuna', 'Ganga', 'Brahmaputra'], 'C',
        'The Ganga is the longest river flowing entirely within India, with a length of about 2,525 km.'],
    [GA, 'Geography', 'Medium', 'The Tropic of Cancer passes through how many Indian states?',
        ['6', '7', '8', '9'], 'C',
        'The Tropic of Cancer passes through 8 Indian states: Gujarat, Rajasthan, Madhya Pradesh, Chhattisgarh, Jharkhand, West Bengal, Tripura and Mizoram.'],
    [GA, 'Modern History', 'Easy', 'Who was the first Governor-General of independent India?',
        ['Jawaharlal Nehru', 'C. Rajagopalachari', 'Lord Mountbatten', 'Rajendra Prasad'], 'C',
        'Lord Mountbatten served as the first Governor-General of independent India (1947-48); C. Rajagopalachari was the first and only Indian Governor-General after him.'],
    [GA, 'Modern History', 'Medium', 'The Jallianwala Bagh massacre took place in which year?',
        ['1917', '1919', '1921', '1923'], 'B',
        'The Jallianwala Bagh massacre occurred on 13 April 1919 in Amritsar, Punjab.'],
    [GA, 'Economics', 'Medium', 'Which organization releases the "Economic Survey of India" every year, just before the Union Budget?',
        ['RBI', 'NITI Aayog', 'Ministry of Finance', 'Planning Commission'], 'C',
        'The Economic Survey is prepared by the Department of Economic Affairs, Ministry of Finance, and tabled in Parliament a day before the Union Budget.'],
    [GA, 'General Science', 'Easy', 'Which blood group is known as the "universal donor"?',
        ['AB+', 'O−', 'A+', 'B−'], 'B',
        'O− blood lacks A, B and Rh antigens, so it can be given to recipients of any blood group in an emergency, making it the universal donor.'],
    [GA, 'General Science', 'Medium', 'The SI unit of electric resistance is:',
        ['Volt', 'Ampere', 'Ohm', 'Watt'], 'C',
        'Electric resistance is measured in Ohms (Ω), defined by Ohm\'s Law: R = V/I.'],
    [GA, 'General Science', 'Easy', 'Which gas is most abundant in the Earth\'s atmosphere?',
        ['Oxygen', 'Carbon dioxide', 'Nitrogen', 'Argon'], 'C',
        'Nitrogen makes up about 78% of the Earth\'s atmosphere by volume, far more than oxygen (~21%).'],
    [GA, 'Sports', 'Easy', 'The "Ranji Trophy" is associated with which sport in India?',
        ['Hockey', 'Cricket', 'Football', 'Badminton'], 'B',
        'The Ranji Trophy is the premier domestic first-class cricket championship played in India.'],
    [GA, 'Static GK', 'Easy', 'Which city is known as the "Pink City" of India?',
        ['Udaipur', 'Jodhpur', 'Jaipur', 'Bikaner'], 'C',
        'Jaipur is called the "Pink City" because many of its buildings were painted pink/terracotta, originally to welcome the Prince of Wales in 1876.'],
    [GA, 'Static GK', 'Medium', 'The headquarters of the Indian Railways is located in:',
        ['Mumbai', 'Kolkata', 'New Delhi', 'Chennai'], 'C',
        'The Railway Board, which governs Indian Railways, is headquartered in New Delhi.'],
    [GA, 'Railways Awareness', 'Medium', 'Which was the first railway line to be opened in India (1853)?',
        ['Howrah to Hooghly', 'Bombay (Bori Bunder) to Thane', 'Madras to Arcot', 'Delhi to Agra'], 'B',
        'India\'s first passenger railway train ran on 16 April 1853 between Bori Bunder (Bombay) and Thane, a distance of about 34 km.'],
    [GA, 'Railways Awareness', 'Medium', 'Indian Railways runs on which gauge predominantly, known as "Broad Gauge"?',
        ['1.676 m', '1.000 m', '0.762 m', '1.435 m'], 'A',
        'Indian Railways\' Broad Gauge has a track width of 1.676 m (5 ft 6 in), the most widely used gauge across the network.'],
    [GA, 'Current Affairs', 'Medium', 'The Vande Bharat Express is an indigenously developed:',
        ['Freight locomotive', 'Semi-high-speed self-propelled train set', 'Metro rail coach', 'Diesel shunting engine'], 'B',
        'Vande Bharat Express is a semi-high-speed, self-propelled (EMU-type) train set manufactured by Indian Railways (ICF Chennai) under the Make in India initiative.'],
];

$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";

$out = [];
$out[] = '-- Generated by tools/build-rrb-ntpc.php. Re-importing replaces the RRB NTPC paper and preset.';
$out[] = 'USE cbt_portal;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = "DELETE FROM presets WHERE id = 'RRB_NTPC';";
$out[] = "DELETE FROM exams WHERE slug = 'rrb-ntpc';";
$out[] = '';
$out[] = "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('RRB_NTPC', 'RRB NTPC', 'Railway Recruitment Board', 90, 1, 0.33, 36, 8);";

$sections = [
    [MATHS, 'Number System, Percentage, Ratio & Proportion, Profit & Loss, Simple & Compound Interest, Time Speed & Distance, Time & Work, Algebra, Mensuration, Trigonometry, Averages', 30],
    [REASON, 'Analogy, Number/Alphabet Series, Coding-Decoding, Blood Relations, Direction Sense, Syllogism, Mirror & Water Images, Classification, Ranking, Calendar & Clock, Puzzle', 30],
    [GA, 'Indian Polity, Modern History, Geography, Economics, General Science, Static GK, Sports, Current Affairs, Railways Awareness & Culture of India', 40],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('RRB_NTPC', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}

$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('rrb-ntpc', 'RRB NTPC CBT-1 Mock Test', 'demo', 'ready', 60, 1, 0.33, 0);";
$out[] = 'SET @rrb_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException('Question ' . ($i + 1) . ' is malformed');
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@rrb_exam, %d, %s, %s, %s, %s, 0, NULL, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}

file_put_contents(__DIR__ . '/../database/rrb_ntpc.sql', implode("\n", $out) . "\n");
echo count($Q) . " questions + RRB_NTPC preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
