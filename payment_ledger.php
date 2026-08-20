<?php
/**
 * payment_ledger.php
 * SOPRA — admin payment ledger controller.
 *
 * This used to be one ~630-line file mixing filter parsing, POST
 * handling, CSV export, stats, and HTML together. It's now a thin
 * controller that wires up the pieces:
 *
 *   includes/payment_query.php   buildQuery()
 *   includes/payment_data.php    payInfo()
 *   includes/payment_stats.php   computePaymentStats()
 *   actions/payment_actions.php  handlePaymentActions() — all POST actions
 *   exports/payment_export.php   exportPaymentFullYear(), exportPaymentMonth()
 *   views/payment_ledger_view.php  all the HTML
 */
require_once 'config.php';
requireAdmin();

require_once 'includes/payment_query.php';
require_once 'includes/payment_data.php';
require_once 'includes/payment_stats.php';

// -----------------------------------------------------------------
// Filters — year, search text, selected ranks (comma list), plus a
// single month + payment-status combo (e.g. "show who hasn't paid
// for MAY"). Action forms resubmit these as hidden POST fields so
// the current view is preserved after a redirect; plain navigation
// uses GET.
// -----------------------------------------------------------------
$src           = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$year          = isset($src['year']) ? (int) $src['year'] : (int) date('Y');
if ($year < 2000 || $year > 2100) { $year = (int) date('Y'); }
$search        = trim($src['search'] ?? '');
$selRanksRaw   = trim($src['ranks'] ?? '');
$selRanks      = $selRanksRaw === '' ? [] : explode(',', $selRanksRaw);

$monthRaw      = $src['fmonth'] ?? '';
$filterMonth   = ($monthRaw !== '' && (int) $monthRaw >= 0 && (int) $monthRaw <= 11) ? (int) $monthRaw : null;
$filterStatus  = in_array($src['fstatus'] ?? '', ['paid', 'unpaid'], true) ? $src['fstatus'] : '';

// -----------------------------------------------------------------
// Handle POST actions (redirects + exits on its own)
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'actions/payment_actions.php';
    handlePaymentActions($pdo);
}

// -----------------------------------------------------------------
// Fetch filtered personnel + payments for the ledger table
// -----------------------------------------------------------------
$sql = 'SELECT id, rank_name, name FROM personnel WHERE 1=1';
$args = [];
if ($search !== '') {
    $sql .= ' AND name LIKE ?';
    $args[] = '%' . $search . '%';
}
if (!empty($selRanks)) {
    $placeholders = implode(',', array_fill(0, count($selRanks), '?'));
    $sql .= " AND rank_name IN ($placeholders)";
    $args = array_merge($args, $selRanks);
}
$sql .= ' ORDER BY name ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$personnelList = $stmt->fetchAll();

// Payments for this year, keyed by "id|month" => ['paid'=>bool,'amount'=>?float,'paid_date'=>?string]
$payMap = [];
$payStmt = $pdo->prepare('SELECT personnel_id, month, paid, amount, paid_date FROM payments WHERE year = ?');
$payStmt->execute([$year]);
foreach ($payStmt->fetchAll() as $row) {
    $payMap[$row['personnel_id'] . '|' . $row['month']] = [
        'paid'      => (bool) $row['paid'],
        'amount'    => $row['amount'] !== null ? (float) $row['amount'] : null,
        'paid_date' => $row['paid_date'],
    ];
}

// Keep an unfiltered (by month/status) copy for the "link to personnel"
// dropdown in the Manage Users modal — that list shouldn't shrink just
// because the ledger view is filtered to "who hasn't paid in May".
$linkablePersonnel = $personnelList;

// Apply the month + payment-status filter (e.g. "show who hasn't paid
// for MAY") on top of the search/rank filters already applied in SQL.
if ($filterMonth !== null && $filterStatus !== '') {
    $personnelList = array_values(array_filter($personnelList, function ($pp) use ($payMap, $filterMonth, $filterStatus) {
        $paid = payInfo($payMap, $pp['id'], $filterMonth)['paid'];
        return $filterStatus === 'paid' ? $paid : !$paid;
    }));
}

// Stats for the summary cards
$stats          = computePaymentStats($personnelList, $payMap, $year);
$rate           = $stats['rate'];
$fullyPaid      = $stats['fullyPaid'];
$paidThisMonth  = $stats['paidThisMonth'];
$totalCollected = $stats['totalCollected'];

// -----------------------------------------------------------------
// CSV exports (stream + exit on their own)
// -----------------------------------------------------------------
if (isset($_GET['export'])) {
    require_once 'exports/payment_export.php';
    exportPaymentFullYearPdf($personnelList, $payMap, $year);
}
if (isset($_GET['export_month'])) {
    require_once 'exports/payment_export.php';
    exportPaymentMonthPdf($personnelList, $payMap, $year, $filterMonth);
}

// All users, for the "Manage Users" modal — left-joined to personnel so
// each "user"-role account can show/search by its linked Name / Call Sign
// and Rank.
$allUsers = $pdo->query(
    "SELECT u.id, u.username, u.role, p.name AS member_name, p.rank_name
     FROM users u
     LEFT JOIN personnel p ON p.id = u.personnel_id
     ORDER BY u.username ASC"
)->fetchAll();

// Every member, for the "Remove Member" picker in Manage Users —
// searchable by Name/Call Sign + Rank same as everywhere else in the app.
// Removing here deletes the personnel record itself (cascading their
// payment/duty history); it does not touch any login account, which is
// removed separately via the "x" next to a user in the list above.
$membersForRemoval = $pdo->query(
    "SELECT p.id AS personnel_id, p.name, p.rank_name
     FROM personnel p
     ORDER BY p.name ASC"
)->fetchAll();
$membersByRank = [];
foreach ($membersForRemoval as $m) {
    $membersByRank[$m['rank_name']][] = [
        'personnel_id' => $m['personnel_id'],
        'label'        => $m['name'],
    ];
}

// Which modal (if any) is open, driven by GET params
$modal = $_GET['modal'] ?? null;
$editData = null;
if ($modal === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT id, rank_name, name FROM personnel WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editData = $stmt->fetch();
}

// Freshly generated login credentials, shown once (right after
// "Add User" creates a member account) so the admin can hand them off.
$newUsername = $_GET['new_username'] ?? null;
$newPassword = $_GET['new_password'] ?? null;

// Upcoming duty assignments (next 5, soonest first) for the dashboard glance
$upcomingDuty = $pdo->query(
    "SELECT da.id, da.state, da.district, da.location, da.date_start, p.name, p.rank_name
     FROM duty_assignments da
     JOIN personnel p ON p.id = da.personnel_id
     WHERE da.date_start >= CURDATE()
     ORDER BY da.date_start ASC LIMIT 5"
)->fetchAll();

require 'views/payment_ledger_view.php';
