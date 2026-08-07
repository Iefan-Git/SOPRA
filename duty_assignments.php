<?php
/**
 * duty_assignments.php
 * SOPRA — duty & operation location controller.
 *
 * Thin controller wiring up:
 *   includes/duty_query.php     buildDutyQuery()
 *   actions/duty_actions.php    handleDutyActions() — add/delete duty
 *   exports/duty_export.php     exportDutyCsv()
 *   views/duty_assignments_view.php  all the HTML
 */
require_once 'config.php';
requireAdmin();

require_once 'includes/duty_query.php';

$error   = '';
$success = '';

// -----------------------------------------------------------------
// Handle actions. add_duty may fail validation and fall through to
// render the page again (with the form repopulated); every other
// outcome redirects and exits inside handleDutyActions().
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'actions/duty_actions.php';
    $error = handleDutyActions($pdo);
}

if (isset($_GET['added'])) {
    $success = 'Duty assignment recorded successfully.';
}

// -----------------------------------------------------------------
// Filters
// -----------------------------------------------------------------
$stateFl     = array_key_exists($_GET['state'] ?? '', STATES_DISTRICTS) ? $_GET['state'] : '';
$districtFl  = trim($_GET['district'] ?? '');
$personnelFl = isset($_GET['personnel_id']) && $_GET['personnel_id'] !== '' ? (int) $_GET['personnel_id'] : null;
$rankFl      = in_array($_GET['rank'] ?? '', RANKS, true) ? $_GET['rank'] : '';
$statusFl    = in_array($_GET['status'] ?? '', ['upcoming', 'ongoing', 'completed'], true) ? $_GET['status'] : '';

$sql = "SELECT da.id, da.state, da.district, da.location, da.duty_type, da.date_start, da.date_end,
               p.id AS personnel_id, p.name, p.rank_name
        FROM duty_assignments da
        JOIN personnel p ON p.id = da.personnel_id
        WHERE 1=1";
$args = [];
if ($stateFl !== '') {
    $sql .= ' AND da.state = ?';
    $args[] = $stateFl;
}
if ($districtFl !== '') {
    $sql .= ' AND da.district = ?';
    $args[] = $districtFl;
}
if ($personnelFl) {
    $sql .= ' AND p.id = ?';
    $args[] = $personnelFl;
}
if ($rankFl !== '') {
    $sql .= ' AND p.rank_name = ?';
    $args[] = $rankFl;
}
if ($statusFl === 'upcoming') {
    $sql .= ' AND da.date_start > CURDATE()';
} elseif ($statusFl === 'ongoing') {
    $sql .= ' AND da.date_start <= CURDATE() AND (da.date_end IS NULL OR da.date_end >= CURDATE())';
} elseif ($statusFl === 'completed') {
    $sql .= ' AND da.date_end IS NOT NULL AND da.date_end < CURDATE()';
}
$sql .= ' ORDER BY da.date_start DESC, da.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$duties = $stmt->fetchAll();

$allPersonnel = $pdo->query('SELECT id, rank_name, name FROM personnel ORDER BY name ASC')->fetchAll();

// -----------------------------------------------------------------
// CSV export — honors the current search/personnel/rank/status filters
// -----------------------------------------------------------------
if (isset($_GET['export'])) {
    require_once 'exports/duty_export.php';
    exportDutyCsv($duties);
}

// Repopulate the form after a validation error
$old = [
    'personnel_id'   => $_POST['personnel_id']   ?? '',
    'state'          => $_POST['state']          ?? '',
    'district'       => $_POST['district']       ?? '',
    'location'       => $_POST['location']       ?? '',
    'duty_type'      => $_POST['duty_type']      ?? '',
    'date_start'     => $_POST['date_start']     ?? '',
    'date_end'       => $_POST['date_end']       ?? '',
    'still_ongoing'  => isset($_POST['still_ongoing']),
];

require 'views/duty_assignments_view.php';
