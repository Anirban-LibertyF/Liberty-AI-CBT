<?php
// Builds database/neet_biology.sql: the NEET Biology practice paper (with diagrams) + the NEET Biology preset.
//
//   C:\xampp\php\php.exe tools\build-bio.php            -> writes database/neet_biology.sql
//   C:\xampp\php\php.exe tools\build-bio.php --svg DIR  -> also dumps each diagram as DIR\qN.svg
//
// Every figure in the paper is different (no picture is used twice). Diagram letters come from BioSvg::render(),
// which also gives the part-name -> letter map for that picture, so a question can't point at the wrong letter.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

const BOTANY = 'Botany';
const ZOOLOGY = 'Zoology';
const GENETICS = 'Genetics & Evolution';
const ECOLOGY = 'Ecology & Environment';

/** Picture + letters for one catalog diagram, seeded so each picture looks (and is labelled) differently. */
$fig = fn (string $id, int $seed) => BioSvg::render($id, $seed);

$ped = fn (array $p) => BioSvg::pedigree($p);
$pedigreeAR = $ped([
    ['id' => 1, 'gen' => 1, 'sex' => 'M', 'affected' => false, 'mate' => 2],
    ['id' => 2, 'gen' => 1, 'sex' => 'F', 'affected' => false, 'mate' => 1],
    ['id' => 3, 'gen' => 2, 'sex' => 'M', 'affected' => false, 'parents' => [1, 2]],
    ['id' => 4, 'gen' => 2, 'sex' => 'F', 'affected' => false, 'parents' => [1, 2]],
    ['id' => 5, 'gen' => 2, 'sex' => 'F', 'affected' => true, 'parents' => [1, 2], 'mate' => 6],
    ['id' => 6, 'gen' => 2, 'sex' => 'M', 'affected' => false, 'mate' => 5],
    ['id' => 7, 'gen' => 3, 'sex' => 'F', 'affected' => false, 'parents' => [6, 5]],
    ['id' => 8, 'gen' => 3, 'sex' => 'M', 'affected' => false, 'parents' => [6, 5]],
]);
$pedigreeXL = $ped([
    ['id' => 1, 'gen' => 1, 'sex' => 'M', 'affected' => false, 'mate' => 2],
    ['id' => 2, 'gen' => 1, 'sex' => 'F', 'affected' => false, 'mate' => 1],
    ['id' => 3, 'gen' => 2, 'sex' => 'M', 'affected' => true, 'parents' => [1, 2]],
    ['id' => 4, 'gen' => 2, 'sex' => 'F', 'affected' => false, 'parents' => [1, 2], 'mate' => 5],
    ['id' => 5, 'gen' => 2, 'sex' => 'M', 'affected' => false, 'mate' => 4],
    ['id' => 6, 'gen' => 3, 'sex' => 'M', 'affected' => true, 'parents' => [5, 4]],
    ['id' => 7, 'gen' => 3, 'sex' => 'F', 'affected' => false, 'parents' => [5, 4]],
    ['id' => 8, 'gen' => 3, 'sex' => 'M', 'affected' => false, 'parents' => [5, 4]],
]);
$energy = BioSvg::pyramid([
    ['name' => 'Producers', 'value' => '20,000 kJ'], ['name' => 'Primary consumers', 'value' => '?'],
    ['name' => 'Secondary consumers', 'value' => '?'], ['name' => 'Tertiary consumers', 'value' => '?'],
]);
$light = ChartSvg::fromSpec(['chartType' => 'line', 'title' => 'Rate of photosynthesis at different light intensities', 'xLabel' => 'Light intensity (kilolux)', 'yLabel' => 'CO2 fixed (units)',
    'labels' => ['0', '10', '20', '30', '40', '50', '60'], 'series' => [['name' => 'Rate', 'values' => [0, 6, 12, 18, 20, 20, 20]]]]);
$growth = ChartSvg::fromSpec(['chartType' => 'line', 'title' => 'Growth of a population in a limited habitat', 'xLabel' => 'Time (days)', 'yLabel' => 'Number of individuals',
    'labels' => ['0', '2', '4', '6', '8', '10', '12', '14'], 'series' => [['name' => 'N', 'values' => [4, 10, 24, 50, 72, 80, 80, 80]]]]);

$plant = $fig('plant_cell', 3); $chloro = $fig('chloroplast', 4); $flower = $fig('flower_ls', 7); $mito2 = $fig('mitosis', 5);
$animal = $fig('animal_cell', 2); $mitoc = $fig('mitochondrion', 8); $heart = $fig('human_heart', 0); $neuron = $fig('neuron', 4);
$syn = $fig('synapse', 11); $neph = $fig('nephron', 0); $eye = $fig('human_eye', 0); $sarc = $fig('sarcomere', 13); $phage = $fig('bacteriophage', 3);
$dna = $fig('dna_ladder', 0); $fork = $fig('replication_fork', 0); $lac = $fig('lac_operon', 9);

$calvin = BioSvg::flow('cycle', ['Carboxylation of RuBP', 'Reduction using ATP and NADPH', '?'], ['', '', ''], 'Calvin cycle (C3 plants)');
$bloodPath = BioSvg::flow('chain', ['Right atrium', '?', 'Pulmonary artery', 'Lungs', 'Left atrium', 'Left ventricle'], ['', '', '', '', ''], 'Path of blood through the heart and lungs');
$nitrogen = BioSvg::flow('cycle', ['Atmospheric N2', 'Ammonia (NH4+)', 'Nitrite (NO2-)', 'Nitrate (NO3-)'], ['?', 'Nitrification', 'Nitrification', 'Denitrification'], 'Nitrogen cycle');
$foodChain = BioSvg::flow('chain', ['Grass', 'Grasshopper', 'Frog', 'Snake', 'Eagle'], [], 'A grassland food chain');
$strip = BioSvg::dnaSequence('TACGGATTCACT', 'protein');
$mono = BioSvg::punnett('Tt', 'Tt');
$dihybrid = BioSvg::punnett('RrYy', 'RrYy');
$testcross = BioSvg::punnett('Tt', 'tt');

$L = fn (array $f, string $part) => $f['letters'][$part];

// [section, topic, difficulty, question, [4 options], correct letter, explanation, svg|null]
$Q = [
    // ------------------------------------------------------------ BOTANY
    [BOTANY, 'Plant Cell Organelles (Diagram)', 'Easy', "In the plant cell shown, the organelle labelled {$L($plant, 'Chloroplast')} is absent from animal cells and contains stacks of thylakoids (grana). What is its main function?",
        ['Cellular respiration', 'Photosynthesis', 'Lipid synthesis', 'Digestion of macromolecules'], 'B',
        'Chloroplasts contain chlorophyll in the thylakoid membranes (light reactions) and the stroma (Calvin cycle), so they are the site of photosynthesis. Respiration happens in mitochondria and digestion in lysosomes.', $plant['svg']],
    [BOTANY, 'Chloroplast Structure (Diagram)', 'Medium', "The enzymes of the Calvin cycle, including RuBisCO, are located in the region of the chloroplast labelled {$L($chloro, 'Stroma')}. What is this region called?",
        ['Thylakoid membrane', 'Stroma', 'Outer membrane', 'Granum'], 'B',
        'The stroma is the colourless fluid matrix around the grana. It holds the enzymes of the dark (biosynthetic) reactions, while the light reactions occur on the thylakoid membranes of the grana.', $chloro['svg']],
    [BOTANY, 'Flower Structure (Diagram)', 'Easy', "In this longitudinal section of a flower, the structure labelled {$L($flower, 'Ovule')} develops, after fertilisation, into a:",
        ['Fruit', 'Seed', 'Embryo sac', 'Pollen grain'], 'B',
        'After fertilisation the ovule becomes the seed (the zygote forms the embryo and the primary endosperm nucleus forms the endosperm), while the ovary wall forms the fruit wall.', $flower['svg']],
    [BOTANY, 'Stages of Mitosis (Diagram)', 'Medium', 'The four cells shown are stages of mitosis drawn in a random order. Which sequence gives the correct order of the stages?',
        [
            "{$L($mito2, 'Metaphase')} → {$L($mito2, 'Prophase')} → {$L($mito2, 'Telophase')} → {$L($mito2, 'Anaphase')}",
            "{$L($mito2, 'Prophase')} → {$L($mito2, 'Metaphase')} → {$L($mito2, 'Anaphase')} → {$L($mito2, 'Telophase')}",
            "{$L($mito2, 'Prophase')} → {$L($mito2, 'Anaphase')} → {$L($mito2, 'Metaphase')} → {$L($mito2, 'Telophase')}",
            "{$L($mito2, 'Telophase')} → {$L($mito2, 'Anaphase')} → {$L($mito2, 'Prophase')} → {$L($mito2, 'Metaphase')}",
        ], 'B',
        "The cell labelled {$L($mito2, 'Prophase')} has condensing chromosomes and a breaking-down nuclear envelope (prophase); {$L($mito2, 'Metaphase')} has chromosomes lined up at the equator (metaphase); {$L($mito2, 'Anaphase')} shows chromatids moving to opposite poles (anaphase); {$L($mito2, 'Telophase')} shows two daughter nuclei with the cell pinching (telophase).", $mito2['svg']],
    [BOTANY, 'Calvin Cycle (Flow Diagram)', 'Medium', 'The diagram shows the three phases of the Calvin cycle. What is the missing phase marked "?"',
        ['Photolysis of water', 'Regeneration of RuBP', 'Oxidative decarboxylation', 'Glycolysis'], 'B',
        'The Calvin cycle has three phases: carboxylation (CO_2 + RuBP, catalysed by RuBisCO), reduction (using ATP and NADPH to make triose phosphate) and regeneration of the CO_2 acceptor RuBP, which keeps the cycle going.', $calvin],
    [BOTANY, 'Light and Photosynthesis (Graph)', 'Medium', 'The graph shows how the rate of photosynthesis of a plant changes with light intensity. Beyond what light intensity does the rate stop increasing?',
        ['20 kilolux', '30 kilolux', '40 kilolux', '60 kilolux'], 'C',
        'The rate rises in a straight line up to 40 kilolux and is then constant at 20 units. Beyond this light saturation point another factor (CO_2 concentration or temperature) becomes limiting.', $light],
    [BOTANY, 'Microsporangia and Anther', 'Medium', 'A typical anther of an angiosperm flower is bilobed and each lobe has two chambers. How many microsporangia (pollen sacs) does it contain?',
        ['2', '4', '6', '8'], 'B',
        'A typical anther is bilobed and dithecous: each lobe has two theca, so there are four microsporangia, in which pollen grains develop from microspore mother cells.', null],
    [BOTANY, 'Cytokinesis in Plants', 'Medium', 'After telophase, cytokinesis in a plant cell takes place by:',
        ['Formation of a cleavage furrow', 'Formation of a cell plate', 'Budding', 'Fragmentation'], 'B',
        'Plant cells have a rigid wall, so cytokinesis occurs centrifugally by the cell plate: Golgi vesicles fuse at the equator to form a new middle lamella and wall. Cleavage furrow formation is typical of animal cells.', null],
    [BOTANY, 'Double Fertilisation', 'Medium', 'In the double fertilisation of an angiosperm, the triploid primary endosperm nucleus (PEN) is formed by the fusion of:',
        ['One male gamete and the egg cell', 'One male gamete and the two polar nuclei', 'Two male gametes and the egg cell', 'One male gamete and one synergid'], 'B',
        'One sperm fuses with the egg (syngamy, 2n zygote) and the second sperm fuses with the two polar nuclei of the central cell (triple fusion), giving the triploid (3n) PEN that develops into the endosperm.', null],
    [BOTANY, 'C4 Photosynthesis', 'Medium', 'Kranz anatomy, with large chloroplast-containing bundle sheath cells arranged in a wreath around the vascular bundles, is characteristic of:',
        ['C3 plants such as wheat', 'C4 plants such as maize', 'CAM plants such as pineapple', 'Hydrophytes such as Hydrilla'], 'B',
        'C4 plants (maize, sugarcane, sorghum) fix CO_2 first into oxaloacetate (a 4-carbon acid) in mesophyll cells, then release it in the bundle sheath cells where the Calvin cycle runs. The wreath-like bundle sheath is called Kranz anatomy.', null],

    // ------------------------------------------------------------ ZOOLOGY
    [ZOOLOGY, 'Golgi Apparatus (Diagram)', 'Easy', "The stack of flattened, membrane-bound cisternae labelled {$L($animal, 'Golgi apparatus')} in this animal cell is mainly concerned with:",
        ['Synthesis of ATP', 'Packaging, modification and secretion of proteins', 'Photosynthesis', 'Digestion of worn-out organelles'], 'B',
        'The Golgi apparatus receives proteins and lipids from the ER, modifies them (for example by glycosylation), sorts and packages them into vesicles for secretion or delivery. Digestion of worn-out organelles is the job of lysosomes.', $animal['svg']],
    [ZOOLOGY, 'Mitochondrion (Diagram)', 'Medium', "ATP synthase and the electron transport chain are located in the membrane labelled {$L($mitoc, 'Inner membrane')} of this mitochondrion. Which membrane is it?",
        ['Outer membrane', 'Inner membrane', 'Plasma membrane', 'Nuclear envelope'], 'B',
        'The inner mitochondrial membrane is folded into cristae, which greatly increase its surface area. It carries the electron transport chain and ATP synthase (F_0F_1 particles), where oxidative phosphorylation makes ATP.', $mitoc['svg']],
    [ZOOLOGY, 'Human Heart (Diagram)', 'Easy', "The large vessel labelled {$L($heart, 'Aorta')} arises from the left ventricle. It carries:",
        ['Deoxygenated blood to the lungs', 'Oxygenated blood to the body', 'Oxygenated blood from the lungs', 'Deoxygenated blood from the body'], 'B',
        'The aorta distributes oxygenated blood pumped by the left ventricle to the whole body (systemic circulation). Pulmonary veins bring oxygenated blood from the lungs, and venae cavae bring deoxygenated blood from the body.', $heart['svg']],
    [ZOOLOGY, 'Pulmonary Circulation (Flow Diagram)', 'Medium', 'The flow diagram shows the path of blood from the right atrium through the lungs. Which chamber is missing at "?"',
        ['Left ventricle', 'Right ventricle', 'Left atrium', 'Right atrium'], 'B',
        'Deoxygenated blood passes from the right atrium through the tricuspid valve into the right ventricle, which pumps it through the pulmonary artery to the lungs. Oxygenated blood returns by the pulmonary veins to the left atrium.', $bloodPath],
    [ZOOLOGY, 'Neuron and Nerve Impulse (Diagram)', 'Medium', "In a myelinated axon the impulse appears to jump between the gaps labelled {$L($neuron, 'Node of Ranvier')}. This type of conduction is called:",
        ['Continuous conduction', 'Saltatory conduction', 'Synaptic transmission', 'Reflex conduction'], 'B',
        'Myelin insulates the axon, so depolarisation happens only at the nodes of Ranvier. The impulse jumps from node to node (saltatory conduction), which is faster and uses less energy than continuous conduction in unmyelinated fibres.', $neuron['svg']],
    [ZOOLOGY, 'Synaptic Transmission (Diagram)', 'Hard', "Neurotransmitters are stored in the synaptic vesicles (labelled {$L($syn, 'Synaptic vesicle')}). Influx of which ion into the axon terminal triggers the release of the vesicles?",
        ['Na^+', 'K^+', 'Ca^{2+}', 'Cl^-'], 'C',
        'When an action potential reaches the axon terminal, voltage-gated Ca^{2+} channels open. The Ca^{2+} influx makes the synaptic vesicles fuse with the presynaptic membrane and release the neurotransmitter into the synaptic cleft (' . $L($syn, 'Synaptic cleft') . ') by exocytosis.', $syn['svg']],
    [ZOOLOGY, 'Nephron: Reabsorption (Diagram)', 'Medium', "Selective reabsorption of nearly all the glucose and amino acids from the glomerular filtrate takes place in the part labelled {$L($neph, 'Proximal convoluted tubule (PCT)')}. Which part is it?",
        ['Bowman\'s capsule', 'Proximal convoluted tubule', 'Distal convoluted tubule', 'Collecting duct'], 'B',
        'The PCT, lined with brush-border (microvilli) epithelium, reabsorbs nearly all the glucose and amino acids, and about 70 to 80 per cent of the electrolytes and water.', $neph['svg']],
    [ZOOLOGY, 'Human Eye (Diagram)', 'Easy', "The light-sensitive layer labelled {$L($eye, 'Retina')} at the back of the eye contains the photoreceptors. They are:",
        ['Rods and cones', 'Hair cells', 'Taste buds', 'Olfactory cells'], 'A',
        'The retina has rods (dim-light, black and white vision) and cones (bright-light, colour vision). Hair cells are in the cochlea of the ear.', $eye['svg']],
    [ZOOLOGY, 'Muscle Contraction (Diagram)', 'Hard', 'During contraction of this sarcomere, which TWO labelled regions become shorter, while the A band stays the same length?',
        [
            "{$L($sarc, 'A band')} and {$L($sarc, 'H zone')}",
            "{$L($sarc, 'I band')} and {$L($sarc, 'H zone')}",
            "{$L($sarc, 'I band')} and {$L($sarc, 'A band')}",
            "{$L($sarc, 'Z line')} and {$L($sarc, 'A band')}",
        ], 'B',
        'In the sliding-filament model the thin filaments slide towards the centre. The I band (only thin filaments) and the H zone (only thick filaments) shorten, the Z lines move closer, but the A band (the length of the thick filaments) does not change.', $sarc['svg']],
    [ZOOLOGY, 'Bacteriophage (Diagram)', 'Medium', "In a T4 bacteriophage the part labelled {$L($phage, 'Tail sheath')} contracts after the phage attaches to a bacterium, pushing the core through the cell wall. Which part is this?",
        ['Head (capsid)', 'Collar', 'Tail sheath', 'Tail fibre'], 'C',
        'The contractile tail sheath shortens like a spring, driving the hollow core into the host cell so that the DNA from the head is injected. The tail fibres only attach to the receptors on the bacterial surface.', $phage['svg']],
    [ZOOLOGY, 'Lysosomes', 'Easy', "Lysosomes are called 'suicide bags' because they contain:",
        ['Hydrolytic enzymes', 'Chlorophyll', 'DNA polymerase', 'Oxidative phosphorylation enzymes'], 'A',
        'Lysosomes hold acid hydrolases (about 40 types, active at acidic pH). If the membrane bursts, these enzymes digest the cell itself, hence the name suicide bags.', null],
    [ZOOLOGY, 'Glomerular Filtration', 'Medium', 'What is the normal glomerular filtration rate (GFR) of a healthy adult?',
        ['About 12.5 mL/min', 'About 125 mL/min', 'About 1250 mL/min', 'About 25 mL/min'], 'B',
        'A healthy adult has a GFR of about 125 mL/min, that is about 180 litres of filtrate per day, of which only about 1.5 litres is excreted as urine because most is reabsorbed.', null],
    [ZOOLOGY, 'Loop of Henle', 'Hard', 'The main role of the loop of Henle and the vasa recta together is to:',
        ['Filter blood plasma under pressure', 'Secrete renin', 'Create and maintain the high osmolarity of the medullary interstitium (countercurrent mechanism)', 'Reabsorb all the glucose'], 'C',
        'The loop of Henle and the vasa recta work as a countercurrent system that builds up a gradient of osmolarity (about 300 mOsm/L in the cortex to about 1200 mOsm/L in the inner medulla). This lets the collecting duct produce concentrated urine.', null],

    // ------------------------------------------------------------ GENETICS & EVOLUTION
    [GENETICS, 'Monohybrid Cross (Punnett Square)', 'Easy', 'The Punnett square shows a cross between two heterozygous tall pea plants (Tt × Tt). What is the phenotypic ratio of the offspring (tall : dwarf)?',
        ['1 : 2 : 1', '3 : 1', '1 : 1', '9 : 3 : 3 : 1'], 'B',
        'The four squares are TT, Tt, Tt and tt. Three offspring carry at least one dominant T (tall) and one is tt (dwarf), so the phenotypic ratio is 3 tall : 1 dwarf. (The genotypic ratio is 1 TT : 2 Tt : 1 tt.)', $mono],
    [GENETICS, 'Dihybrid Cross (Punnett Square)', 'Medium', 'The 16-square grid shows a dihybrid cross RrYy × RrYy, where R (round) and Y (yellow) are dominant to r (wrinkled) and y (green). How many of the 16 offspring are round and green?',
        ['1', '3', '4', '9'], 'B',
        'Round green seeds have the genotype R_yy: RRyy (1) + Rryy (2) = 3 squares. So 3/16 are round and green.', $dihybrid],
    [GENETICS, 'Test Cross (Punnett Square)', 'Medium', 'A tall plant (Tt) is crossed with a dwarf plant (tt), as shown in the Punnett square. What is the ratio of tall : dwarf offspring?',
        ['3 : 1', '1 : 1', '1 : 2 : 1', 'All tall'], 'B',
        'Gametes of Tt are T and t; those of tt are only t. The four squares are Tt, tt, Tt and tt: two tall and two dwarf, a 1 : 1 ratio. This is the typical result of a test cross with a heterozygous plant, and it reveals the unknown genotype.', $testcross],
    [GENETICS, 'DNA Structure (Diagram)', 'Medium', "The base pair marked {$L($dna, 'G-C base pair (3 hydrogen bonds)')} in this DNA segment is held together by how many hydrogen bonds, and between which bases?",
        ['2 hydrogen bonds between adenine and thymine', '3 hydrogen bonds between guanine and cytosine', '3 hydrogen bonds between adenine and thymine', '2 hydrogen bonds between guanine and cytosine'], 'B',
        'Guanine pairs with cytosine through three hydrogen bonds (G≡C), while adenine pairs with thymine through two (A=T). This is why DNA rich in G-C pairs is more stable and has a higher melting temperature.', $dna['svg']],
    [GENETICS, 'Transcription and Translation (Sequence Diagram)', 'Hard', 'The template strand of a DNA segment is shown with its coding strand, mRNA and codons. Which polypeptide does this mRNA code for (up to the stop codon)?',
        ['Met - Pro - Lys', 'Met - Gly - Phe', 'Tyr - Gly - Phe', 'Met - Pro - Lys - Thr'], 'A',
        'The mRNA is complementary to the template: AUG CCU AAG UGA. AUG is the start codon (Met), CCU codes for Pro, AAG for Lys and UGA is a stop codon, so translation ends there and the polypeptide is Met-Pro-Lys.', $strip],
    [GENETICS, 'DNA Replication (Diagram)', 'Hard', "In this replication fork, the new strand labelled {$L($fork, 'Leading strand')} is synthesised:",
        ['Discontinuously as Okazaki fragments, away from the fork', 'Continuously, in the same direction as the fork opens (towards the fork)', 'Only after the lagging strand is complete', 'Without any template'], 'B',
        'DNA polymerase adds nucleotides only in the 5\' to 3\' direction. On the template that is read 3\' to 5\' towards the fork, the new strand (leading) is made continuously; on the other template, short Okazaki fragments (lagging strand) are made away from the fork and joined by DNA ligase.', $fork['svg']],
    [GENETICS, 'lac Operon (Diagram)', 'Hard', "In the lac operon of E. coli, the gene labelled {$L($lac, 'Regulator gene (lac i)')} codes for a protein that, in the absence of lactose, binds to the operator. What is this protein?",
        ['RNA polymerase', 'Repressor', 'Beta-galactosidase', 'Permease'], 'B',
        'The regulator (i) gene is expressed constitutively and makes the repressor protein. Without lactose the repressor binds the operator and blocks transcription of lac Z, Y and A; allolactose (the inducer) binds the repressor and releases the operator.', $lac['svg']],
    [GENETICS, 'Pedigree Analysis (Diagram)', 'Hard', 'In the pedigree chart shown, a trait appears in individual 5 although both her parents (1 and 2) are unaffected. What is the most likely mode of inheritance?',
        ['Autosomal dominant', 'Autosomal recessive', 'X-linked recessive', 'X-linked dominant'], 'B',
        'Two unaffected parents with an affected child means the trait is recessive (an unaffected carrier pair). An affected daughter cannot come from an unaffected father in X-linked recessive inheritance, since the father would have to give her an X carrying the allele and would himself be affected, so the trait is autosomal recessive.', $pedigreeAR],
    [GENETICS, 'Pedigree Analysis (Diagram)', 'Hard', 'In this pedigree only males are affected, and the trait passes through unaffected females and is never passed from father to son. What is the most likely mode of inheritance?',
        ['Autosomal dominant', 'Autosomal recessive', 'X-linked recessive', 'Y-linked'], 'C',
        'Unaffected carrier mothers pass an X carrying the allele to their sons, who are affected because they have only one X. Fathers cannot pass it to sons (sons get the Y), and affected males appear in alternate generations. This is X-linked recessive, like haemophilia and colour blindness. Y-linked traits would pass from every affected father to all his sons.', $pedigreeXL],
    [GENETICS, "Chargaff's Rule", 'Medium', 'In a double-stranded DNA molecule, 30 per cent of the bases are adenine. What percentage of the bases is guanine?',
        ['20%', '30%', '40%', '70%'], 'A',
        "By Chargaff's rule A = T and G = C. If A = 30 per cent then T = 30 per cent, so A + T = 60 per cent and G + C = 40 per cent, which means G = 20 per cent.", null],
    [GENETICS, 'Hardy-Weinberg Principle', 'Hard', 'In a population in Hardy-Weinberg equilibrium, 4 per cent of the individuals show a recessive trait. What percentage of the population are heterozygous carriers?',
        ['16%', '32%', '64%', '8%'], 'B',
        'q^2 = 0.04 so q = 0.2 and p = 1 − 0.2 = 0.8. The frequency of heterozygotes is 2pq = 2 × 0.8 × 0.2 = 0.32, that is 32 per cent.', null],
    [GENETICS, 'Evolution: Natural Selection', 'Medium', 'The increase in the proportion of dark (melanic) peppered moths, Biston betularia, in industrial areas of England during the 19th century is an example of:',
        ['Genetic drift', 'Natural selection (adaptive change)', 'The founder effect', 'Inheritance of acquired characters'], 'B',
        'In polluted woods darkened by soot, dark moths were better camouflaged from birds and survived and reproduced more, so the frequency of the melanic allele rose. This is natural selection, supporting Darwin\'s theory.', null],

    // ------------------------------------------------------------ ECOLOGY & ENVIRONMENT
    [ECOLOGY, 'Energy Flow and the 10% Law (Diagram)', 'Medium', 'The energy pyramid shows that producers fix 20,000 kJ of energy. According to the 10 per cent law, how much energy is available to the tertiary consumers?',
        ['2 kJ', '20 kJ', '200 kJ', '2,000 kJ'], 'B',
        'Only about 10 per cent of the energy at one trophic level passes to the next: producers 20,000 kJ → primary consumers 2,000 kJ → secondary consumers 200 kJ → tertiary consumers 20 kJ.', $energy],
    [ECOLOGY, 'Nitrogen Cycle (Flow Diagram)', 'Medium', 'In the nitrogen cycle shown, what is the process marked "?" by which atmospheric nitrogen is converted into ammonia?',
        ['Nitrification', 'Biological nitrogen fixation', 'Denitrification', 'Ammonification'], 'B',
        'Nitrogen-fixing organisms (Rhizobium in legume root nodules, Azotobacter, Anabaena, and others) reduce atmospheric N_2 to ammonia using the enzyme nitrogenase. Nitrification oxidises ammonia to nitrite and nitrate; denitrification returns N_2 to the air.', $nitrogen],
    [ECOLOGY, 'Population Growth (Graph)', 'Medium', 'The graph shows the growth of a population in a limited habitat. The carrying capacity (K) of the habitat is about:',
        ['40 individuals', '60 individuals', '80 individuals', '100 individuals'], 'C',
        'The curve is S-shaped (logistic): growth slows as resources become limiting and the population levels off at the carrying capacity, here about 80 individuals.', $growth],
    [ECOLOGY, 'Food Chains (Flow Diagram)', 'Medium', 'In the grassland food chain shown, the eagle is a:',
        ['Secondary consumer at the 3rd trophic level', 'Tertiary consumer at the 4th trophic level', 'Quaternary consumer at the 5th trophic level', 'Primary consumer at the 2nd trophic level'], 'C',
        'Grass (producer) is the 1st trophic level, grasshopper (primary consumer) the 2nd, frog (secondary consumer) the 3rd, snake (tertiary consumer) the 4th and the eagle (quaternary consumer) the 5th.', $foodChain],
    [ECOLOGY, 'Ecological Succession', 'Easy', 'The pioneer species in primary succession on a bare rock (xerarch succession) are usually:',
        ['Mosses', 'Lichens', 'Shrubs', 'Annual herbs'], 'B',
        'Crustose lichens secrete acids that weather the rock and make the first soil. They are followed by mosses, herbs, shrubs and finally the climax forest.', null],
];

// ------------------------------------------------------------------ balance the answer key
// Hand-written keys cluster on one letter. Give every question a target slot for its correct option
// (balanced A/B/C/D, fixed seed so the paper is reproducible) and move the correct option there.
mt_srand(20261029);
$targets = [];
foreach (array_keys($Q) as $i) {
    $targets[] = $i % 4;
}
for ($i = count($targets) - 1; $i > 0; $i--) {
    $j = mt_rand(0, $i);
    [$targets[$i], $targets[$j]] = [$targets[$j], $targets[$i]];
}
foreach ($Q as $i => &$q) {
    $right = ord($q[5]) - 65;
    $correctText = $q[4][$right];
    $rest = array_values(array_diff_key($q[4], [$right => true]));
    array_splice($rest, $targets[$i], 0, [$correctText]);
    $q[4] = $rest;
    $q[5] = 'ABCD'[$targets[$i]];
}
unset($q);

// ------------------------------------------------------------------ SQL
$sq = fn (?string $v) => $v === null ? 'NULL' : "'" . str_replace(['\\', "'", "\0"], ['\\\\', "''", '\\0'], $v) . "'";
$out = [
    '-- Generated by tools/build-bio.php. Re-importing replaces the NEET biology paper and preset.',
    'USE cbt_portal;',
    'SET NAMES utf8mb4;',
    '',
    "DELETE FROM presets WHERE id = 'NEET_BIO';",
    "DELETE FROM exams WHERE slug = 'neet-bio';",
    '',
    "INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('NEET_BIO', 'NEET Biology', 'Medical Entrance / Biology', 45, 4, 1, 40, 7);",
];
$sections = [
    [BOTANY, 'Plant cell and organelles, chloroplast, photosynthesis (C3 and C4), flower structure, double fertilisation, plant kingdom, plant physiology, cell cycle and mitosis', 25],
    [ZOOLOGY, 'Animal cell organelles, mitochondria, human heart and circulation, nephron and excretion, neuron and synapse, human eye, muscle contraction, endocrine system, digestion, breathing', 30],
    [GENETICS, 'Mendelian genetics, Punnett squares, pedigree analysis, DNA structure, replication, transcription, translation, lac operon, Hardy-Weinberg principle, evolution', 30],
    [ECOLOGY, 'Energy flow, ecological pyramids, food chains, nutrient cycles, succession, population growth, biodiversity, environmental issues', 15],
];
foreach ($sections as $i => [$n, $t, $w]) {
    $out[] = sprintf("INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('NEET_BIO', %d, %s, %s, %d);", $i + 1, $sq($n), $sq($t), $w);
}
$out[] = '';
$out[] = "INSERT INTO exams (slug, title, source, status, duration_minutes, positive_marks, negative_marks, include_diagrams) VALUES ('neet-bio', 'NEET Biology: Botany, Zoology & Genetics', 'demo', 'ready', 45, 4, 1, 1);";
$out[] = 'SET @bio_exam = LAST_INSERT_ID();';
foreach ($Q as $i => [$section, $topic, $diff, $text, $options, $correct, $explanation, $svg]) {
    if (count($options) !== 4 || !in_array($correct, ['A', 'B', 'C', 'D'], true)) {
        throw new RuntimeException("Question " . ($i + 1) . " is malformed");
    }
    if ($svg === null && preg_match('/\b(shown|diagram|figure|graph|pedigree|labelled)\b/i', $text . ' ')) {
        throw new RuntimeException("Question " . ($i + 1) . " mentions a figure but has none");
    }
    $out[] = sprintf(
        'INSERT INTO questions (exam_id, position, section, topic, difficulty, question_text, has_diagram, svg_diagram, explanation, correct_option) VALUES (@bio_exam, %d, %s, %s, %s, %s, %d, %s, %s, %s);',
        $i + 1, $sq($section), $sq($topic), $sq($diff), $sq($text), $svg ? 1 : 0, $sq($svg), $sq($explanation), $sq($correct)
    );
    $out[] = 'SET @q = LAST_INSERT_ID();';
    foreach ($options as $j => $o) {
        $out[] = sprintf("INSERT INTO question_options (question_id, label, option_text) VALUES (@q, '%s', %s);", 'ABCD'[$j], $sq($o));
    }
}
file_put_contents(__DIR__ . '/../database/neet_biology.sql', implode("\n", $out) . "\n");
$diagrams = count(array_filter($Q, fn ($q) => $q[7] !== null));
$unique = count(array_unique(array_filter(array_column($Q, 7))));
echo count($Q) . " questions ($diagrams with diagrams, $unique distinct pictures) + NEET_BIO preset\n";
echo json_encode(array_count_values(array_column($Q, 0))), "\n";
$letters = array_count_values(array_column($Q, 5));
ksort($letters);
echo 'answer key spread: ', json_encode($letters), "\n";

$i = array_search('--svg', $argv, true);
if ($i !== false && isset($argv[$i + 1])) {
    @mkdir($argv[$i + 1], 0777, true);
    foreach ($Q as $n => $q) {
        if ($q[7]) {
            file_put_contents($argv[$i + 1] . '/q' . ($n + 1) . '.svg', $q[7]);
        }
    }
}
