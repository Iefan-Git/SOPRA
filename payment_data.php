<?php
/**
 * includes/payment_data.php
 * SOPRA — small lookup helper shared by the payment ledger controller,
 * view, and CSV exports.
 */

/**
 * Look up one member's payment record for one month out of the
 * "id|month" => [...] map built by payment_ledger.php.
 * Returns a safe default (unpaid, no amount/date) if there's no row.
 */
function payInfo(array $map, int $id, int $month): array {
    return $map[$id . '|' . $month] ?? ['paid' => false, 'amount' => null, 'paid_date' => null];
}
