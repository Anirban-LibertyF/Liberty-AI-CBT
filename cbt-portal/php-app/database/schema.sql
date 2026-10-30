-- NexusCBT (PHP + MySQL/MariaDB) schema
-- Import with phpMyAdmin, or:  mysql -u root < database/schema.sql
-- Then import database/seed.sql (presets + the demo paper).

CREATE DATABASE IF NOT EXISTS cbt_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cbt_portal;

-- Exam templates shown on the configuration screen (SSC CGL, GATE CS, ...)
CREATE TABLE IF NOT EXISTS presets (
  id               VARCHAR(32)  NOT NULL PRIMARY KEY,
  name             VARCHAR(100) NOT NULL,
  badge            VARCHAR(100) NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL,
  positive_marks   DECIMAL(5,2) NOT NULL,
  negative_marks   DECIMAL(5,2) NOT NULL,
  default_count    SMALLINT UNSIGNED NOT NULL,
  sort_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS preset_sections (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  preset_id VARCHAR(32)  NOT NULL,
  position  SMALLINT UNSIGNED NOT NULL,
  name      VARCHAR(120) NOT NULL,
  topics    VARCHAR(500) NOT NULL,
  weight    SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  CONSTRAINT fk_ps_preset FOREIGN KEY (preset_id) REFERENCES presets (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A question paper. The built-in demo paper has slug 'demo'; AI papers have slug NULL.
CREATE TABLE IF NOT EXISTS exams (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(32)  NULL UNIQUE,
  title            VARCHAR(200) NOT NULL,
  source           ENUM('demo','ai') NOT NULL,
  status           ENUM('generating','ready') NOT NULL DEFAULT 'generating',
  duration_minutes SMALLINT UNSIGNED NOT NULL,
  positive_marks   DECIMAL(5,2) NOT NULL,
  negative_marks   DECIMAL(5,2) NOT NULL,
  difficulty_mix   VARCHAR(120) NULL,
  include_diagrams TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- One row per parallel Gemini call (a section, or a 12-question slice of a big section).
CREATE TABLE IF NOT EXISTS exam_batches (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  exam_id         INT UNSIGNED NOT NULL,
  section_name    VARCHAR(120) NOT NULL,
  topics          VARCHAR(500) NOT NULL,
  question_count  SMALLINT UNSIGNED NOT NULL,
  part_label      VARCHAR(30)  NOT NULL,
  status          ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  generated_count SMALLINT UNSIGNED NULL,
  CONSTRAINT fk_batch_exam FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  exam_id        INT UNSIGNED NOT NULL,
  batch_id       INT UNSIGNED NULL,
  position       SMALLINT UNSIGNED NOT NULL DEFAULT 0,   -- 1-based question number
  section        VARCHAR(120) NOT NULL,
  topic          VARCHAR(200) NOT NULL,
  difficulty     ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
  question_text  MEDIUMTEXT   NOT NULL,                  -- may contain LaTeX-style markup
  has_diagram    TINYINT(1)   NOT NULL DEFAULT 0,
  svg_diagram    MEDIUMTEXT   NULL,
  diagram_key    VARCHAR(80)  NULL,                      -- e.g. catalog:animal_cell, punnett:TtxTt (variety tracking)
  explanation    MEDIUMTEXT   NOT NULL,
  correct_option CHAR(1)      NOT NULL,
  KEY idx_q_exam_pos (exam_id, position),
  CONSTRAINT fk_q_exam  FOREIGN KEY (exam_id)  REFERENCES exams (id) ON DELETE CASCADE,
  CONSTRAINT fk_q_batch FOREIGN KEY (batch_id) REFERENCES exam_batches (id) ON DELETE SET NULL,
  CONSTRAINT chk_q_correct CHECK (correct_option IN ('A','B','C','D'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS question_options (
  question_id INT UNSIGNED NOT NULL,
  label       CHAR(1)      NOT NULL,
  option_text TEXT         NOT NULL,
  PRIMARY KEY (question_id, label),
  CONSTRAINT fk_opt_q FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A candidate sitting an exam. Times are UTC. The server owns the clock:
-- deadline = started_at + exams.duration_minutes.
CREATE TABLE IF NOT EXISTS attempts (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  exam_id          INT UNSIGNED NOT NULL,
  status           ENUM('in_progress','submitted') NOT NULL DEFAULT 'in_progress',
  started_at       DATETIME NOT NULL,
  submitted_at     DATETIME NULL,
  -- filled in when the attempt is submitted
  correct_count     SMALLINT UNSIGNED NULL,
  incorrect_count   SMALLINT UNSIGNED NULL,
  unattempted_count SMALLINT UNSIGNED NULL,
  total_score       DECIMAL(8,2) NULL,
  max_score         DECIMAL(8,2) NULL,
  KEY idx_attempt_exam (exam_id),
  CONSTRAINT fk_attempt_exam FOREIGN KEY (exam_id) REFERENCES exams (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Palette state + chosen option + time spent, per question per attempt.
CREATE TABLE IF NOT EXISTS attempt_answers (
  attempt_id         INT UNSIGNED NOT NULL,
  question_id        INT UNSIGNED NOT NULL,
  selected_option    CHAR(1) NULL,
  cbt_state          ENUM('NOT_VISITED','NOT_ANSWERED','ANSWERED','MARKED_FOR_REVIEW','ANSWERED_AND_MARKED')
                     NOT NULL DEFAULT 'NOT_VISITED',
  time_spent_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (attempt_id, question_id),
  CONSTRAINT fk_aa_attempt  FOREIGN KEY (attempt_id)  REFERENCES attempts (id)  ON DELETE CASCADE,
  CONSTRAINT fk_aa_question FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coach_reports (
  attempt_id INT UNSIGNED NOT NULL PRIMARY KEY,
  source     ENUM('ai','offline') NOT NULL,
  report     LONGTEXT NOT NULL,                          -- JSON
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coach_attempt FOREIGN KEY (attempt_id) REFERENCES attempts (id) ON DELETE CASCADE
) ENGINE=InnoDB;
