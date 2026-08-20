<?php
/**
 * includes/auth.php
 * SOPRA — session/login state and page-guard helpers.
 */

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/** Bounce anonymous visitors to login.php */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/** Only allow admins; everyone else gets sent to their own dashboard */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: personnel_overview.php');
        exit;
    }
}

/** Only allow regular users; admins get sent to the admin dashboard */
function requireUser(): void {
    requireLogin();
    if (isAdmin()) {
        header('Location: payment_ledger.php');
        exit;
    }
}
