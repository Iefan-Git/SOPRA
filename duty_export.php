<?php
/**
 * exports/duty_export.php
 * SOPRA — PDF export for the duty assignments list, watermarked with
 * the login logo. Honors whatever search/personnel/rank/status filters
 * are currently applied.
 */
require_once __DIR__ . '/../includes/pdf_builder.php';
require_once __DIR__ . '/../includes/pdf_watermark.php';

function exportDutyPdf(array $duties): void {
    $exportStamp = date('Y-m-d');

    // Landscape A4 — 10 columns needs the extra width.
    $pageW = 841.89;
    $pageH = 595.28;
    $marginX = 30;
    $topY = $pageH - 40;
    $bottomY = 40;
    $rowH = 16;

    $cols = [
        ['label' => 'Rank',       'w' => 55],
        ['label' => 'Name',       'w' => 95],
        ['label' => 'State',      'w' => 70],
        ['label' => 'District',   'w' => 85],
        ['label' => 'Location',   'w' => 105],
        ['label' => 'Duty Type',  'w' => 95],
        ['label' => 'Start',      'w' => 62],
        ['label' => 'End',        'w' => 62],
        ['label' => 'Duration',   'w' => 58],
        ['label' => 'Status',     'w' => 62],
    ];

    $pdf = new MiniPdf();
    $wm = buildPdfWatermark($pageW, $pageH);
    if ($wm) { $pdf->setWatermark($wm['data'], $wm['w'], $wm['h']); }

    $colX = [];
    $drawHeader = function () use ($pdf, $pageW, $pageH, $marginX, $topY, $exportStamp, $cols, &$colX): float {
        $pdf->addPage($pageW, $pageH);
        $pdf->text($marginX, $pageH - 22, APP_NAME . ' - Duty & Operation Location', 13, true);
        $pdf->text($marginX, $pageH - 34, 'Exported on ' . $exportStamp, 8, false, [0.4, 0.4, 0.4]);

        $y = $topY;
        $x = $marginX;
        $colX = [];
        $pdf->rectFill($marginX, $y - 12, array_sum(array_column($cols, 'w')), 16, 0.88);
        foreach ($cols as $c) {
            $colX[] = $x;
            $pdf->text($x + 3, $y - 9, $c['label'], 8, true);
            $x += $c['w'];
        }
        $pdf->line($marginX, $y - 13, $x, $y - 13, 0.7);
        return $y - 16;
    };

    $y = $drawHeader();

    if (empty($duties)) {
        $pdf->text($marginX, $y - 12, 'No duty assignments match the current filters.', 9, false, [0.4, 0.4, 0.4]);
    }

    foreach ($duties as $d) {
        if ($y - $rowH < $bottomY) {
            $y = $drawHeader();
        }
        $status = dutyStatus($d['date_start'], $d['date_end']);
        $cells = [
            $d['rank_name'],
            $d['name'],
            $d['state'],
            $d['district'],
            $d['location'] ?? '',
            DUTY_TYPES[$d['duty_type']] ?? $d['duty_type'],
            fmtDateShort($d['date_start']),
            $d['date_end'] ? fmtDateShort($d['date_end']) : '-',
            fmtDuration($d['date_start'], $d['date_end']),
            $status,
        ];
        foreach ($cells as $i => $val) {
            $color = null;
            if ($cols[$i]['label'] === 'Status') {
                $color = $status === 'Ongoing' ? [0.13, 0.55, 0.13] : ($status === 'Completed' ? [0.45, 0.45, 0.45] : [0.75, 0.35, 0]);
            }
            $pdf->text($colX[$i] + 3, $y - 11, (string) $val, 8, false, $color);
        }
        $pdf->line($marginX, $y - $rowH, $marginX + array_sum(array_column($cols, 'w')), $y - $rowH, 0.3, 0.85);
        $y -= $rowH;
    }

    $pdf->output('SOPRA_Duty_Assignments_Export_' . $exportStamp . '.pdf');
}
