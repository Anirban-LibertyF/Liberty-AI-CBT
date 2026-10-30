-- Remembers which diagram each AI question used, so later papers can pick different ones.
-- Run once on an existing cbt_portal database (fresh installs already get the column from schema.sql).
USE cbt_portal;
ALTER TABLE questions ADD COLUMN diagram_key VARCHAR(80) NULL AFTER svg_diagram;
