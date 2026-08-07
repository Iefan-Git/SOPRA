-- Migration: drop purpose and notes columns from duty_assignments
-- WARNING: Backup your database before running this migration.
-- Usage (mysql):
--   mysql -u <user> -p <database_name> < migration_drop_purpose_notes.sql

ALTER TABLE duty_assignments
  DROP COLUMN IF EXISTS purpose,
  DROP COLUMN IF EXISTS notes;

-- End of migration
