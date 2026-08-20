<?php
/**
 * actions/duty_actions.php
 * SOPRA — handles POST actions submitted from duty_assignments.php:
 *   add_duty     validate + record a new duty/operation assignment
 *   delete_duty  remove a duty record
 *
 * add_duty is the one action that can fail validation — in that case
 * this returns an error message and the controller keeps rendering
 * the page (with the form repopulated) instead of redirecting.
 * Every other outcome (success, or delete_duty) redirects and exits
 * on its own, same as the original inline handler.
 */
function handleDutyActions(PDO $pdo): string {
    $action = $_POST['action'] ?? '';
    $error  = '';

    if ($action === 'add_duty') {
        $personnelId = (int) ($_POST['personnel_id'] ?? 0);
        $state       = trim($_POST['state'] ?? '');
        $district    = trim($_POST['district'] ?? '');
        $location    = trim($_POST['location'] ?? '');
        $dutyType    = $_POST['duty_type'] ?? '';
        $dateStart   = trim($_POST['date_start'] ?? '');
        $stillOngoing = isset($_POST['still_ongoing']);
        $dateEnd     = $stillOngoing ? '' : trim($_POST['date_end'] ?? '');

        $startDt = DateTime::createFromFormat('Y-m-d', $dateStart);
        $endDt   = $dateEnd !== '' ? DateTime::createFromFormat('Y-m-d', $dateEnd) : null;

        if ($personnelId <= 0) {
            $error = 'Please select a member.';
        } elseif ($state === '' || !isset(STATES_DISTRICTS[$state])) {
            $error = 'Please select a valid state.';
        } elseif ($district === '' || !in_array($district, STATES_DISTRICTS[$state], true)) {
            $error = 'Please select a valid district for the chosen state.';
        } elseif (!array_key_exists($dutyType, DUTY_TYPES)) {
            $error = 'Please select a duty type.';
        } elseif (!$startDt) {
            $error = 'Please choose a valid departure date.';
        } elseif ($dateEnd !== '' && !$endDt) {
            $error = 'Please choose a valid return date.';
        } elseif ($endDt && $endDt < $startDt) {
            $error = 'Return date cannot be earlier than the departure date.';
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO duty_assignments
                    (personnel_id, state, district, location, duty_type, date_start, date_end, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $personnelId,
                $state,
                $district,
                $location !== '' ? $location : null,
                $dutyType,
                $startDt->format('Y-m-d'),
                $endDt ? $endDt->format('Y-m-d') : null,
                (int) $_SESSION['user_id'],
            ]);
            header('Location: duty_assignments.php?added=1');
            exit;
        }

    } elseif ($action === 'delete_duty') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM duty_assignments WHERE id = ?');
            $del->execute([$id]);
        }
        header('Location: duty_assignments.php');
        exit;
    }

    return $error;
}
