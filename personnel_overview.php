<?php
/**
 * personnel_overview.php
 * SOPRA — regular member's own "My Ledger" controller.
 * Fetches this member's own payment + duty data, then hands off to
 * views/personnel_overview_view.php for the HTML.
 */
require_once 'config.php';
requireUser(); // logged in AND not an admin

$personnelId = $_SESSION['personnel_id'] ?? null;

// Selected year (defaults to current year), constrained to a sane range.
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
if ($year < 2000 || $year > 2100) {
    $year = (int) date('Y');
}

$person = null;
$payMonths = [];      // month index (0-11) => ['paid'=>bool,'amount'=>?float,'paid_date'=>?string]
$paidCount  = 0;
$yearTotal  = 0.0;
$duties     = [];

if ($personnelId) {
    // Fetch ONLY this user's own personnel row — no other rows are ever queried.
    $stmt = $pdo->prepare('SELECT id, rank_name, name FROM personnel WHERE id = ?');
    $stmt->execute([$personnelId]);
    $person = $stmt->fetch();

    if ($person) {
        $payStmt = $pdo->prepare('SELECT month, paid, amount, paid_date FROM payments WHERE personnel_id = ? AND year = ?');
        $payStmt->execute([$personnelId, $year]);
        foreach ($payStmt->fetchAll() as $row) {
            $payMonths[(int) $row['month']] = [
                'paid'      => (bool) $row['paid'],
                'amount'    => $row['amount'] !== null ? (float) $row['amount'] : null,
                'paid_date' => $row['paid_date'],
            ];
            if ($row['paid']) {
                $paidCount++;
                $yearTotal += (float) ($row['amount'] ?? 0);
            }
        }

        // This member's own duty/operation schedule — where, when, why.
        $dutyStmt = $pdo->prepare(
            'SELECT state, district, location, duty_type, date_start, date_end
             FROM duty_assignments
             WHERE personnel_id = ? ORDER BY date_start DESC LIMIT 20'
        );
        $dutyStmt->execute([$personnelId]);
        $duties = $dutyStmt->fetchAll();
    }
}

require 'views/personnel_overview_view.php';
