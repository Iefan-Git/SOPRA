<?php
/**
 * includes/format_helpers.php
 * SOPRA — small, page-agnostic formatting/output helpers shared by
 * every view and CSV export.
 */

/** Shorthand output-escaping helper */
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Format a nullable RM amount for display */
function fmtRM(?float $amount): string {
    return $amount === null ? '—' : 'RM ' . number_format($amount, 2);
}

/** Format a nullable date (Y-m-d) into "05 May 2026" style */
function fmtDate(?string $ymd): string {
    if (!$ymd) return '—';
    $ts = strtotime($ymd);
    if (!$ts) return '—';
    $d = (int) date('j', $ts);
    $m = MONTHS_FULL[(int) date('n', $ts) - 1];
    $y = date('Y', $ts);
    return "$d $m $y";
}

/** Format a nullable date (Y-m-d) compactly as "07/08/2026" for table cells */
function fmtDateShort(?string $ymd): string {
    if (!$ymd) return '';
    $ts = strtotime($ymd);
    if (!$ts) return '';
    return date('d/m/Y', $ts);
}
