<?php
/**
 * includes/duty_helpers.php
 * SOPRA — helpers for turning a duty assignment's date range into the
 * status/duration text shown in the UI and CSV export. Kept separate
 * from format_helpers.php since these are specific to the duty module.
 */

/**
 * Duty status derived from date_start/date_end — never stored, always
 * computed against today's date:
 *   Upcoming  — date_start is in the future
 *   Ongoing   — date_start has passed and (date_end is NULL, i.e. return
 *               date not yet known, OR date_end is today/in the future)
 *   Completed — date_end has passed
 */
function dutyStatus(string $dateStart, ?string $dateEnd): string {
    $today = date('Y-m-d');
    if ($dateStart > $today) return 'Upcoming';
    if ($dateEnd === null || $dateEnd >= $today) return 'Ongoing';
    return 'Completed';
}

/** Human-readable duration for a duty assignment, e.g. "3 days" / "Ongoing" */
function fmtDuration(string $dateStart, ?string $dateEnd): string {
    if ($dateEnd === null) return 'Ongoing';
    $start = new DateTime($dateStart);
    $end   = new DateTime($dateEnd);
    if ($end < $start) return '—';
    $days = $start->diff($end)->days + 1; // inclusive of both departure and return day
    return $days . ' ' . ($days === 1 ? 'day' : 'days');
}
