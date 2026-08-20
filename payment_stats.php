<?php
/**
 * includes/payment_stats.php
 * SOPRA — computes the summary numbers shown in the ledger's stat
 * cards (collection rate, fully-paid count, total RM collected, and
 * how many members have paid in the current calendar month).
 */
function computePaymentStats(array $personnelList, array $payMap, int $year): array {
    $totalCells = count($personnelList) * 12;
    $paidCells  = 0;
    $fullyPaid  = 0;
    $totalCollected = 0.0;
    foreach ($personnelList as $pp) {
        $count = 0;
        for ($m = 0; $m < 12; $m++) {
            $info = payInfo($payMap, $pp['id'], $m);
            if ($info['paid']) {
                $count++;
                $totalCollected += (float) ($info['amount'] ?? 0);
            }
        }
        $paidCells += $count;
        if ($count === 12) $fullyPaid++;
    }
    $rate = $totalCells ? round(($paidCells / $totalCells) * 100) : 0;

    $currentMonthIdx = ((int) date('Y')) === $year ? ((int) date('n') - 1) : null;
    $paidThisMonth = $currentMonthIdx !== null
        ? count(array_filter($personnelList, function ($pp) use ($payMap, $currentMonthIdx) {
            return payInfo($payMap, $pp['id'], $currentMonthIdx)['paid'];
        }))
        : null;

    return [
        'totalCells'      => $totalCells,
        'paidCells'       => $paidCells,
        'fullyPaid'       => $fullyPaid,
        'totalCollected'  => $totalCollected,
        'rate'            => $rate,
        'currentMonthIdx' => $currentMonthIdx,
        'paidThisMonth'   => $paidThisMonth,
    ];
}
