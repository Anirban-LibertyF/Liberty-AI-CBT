-- Extra exam presets (SSC CHSL/MTS, UPSC, WB PSC, WB Police, SBI Clerk), the final display order of all
-- presets, and removal of SMFWBEE. Import after seed.sql and the paper files. Safe to re-run.
USE cbt_portal;
SET NAMES utf8mb4;

DELETE FROM presets WHERE id = 'SMFWBEE';
DELETE FROM exams WHERE slug = 'smfwbee';

DELETE FROM presets WHERE id = 'SSC_CHSL';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('SSC_CHSL', 'SSC CHSL Tier-I', 'Govt / Staff Selection', 60, 2, 0.5, 100, 2);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_CHSL', 1, 'General Intelligence & Reasoning', 'Analogy, Classification, Series, Coding-Decoding, Venn Diagrams, Matrix, Non-Verbal Reasoning', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_CHSL', 2, 'General Awareness', 'Static GK, Indian Polity, History, Geography, General Science, Current Affairs', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_CHSL', 3, 'Quantitative Aptitude', 'Percentages, Profit & Loss, Time & Work, Simplification, Algebra, Geometry, Mensuration, Data Interpretation', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_CHSL', 4, 'English Language', 'Error Spotting, Fill in the Blanks, Synonyms & Antonyms, Idioms & Phrases, Reading Comprehension, Cloze Test', 25);
DELETE FROM presets WHERE id = 'SSC_MTS';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('SSC_MTS', 'SSC MTS (Havaldar)', 'Govt / Staff Selection', 90, 1, 0.25, 90, 3);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_MTS', 1, 'Numerical & Mathematical Ability', 'Number System, Percentage, Ratio & Proportion, Average, Simple Interest, Time & Work, Mensuration', 22);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_MTS', 2, 'Reasoning Ability', 'Analogy, Series, Coding-Decoding, Blood Relations, Direction Sense, Non-Verbal Reasoning', 22);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_MTS', 3, 'General English', 'Vocabulary, Error Spotting, Fill in the Blanks, Comprehension, Sentence Rearrangement', 28);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SSC_MTS', 4, 'General Awareness', 'Static GK, Indian Polity, History, Geography, General Science, Current Affairs', 28);
DELETE FROM presets WHERE id = 'UPSC_CSE';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('UPSC_CSE', 'UPSC Civil Services Prelims (GS Paper I)', 'Civil Services / UPSC', 120, 2, 0.66, 100, 4);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 1, 'History & Culture', 'Ancient, Medieval & Modern India, Freedom Struggle, Art & Culture', 17);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 2, 'Polity & Governance', 'Constitution, Parliament, Judiciary, Federalism, Public Policy, Rights Issues', 18);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 3, 'Geography', 'Physical Geography, Indian Geography, Climate, Resources, World Geography', 15);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 4, 'Economy', 'Macroeconomics, Banking, Budget & Fiscal Policy, Inflation, External Sector, Agriculture', 17);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 5, 'Environment & Ecology', 'Biodiversity, Climate Change, Conservation, Environmental Conventions, Ecology', 13);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 6, 'Science & Technology', 'Space, Defence, Biotechnology, IT & AI, Health, Everyday Science', 10);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('UPSC_CSE', 7, 'Current Affairs', 'National & International Events, Government Schemes, Reports & Indices', 10);
DELETE FROM presets WHERE id = 'WBPSC_CLERK';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('WBPSC_CLERK', 'WB PSC Clerkship', 'West Bengal Public Service Commission', 90, 1, 0.25, 100, 5);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBPSC_CLERK', 1, 'General Knowledge & Current Affairs', 'West Bengal GK, India GK, Current Affairs, History, Geography, Polity', 30);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBPSC_CLERK', 2, 'Arithmetic & Numerical Ability', 'Percentage, Ratio, Average, Profit & Loss, Time & Work, Simple & Compound Interest', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBPSC_CLERK', 3, 'Reasoning', 'Analogy, Series, Coding-Decoding, Blood Relations, Direction Sense, Syllogism', 20);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WBPSC_CLERK', 4, 'English & Bengali Language', 'Grammar, Vocabulary, Error Spotting, Comprehension, Bengali Grammar & Usage', 25);
DELETE FROM presets WHERE id = 'WB_POLICE';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('WB_POLICE', 'WB Police Constable (Prelims)', 'West Bengal Police Recruitment Board', 90, 1, 0.25, 100, 6);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WB_POLICE', 1, 'General Knowledge & Current Affairs', 'West Bengal GK, Indian Polity, History, Geography, Sports, Current Affairs', 30);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WB_POLICE', 2, 'Arithmetic', 'Number System, Percentage, Ratio, Average, Time & Work, Time Speed & Distance, Mensuration', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WB_POLICE', 3, 'Reasoning', 'Analogy, Series, Coding-Decoding, Blood Relations, Direction Sense, Mirror Images', 25);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('WB_POLICE', 4, 'English / Bengali Language', 'Grammar, Vocabulary, Error Spotting, Comprehension, Bengali Grammar & Usage', 20);
DELETE FROM presets WHERE id = 'SBI_CLERK';
INSERT INTO presets (id, name, badge, duration_minutes, positive_marks, negative_marks, default_count, sort_order) VALUES ('SBI_CLERK', 'SBI Clerk Prelims', 'Banking Sector', 60, 1, 0.25, 100, 8);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SBI_CLERK', 1, 'English Language', 'Reading Comprehension, Cloze Test, Error Spotting, Para Jumbles, Fillers, Phrase Replacement', 30);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SBI_CLERK', 2, 'Numerical Ability', 'Simplification, Number Series, Quadratic Equations, Data Interpretation, Arithmetic Word Problems', 35);
INSERT INTO preset_sections (preset_id, position, name, topics, weight) VALUES ('SBI_CLERK', 3, 'Reasoning Ability', 'Puzzles, Seating Arrangement, Inequality, Syllogism, Blood Relations, Direction Sense', 35);

UPDATE presets SET sort_order = 7 WHERE id = 'IBPS_PO';
UPDATE presets SET sort_order = 9 WHERE id = 'JEE_MAIN';
UPDATE presets SET sort_order = 10 WHERE id = 'CAT_MBA';
UPDATE presets SET sort_order = 11 WHERE id = 'NEET_UG';
UPDATE presets SET sort_order = 12 WHERE id = 'RRB_NTPC';
UPDATE presets SET sort_order = 13 WHERE id = 'WBCS_PRE';
UPDATE presets SET sort_order = 14 WHERE id = 'WBJEE';
UPDATE presets SET sort_order = 15 WHERE id = 'JENPAS_UG';
UPDATE presets SET sort_order = 16 WHERE id = 'GNM_ANM';
