<?php
/**
 * includes/duty_query.php
 * SOPRA — duty assignments query-string builder.
 *
 * Rebuilds the current filter set (state, district, member, rank,
 * status) as a query string, with optional overrides — used by the
 * CSV export link and the "Reset Filters" link on duty_assignments.php.
 *
 * Expects $stateFl, $districtFl, $personnelFl, $rankFl, $statusFl to
 * already be set as globals by the controller before it's called.
 */
function buildDutyQuery(array $overrides = []) {
    global $stateFl, $districtFl, $personnelFl, $rankFl, $statusFl;
    $params = [
        'state'        => $stateFl,
        'district'     => $districtFl,
        'personnel_id' => $personnelFl ?: '',
        'rank'         => $rankFl,
        'status'       => $statusFl,
    ];
    $params = array_merge($params, $overrides);
    $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
    return http_build_query($params);
}
