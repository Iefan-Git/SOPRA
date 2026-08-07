<?php
/**
 * exports/duty_export.php
 * SOPRA — CSV export for the duty assignments list. Honors whatever
 * search/personnel/rank/status filters are currently applied.
 */
function exportDutyCsv(array $duties): void {
    $exportStamp = date('Y-m-d');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SOPRA_Duty_Assignments_Export_' . $exportStamp . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [APP_NAME . ' - Duty & Operation Location - Exported on ' . $exportStamp]);
    fputcsv($out, []);
    fputcsv($out, ['Rank', 'Name', 'State', 'District', 'Location', 'Duty Type', 'Date Start', 'Date End', 'Duration', 'Status']);
    foreach ($duties as $d) {
        fputcsv($out, [
            $d['rank_name'],
            $d['name'],
            $d['state'],
            $d['district'],
            $d['location'] ?? '',
            DUTY_TYPES[$d['duty_type']] ?? $d['duty_type'],
            fmtDate($d['date_start']),
            $d['date_end'] ? fmtDate($d['date_end']) : '',
            fmtDuration($d['date_start'], $d['date_end']),
            dutyStatus($d['date_start'], $d['date_end']),
        ]);
    }
    fclose($out);
    exit;
}
