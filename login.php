<?php
require_once 'config.php';

// Already logged in? Skip straight to the right dashboard.
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'payment_ledger.php' : 'personnel_overview.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in your username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session id on login to prevent session fixation.
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['role']         = $user['role'];
            $_SESSION['personnel_id'] = $user['personnel_id'];

            header('Location: ' . ($user['role'] === 'admin' ? 'payment_ledger.php' : 'personnel_overview.php'));
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In &mdash; <?= APP_NAME ?></title>
<link rel="icon" href="assets/logo.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0b2a4a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="SOPRA">
<link rel="apple-touch-icon" href="assets/icon-192.png">
<link rel="apple-touch-icon" sizes="512x512" href="assets/icon-512.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css?v=3">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="badge"><img src="assets/logo.png" alt="Logo PTK N.C.I.D"></div>
    <h1><?= APP_NAME ?></h1>
    <div class="sub"><?= e(APP_FULL_NAME) ?></div>

    <form method="post" action="login.php">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>" />
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required />
      </div>
      <button type="submit" class="btn btn-primary">Log In</button>
      <?php if ($error): ?><div class="login-error"><?= e($error) ?></div><?php endif; ?>
    </form>

    <button type="button" id="pwaInstallBtn" class="btn btn-gold" style="display:none;">Install App</button>

    <div class="login-hint">
      Accounts are only created by an <b>Admin</b>. There is no self-registration &mdash;
      contact your unit's Admin to get a username &amp; password.
    </div>
  </div>
</div>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('service-worker.js').catch(function () {});
  }

  (function () {
    var deferredPrompt = null;
    var btn = document.getElementById('pwaInstallBtn');

    window.addEventListener('beforeinstallprompt', function (e) {
      e.preventDefault();
      deferredPrompt = e;
      if (btn) btn.style.display = '';
    });

    if (btn) {
      btn.addEventListener('click', function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.finally(function () {
          deferredPrompt = null;
          btn.style.display = 'none';
        });
      });
    }

    window.addEventListener('appinstalled', function () {
      if (btn) btn.style.display = 'none';
      deferredPrompt = null;
    });

    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (isIOS && !isStandalone && btn) {
      btn.textContent = 'Add to Home Screen';
      btn.style.display = '';
      btn.addEventListener('click', function () {
        alert('To install SOPRA on iPhone/iPad:\n\n1. Tap the Share icon in Safari\n2. Tap "Add to Home Screen"');
      });
    }
  })();
</script>
</body>
</html>
