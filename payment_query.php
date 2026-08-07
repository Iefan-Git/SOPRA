<?php
/**
 * includes/payment_query.php
 * SOPRA — payment ledger query-string builder.
 *
 * Rebuilds the current filter set (year, search, ranks, month/status
 * combo) as a query string, with optional overrides. Used by every
 * link and hidden form field on payment_ledger.php so the current
 * view survives navigation, edits, and redirects.
 *
 * Expects $year, $search, $selRanks, $filterMonth, $filterStatus to
 * already be set as globals by the controller before it's called.
 */
function buildQuery(array $overrides = []) {
    global $year, $search, $selRanks, $filterMonth, $filterStatus;
    $params = [
        'year'    => $year,
        'search'  => $search,
        'ranks'   => implode(',', $selRanks),
        'fmonth'  => $filterMonth === null ? '' : $filterMonth,
        'fstatus' => $filterStatus,
    ];
    $params = array_merge($params, $overrides);
    // Drop empty values for a cleaner URL
    $params = array_filter($params, function ($v) {
        return $v !== '' && $v !== null;
    });
    return http_build_query($params);
}
