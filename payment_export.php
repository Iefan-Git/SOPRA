<?php
/**
 * exports/payment_export.php
 * SOPRA — the two CSV exports offered from the payment ledger.
 * Both stream straight to the browser and exit, same as the
 * original inline code in payment_ledger.php.
 */

/**
 * Full-year matrix export — every month, every member currently
 * matching the ledger's search/rank filters.
 */
function exportPaymentFullYear(array $personnelList, array $payMap, int $year): void {
    $exportStamp = date('Y-m-d');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SOPRA_Bayaran_' . $year . '_Eksport_' . $exportStamp . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [APP_NAME . ' - Payment Ledger ' . $year . ' - Exported on ' . $exportStamp]);
    fputcsv($out, []);
    $header = ['Rank', 'Name'];
    foreach (MONTHS as $m) { $header[] = $m . ' Payment Status'; $header[] = $m . ' Payment Date'; }
    fputcsv($out, $header);
    foreach ($personnelList as $pp) {
        $line = [$pp['rank_name'], $pp['name']];
        for ($m = 0; $m < 12; $m++) {
            $info = payInfo($payMap, $pp['id'], $m);
            $line[] = $info['paid'] ? 'PAID' : 'NOT PAID';
            $line[] = $info['paid'] ? ($info['paid_date'] ?? '') : '';
        }
        fputcsv($out, $line);
    }
    fclose($out);
    exit;
}

/**
 * Single-month export — who has paid THIS month (or the month
 * filter, if one is selected) and what day they paid.
 */
function exportPaymentMonth(array $personnelList, array $payMap, int $year, ?int $filterMonth): void {
    $exportStamp = date('Y-m-d');
    $mIdx = $filterMonth !== null ? $filterMonth : (int) date('n') - 1;
    $exportMonthName = MONTHS_FULL[$mIdx];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SOPRA_Bayaran_' . $exportMonthName . '_' . $year . '_Eksport_' . $exportStamp . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [APP_NAME . ' - Payment Status ' . $exportMonthName . ' ' . $year . ' - Exported on ' . $exportStamp]);
    fputcsv($out, []);
    fputcsv($out, ['Rank', 'Name', 'Payment Status', 'Payment Date']);
    foreach ($personnelList as $pp) {
        $info = payInfo($payMap, $pp['id'], $mIdx);
        fputcsv($out, [
            $pp['rank_name'],
            $pp['name'],
            $info['paid'] ? 'PAID' : 'NOT PAID',
            $info['paid'] ? fmtDate($info['paid_date']) : '',
        ]);
    }
    fclose($out);
    exit;
}
