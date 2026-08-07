<?php
/**
 * actions/payment_actions.php
 * SOPRA — handles every POST action submitted from payment_ledger.php:
 *   toggle_payment   mark a month paid (asks amount) / reopen it
 *   add_personnel    add a new member to the ledger
 *   edit_personnel   rename / re-rank a member
 *   delete_personnel remove a member (cascades payments & duty records)
 *   add_user         create a login (admin or user, optionally linked
 *                    to a personnel record) — the only way accounts
 *                    are created, since there is no self-registration
 *   delete_user      remove a login (never your own)
 *
 * Requires includes/payment_query.php (buildQuery()) to already be
 * loaded. Redirects back to the ledger with the current filters
 * preserved and exits, same as the original inline handler.
 */
/**
 * Build a URL-safe, easy-to-read username from a Name / Call Sign,
 * then make it unique by appending a number if it's already taken.
 */
function generateUsernameFromName(PDO $pdo, string $name): string {
    $base = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($name)));
    $base = trim($base, '_');
    if ($base === '') { $base = 'member'; }

    $username = $base;
    $suffix = 1;
    while (true) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $check->execute([$username]);
        if (!$check->fetch()) { return $username; }
        $suffix++;
        $username = $base . $suffix;
    }
}

/**
 * Generate a short, easy-to-read random password (avoids visually
 * ambiguous characters like 0/O and 1/l/I).
 */
function generateRandomPassword(int $length = 8): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

function handlePaymentActions(PDO $pdo): void {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_payment') {
        $pid = (int) ($_POST['personnel_id'] ?? 0);
        $mth = (int) ($_POST['month'] ?? -1);
        $yr  = (int) ($_POST['year'] ?? 0);

        if ($pid > 0 && $mth >= 0 && $mth <= 11 && $yr > 0) {
            $stmt = $pdo->prepare('SELECT paid FROM payments WHERE personnel_id = ? AND year = ? AND month = ?');
            $stmt->execute([$pid, $yr, $mth]);
            $row = $stmt->fetch();
            $nowPaid = !($row && $row['paid']);

            if ($nowPaid) {
                // Marking as paid: capture the amount the member chose to pay
                // this month, and record today as the payment date.
                $amountRaw = $_POST['amount'] ?? '';
                $amount = is_numeric($amountRaw) && (float) $amountRaw >= 0 ? round((float) $amountRaw, 2) : null;
                if ($row) {
                    $upd = $pdo->prepare('UPDATE payments SET paid = 1, amount = ?, paid_date = CURDATE() WHERE personnel_id = ? AND year = ? AND month = ?');
                    $upd->execute([$amount, $pid, $yr, $mth]);
                } else {
                    $ins = $pdo->prepare('INSERT INTO payments (personnel_id, year, month, paid, amount, paid_date) VALUES (?, ?, ?, 1, ?, CURDATE())');
                    $ins->execute([$pid, $yr, $mth, $amount]);
                }
            } else {
                // Reverting to unpaid clears the recorded amount/date.
                $upd = $pdo->prepare('UPDATE payments SET paid = 0, amount = NULL, paid_date = NULL WHERE personnel_id = ? AND year = ? AND month = ?');
                $upd->execute([$pid, $yr, $mth]);
            }
        }

    } elseif ($action === 'add_personnel') {
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $rank = $_POST['rank'] ?? RANKS[0];
        if ($name !== '' && in_array($rank, RANKS, true)) {
            $ins = $pdo->prepare('INSERT INTO personnel (rank_name, name) VALUES (?, ?)');
            $ins->execute([$rank, $name]);
        }

    } elseif ($action === 'edit_personnel') {
        $id   = (int) ($_POST['id'] ?? 0);
        $name = mb_strtoupper(trim($_POST['name'] ?? ''));
        $rank = $_POST['rank'] ?? RANKS[0];
        if ($id > 0 && $name !== '' && in_array($rank, RANKS, true)) {
            $upd = $pdo->prepare('UPDATE personnel SET name = ?, rank_name = ? WHERE id = ?');
            $upd->execute([$name, $rank, $id]);
        }

    } elseif ($action === 'delete_personnel') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare('DELETE FROM personnel WHERE id = ?');
            $del->execute([$id]); // payments + duty records cascade-delete, linked user's personnel_id is set NULL
        }

    } elseif ($action === 'add_user') {
        $urole = $_POST['role'] ?? 'user';
        $genUsername = null;
        $genPassword = null;

        if ($urole === 'admin') {
            // Admins are created the traditional way: admin chooses the
            // username & password directly (no member/rank — admins
            // aren't linked to a personnel record).
            $uname = trim($_POST['username'] ?? '');
            $upass = $_POST['password'] ?? '';
            if ($uname !== '' && $upass !== '') {
                $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
                $check->execute([$uname]);
                if (!$check->fetch()) {
                    $hash = password_hash($upass, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, NULL)');
                    $ins->execute([$uname, $hash, 'admin']);
                }
            }
        } else {
            // Regular members: admin only enters Name/Call Sign + Rank.
            // Username & password are generated automatically, shown once
            // after creation so the admin can hand them to the member.
            $mname = mb_strtoupper(trim($_POST['member_name'] ?? ''));
            $mrank = $_POST['member_rank'] ?? RANKS[0];

            if ($mname !== '' && in_array($mrank, RANKS, true)) {
                // Reuse an existing personnel record with this exact name,
                // otherwise create it.
                $find = $pdo->prepare('SELECT id FROM personnel WHERE name = ?');
                $find->execute([$mname]);
                $existing = $find->fetch();
                if ($existing) {
                    $upid = (int) $existing['id'];
                } else {
                    $ins2 = $pdo->prepare('INSERT INTO personnel (rank_name, name) VALUES (?, ?)');
                    $ins2->execute([$mrank, $mname]);
                    $upid = (int) $pdo->lastInsertId();
                }

                $genUsername = generateUsernameFromName($pdo, $mname);
                $genPassword = generateRandomPassword();
                $hash = password_hash($genPassword, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (username, password, role, personnel_id) VALUES (?, ?, ?, ?)');
                $ins->execute([$genUsername, $hash, 'user', $upid]);
            }
        }

        header('Location: payment_ledger.php?' . buildQuery([
            'modal'        => 'manageUsers',
            'new_username' => $genUsername,
            'new_password' => $genPassword,
        ]));
        exit;

    } elseif ($action === 'delete_user') {
        $uid = (int) ($_POST['id'] ?? 0);
        if ($uid > 0 && $uid !== (int) $_SESSION['user_id']) { // can't delete yourself
            $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $del->execute([$uid]);
        }
    }

    // A few forms (like "Add Member" inside Manage Users) want the user
    // sent back to the same modal instead of the bare ledger view.
    $backToModal = trim($_POST['modal'] ?? '');
    header('Location: payment_ledger.php?' . buildQuery($backToModal !== '' ? ['modal' => $backToModal] : []));
    exit;
}
