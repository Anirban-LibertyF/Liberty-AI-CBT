-- Adds user accounts. Run once on an existing cbt_portal database (fresh installs get this from schema.sql).
USE cbt_portal;
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(80)  NOT NULL,
  phone         CHAR(10)     NOT NULL UNIQUE,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE attempts ADD COLUMN user_id INT UNSIGNED NULL AFTER exam_id,
  ADD KEY idx_attempt_user (user_id),
  ADD CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL;
