-- =================================================================
-- SOPRA — migration v2
-- Run this ONCE against an existing sopra_db that was created from
-- the OLD database_schema.sql (single duty_date column, free-text location
-- only). Skip this file entirely on a brand-new install — the
-- updated database_schema.sql already has the new structure.
--
--   mysql -u root -p sopra_db < migration_v2_duty_fields.sql
-- =================================================================

USE sopra_db;

-- 1. Add the new columns (nullable for now, so the ALTER succeeds
--    even with existing rows).
ALTER TABLE duty_assignments
    ADD COLUMN state      VARCHAR(50)  NULL AFTER personnel_id,
    ADD COLUMN district   VARCHAR(100) NULL AFTER state,
    ADD COLUMN duty_type  ENUM('CONFIDENTIAL','COURT_HEARING','LDP','EXHIBITION','OTHER')
                           NOT NULL DEFAULT 'OTHER' AFTER location,
    ADD COLUMN date_start DATE NULL AFTER duty_type,
    ADD COLUMN date_end   DATE NULL AFTER date_start;

-- 2. Backfill date_start from the old duty_date column so existing
--    records keep their date instead of becoming blank.
UPDATE duty_assignments SET date_start = duty_date WHERE date_start IS NULL;

-- 3. Backfill state/district with a placeholder so old free-text
--    "location" rows remain visible under the new filters. Go back
--    and correct these manually in the UI when convenient — they are
--    NOT auto-mapped from the old location text.
UPDATE duty_assignments SET state = 'UNSPECIFIED' WHERE state IS NULL;
UPDATE duty_assignments SET district = 'UNSPECIFIED' WHERE district IS NULL;

-- 4. Now that every row has a value, make the required columns
--    NOT NULL and drop the old duty_date column.
ALTER TABLE duty_assignments
    MODIFY COLUMN state      VARCHAR(50)  NOT NULL,
    MODIFY COLUMN district   VARCHAR(100) NOT NULL,
    MODIFY COLUMN date_start DATE NOT NULL,
    MODIFY COLUMN location   VARCHAR(150) NULL,
    DROP COLUMN duty_date;

-- 5. Rebuild indexes for the new date columns.
DROP INDEX idx_duty_date ON duty_assignments;
CREATE INDEX idx_duty_date_start ON duty_assignments (date_start);
CREATE INDEX idx_duty_date_end   ON duty_assignments (date_end);
