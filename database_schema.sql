-- =================================================================
-- SOPRA — System for Operational Personnel Resource Allocation
-- Database schema
--
-- Import this once before first run, e.g. in phpMyAdmin or:
--   mysql -u root -p < database_schema.sql
-- =================================================================

CREATE DATABASE IF NOT EXISTS sopra_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sopra_db;

-- -----------------------------------------------------------------
-- personnel: every anggota tracked by the system
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS personnel (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    rank_name  VARCHAR(20)  NOT NULL,
    name       VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------------
-- users: login accounts. There is NO public signup — every account
-- (admin or user) is created by an existing admin from inside the
-- admin dashboard (Urus Pengguna). A 'user' account is optionally
-- linked to one personnel row, so that person can log in and see
-- only their own contribution + duty records.
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('admin','user') NOT NULL DEFAULT 'user',
    personnel_id INT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------------
-- payments: one row per personnel/year/month. Members contribute
-- whatever amount they choose each month (no fixed rank fee) —
-- "paid" is the green/red flag, "amount" is what they actually
-- gave, "paid_date" is the day the admin recorded it.
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    year         INT NOT NULL,
    month        TINYINT NOT NULL,              -- 0 = Jan ... 11 = Dec
    paid         TINYINT(1) NOT NULL DEFAULT 0,  -- drives the green/red tick
    amount       DECIMAL(10,2) NULL,             -- RM amount the member chose to pay
    paid_date    DATE NULL,                      -- day the payment was recorded
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_personnel_month (personnel_id, year, month),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------
-- duty_assignments: the "where is this anggota on duty" tracker —
-- state/district, date range (departure -> return) and duty type.
--
-- date_end is NULLABLE: a NULL date_end means the operation is still
-- ongoing / the return date is not yet known. Duration and status
-- (Upcoming / Ongoing / Completed) are derived from date_start and
-- date_end at query time, not stored.
-- -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS duty_assignments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    state        VARCHAR(50)  NOT NULL,   -- negeri
    district     VARCHAR(100) NOT NULL,   -- daerah (depends on state)
    location     VARCHAR(150) NULL,       -- optional specific venue/address within the district
    duty_type    ENUM('CONFIDENTIAL','COURT_HEARING','LDP','EXHIBITION','OTHER') NOT NULL DEFAULT 'OTHER',
    date_start   DATE NOT NULL,           -- tarikh pergi
    date_end     DATE NULL,               -- tarikh pulang (NULL = still ongoing)
    created_by   INT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_duty_date_start ON duty_assignments (date_start);
CREATE INDEX idx_duty_date_end ON duty_assignments (date_end);
CREATE INDEX idx_duty_personnel ON duty_assignments (personnel_id);
