<?php
/**
 * exports/payment_export.php
 * SOPRA — the two PDF exports offered from the payment ledger, both
 * watermarked with the login logo.
 */
require_once __DIR__ . '/../includes/pdf_builder.php';
require_once __DIR__ . '/../includes/pdf_watermark.php';

/**
 * Full-year matrix export — every month, every member currently
 * matching the ledger's search/rank filters. Each month is one
 * compact two-line cell (status + paid date) so the whole year still
 * fits on a landscape page.
 */
function exportPaymentFullYearPdf(array $personnelList, array $payMap, int $year): void {
    $exportStamp = date('Y-m-d');

    $pageW = 841.89; // landscape A4
    $pageH = 595.28;
    $marginX = 26;
    $topY = $pageH - 40;
    $bottomY = 40;
    $rowH = 24;

    $rankW = 45;
    $nameW = 95;
    $monthW = (($pageW - 2 * $marginX) - $rankW - $nameW) / 12;

    $pdf = new MiniPdf();
    $wm = buildPdfWatermark($pageW, $pageH);
    if ($wm) { $pdf->setWatermark($wm['data'], $wm['w'], $wm['h']); }

    $tableW = $rankW + $nameW + $monthW * 12;

    $drawHeader = function () use ($pdf, $pageW, $pageH, $marginX, $topY, $exportStamp, $year, $rankW, $nameW, $monthW, $tableW): float {
        $pdf->addPage($pageW, $pageH);
        $pdf->text($marginX, $pageH - 22, APP_NAME . ' - Payment Ledger ' . $year, 13, true);
        $pdf->text($marginX, $pageH - 34, 'Exported on ' . $exportStamp, 8, false, [0.4, 0.4, 0.4]);

        $y = $topY;
        $pdf->rectFill($marginX, $y - 12, $tableW, 16, 0.88);
        $x = $marginX;
        $pdf->text($x + 3, $y - 9, 'Rank', 8, true);
        $x += $rankW;
        $pdf->text($x + 3, $y - 9, 'Name', 8, true);
        $x += $nameW;
        foreach (MONTHS as $m) {
            $pdf->text($x + 3, $y - 9, $m, 8, true);
            $x += $monthW;
        }
        $pdf->line($marginX, $y - 13, $marginX + $tableW, $y - 13, 0.7);
        return $y - 16;
    };

    $y = $drawHeader();

    if (empty($personnelList)) {
        $pdf->text($marginX, $y - 12, 'No members match the current filters.', 9, false, [0.4, 0.4, 0.4]);
    }

    foreach ($personnelList as $pp) {
        if ($y - $rowH < $bottomY) {
            $y = $drawHeader();
        }
        $x = $marginX;
        $pdf->text($x + 3, $y - 10, $pp['rank_name'], 7.5);
        $x += $rankW;
        $pdf->text($x + 3, $y - 10, $pp['name'], 7.5);
        $x += $nameW;
        for ($m = 0; $m < 12; $m++) {
            $info = payInfo($payMap, $pp['id'], $m);
            $statusColor = $info['paid'] ? [0.13, 0.55, 0.13] : [0.75, 0.2, 0.2];
            $pdf->text($x + 3, $y - 10, $info['paid'] ? 'PAID' : 'NOT PAID', 7, false, $statusColor);
            if ($info['paid']) {
                $pdf->text($x + 3, $y - 20, fmtDateShort($info['paid_date'] ?? null), 6.5, false, [0.45, 0.45, 0.45]);
            }
            $x += $monthW;
        }
        $pdf->line($marginX, $y - $rowH, $marginX + $tableW, $y - $rowH, 0.3, 0.85);
        $y -= $rowH;
    }

    $pdf->output('SOPRA_Bayaran_' . $year . '_Eksport_' . $exportStamp . '.pdf');
}

/**
 * Single-month export — who has paid THIS month (or the month filter,
 * if one is selected) and what day they paid.
 */
function exportPaymentMonthPdf(array $personnelList, array $payMap, int $year, ?int $filterMonth): void {
    $exportStamp = date('Y-m-d');
    $mIdx = $filterMonth !== null ? $filterMonth : (int) date('n') - 1;
    $exportMonthName = MONTHS_FULL[$mIdx];

    $pageW = 595.28; // portrait A4
    $pageH = 841.89;
    $marginX = 40;
    $topY = $pageH - 60;
    $bottomY = 50;
    $rowH = 18;

    $cols = [
        ['label' => 'Rank',            'w' => 80],
        ['label' => 'Name',            'w' => 190],
        ['label' => 'Payment Status',  'w' => 110],
        ['label' => 'Payment Date',    'w' => 110],
    ];
    $tableW = array_sum(array_column($cols, 'w'));

    $pdf = new MiniPdf();
    $wm = buildPdfWatermark($pageW, $pageH);
    if ($wm) { $pdf->setWatermark($wm['data'], $wm['w'], $wm['h']); }

    $colX = [];
    $drawHeader = function () use ($pdf, $pageW, $pageH, $marginX, $topY, $exportStamp, $exportMonthName, $year, $cols, $tableW, &$colX): float {
        $pdf->addPage($pageW, $pageH);
        $pdf->text($marginX, $pageH - 30, APP_NAME . ' - Payment Status ' . $exportMonthName . ' ' . $year, 13, true);
        $pdf->text($marginX, $pageH - 44, 'Exported on ' . $exportStamp, 8, false, [0.4, 0.4, 0.4]);

        $y = $topY;
        $x = $marginX;
        $colX = [];
        $pdf->rectFill($marginX, $y - 12, $tableW, 16, 0.88);
        foreach ($cols as $c) {
            $colX[] = $x;
            $pdf->text($x + 4, $y - 9, $c['label'], 8, true);
            $x += $c['w'];
        }
        $pdf->line($marginX, $y - 13, $marginX + $tableW, $y - 13, 0.7);
        return $y - 16;
    };

    $y = $drawHeader();

    if (empty($personnelList)) {
        $pdf->text($marginX, $y - 12, 'No members match the current filters.', 9, false, [0.4, 0.4, 0.4]);
    }

    foreach ($personnelList as $pp) {
        if ($y - $rowH < $bottomY) {
            $y = $drawHeader();
        }
        $info = payInfo($payMap, $pp['id'], $mIdx);
        $statusColor = $info['paid'] ? [0.13, 0.55, 0.13] : [0.75, 0.2, 0.2];
        $cells = [
            $pp['rank_name'],
            $pp['name'],
            $info['paid'] ? 'PAID' : 'NOT PAID',
            $info['paid'] ? fmtDateShort($info['paid_date']) : '-',
        ];
        foreach ($cells as $i => $val) {
            $color = $cols[$i]['label'] === 'Payment Status' ? $statusColor : null;
            $pdf->text($colX[$i] + 4, $y - 11, (string) $val, 8.5, false, $color);
        }
        $pdf->line($marginX, $y - $rowH, $marginX + $tableW, $y - $rowH, 0.3, 0.85);
        $y -= $rowH;
    }

    $pdf->output('SOPRA_Bayaran_' . $exportMonthName . '_' . $year . '_Eksport_' . $exportStamp . '.pdf');
}
