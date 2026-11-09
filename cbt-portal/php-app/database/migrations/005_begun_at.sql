-- Exam clock now starts when the candidate clicks "I am ready to begin". Run once on an existing database.
USE cbt_portal;
ALTER TABLE attempts ADD COLUMN begun_at DATETIME NULL AFTER started_at;
UPDATE attempts SET begun_at = started_at WHERE begun_at IS NULL;
