-- Adds the Bengali translation cache. Run once on an existing cbt_portal database.
USE cbt_portal;
CREATE TABLE IF NOT EXISTS question_translations (
  question_id   INT UNSIGNED NOT NULL,
  lang          CHAR(2)      NOT NULL,
  question_text MEDIUMTEXT   NOT NULL,
  options_json  TEXT         NOT NULL,                   -- {"A": "...", "B": "...", ...}
  explanation   MEDIUMTEXT   NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (question_id, lang),
  CONSTRAINT fk_qt_q FOREIGN KEY (question_id) REFERENCES questions (id) ON DELETE CASCADE
) ENGINE=InnoDB;
