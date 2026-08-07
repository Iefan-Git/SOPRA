<?php
/**
 * config.php
 * SOPRA — System for Operational Personnel Resource Allocation
 * Bootstrap entry point. Every page still starts with:
 *   require_once 'config.php';
 *
 * This file itself does almost nothing — it just starts the session
 * and pulls in the pieces the old, single-file config.php used to
 * hold all at once:
 *
 *   config/database.php    DB connection ($pdo) + first-run admin seed
 *   config/constants.php    APP_NAME, RANKS, MONTHS, DUTY_TYPES, STATES_DISTRICTS
 *   includes/auth.php       isLoggedIn(), requireAdmin(), etc.
 *   includes/format_helpers.php  e(), fmtRM(), fmtDate()
 *   includes/duty_helpers.php    dutyStatus(), fmtDuration()
 */

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/format_helpers.php';
require_once __DIR__ . '/includes/duty_helpers.php';
