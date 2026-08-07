<?php
/**
 * config/database.php
 * SOPRA — database connection + first-run seed.
 *
 * Opens the PDO connection every page uses ($pdo), and — on a
 * completely empty install — seeds one default admin account so the
 * system is reachable for the very first login.
 */

// ---------------------------------------------------------------
// Database configuration — edit these to match your MySQL setup
// ---------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'sopra_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $ex) {
    die('Database connection failed: ' . $ex->getMessage() . ' — please import database_schema.sql first.');
}

// ---------------------------------------------------------------
// First-run seed: if there are no users yet, create a default admin
// account so the system is reachable. Change this password after
// your first login (see "Manage Users" in payment_ledger.php).
//
// IMPORTANT: SOPRA has NO public self-registration. The only way a
// new account is created is by an existing admin, from inside the
// admin dashboard. This seed exists purely so there is one admin
// account to log in with on a brand-new install.
// ---------------------------------------------------------------
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($userCount === 0) {
    $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
    $seedStmt = $pdo->prepare(
        'INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, NULL)'
    );
    $seedStmt->execute(['admin', $defaultHash, 'admin']);
}
