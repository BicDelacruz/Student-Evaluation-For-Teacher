<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_GET['action']) && $_GET['action'] === 'back') {
    reset_admin_session(false);
    redirect(ADMIN_LOGIN_REDIRECT);
}

require_admin_pending();

$error = get_flash('auth_error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = preg_replace('/\D+/', '', (string) ($_POST['pin'] ?? ''));

    if ($pin === '99999') {
        $_SESSION['admin_verified'] = true;
        unset($_SESSION['admin_pending']);
        redirect('dashboard.php');
    }

    set_flash('auth_error', 'Incorrect PIN. Please enter the valid 5-digit admin code.');
    redirect('authentication.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <img src="../img/logo.png" alt="School logo" class="auth-logo">
        <div class="auth-shield">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l6 2.25v5.34C18 14.42 15.44 17.98 12 19 8.56 17.98 6 14.42 6 10.59V5.25L12 3z"></path></svg>
        </div>
        <h1>Admin Authentication</h1>
        <p>Scan the QR Code to verify your identity</p>

        <?php if ($error !== null): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="qr-box">
            <div class="qr-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 4h6v6H4z"></path>
                    <path d="M14 4h6v6h-6z"></path>
                    <path d="M4 14h6v6H4z"></path>
                    <path d="M12 7h.01"></path>
                    <path d="M12 10v2a2 2 0 0 1-2 2H8"></path>
                    <path d="M12 12a2 2 0 0 0 2 2h2"></path>
                    <path d="M14 20v-3a2 2 0 0 1 2-2h2"></path>
                    <path d="M12 17v3"></path>
                    <path d="M18 12h2"></path>
                    <path d="M20 18h.01"></path>
                    <path d="M16 12h.01"></path>
                    <path d="M4 12h.01"></path>
                </svg>
            </div>
            <div class="qr-caption">Scan this Unique QR Code to verify and register your device</div>
        </section>

        <form method="post" class="auth-form" novalidate>
            <div class="pin-label">Enter 5-Digit PIN</div>
            <div class="pin-inputs" role="group" aria-label="5 digit verification PIN">
                <input type="text" maxlength="1" inputmode="numeric" class="pin-input" aria-label="PIN digit 1">
                <input type="text" maxlength="1" inputmode="numeric" class="pin-input" aria-label="PIN digit 2">
                <input type="text" maxlength="1" inputmode="numeric" class="pin-input" aria-label="PIN digit 3">
                <input type="text" maxlength="1" inputmode="numeric" class="pin-input" aria-label="PIN digit 4">
                <input type="text" maxlength="1" inputmode="numeric" class="pin-input" aria-label="PIN digit 5">
            </div>
            <input type="hidden" name="pin" data-pin-target>

            <button type="submit" class="primary-button">Verify Identity</button>
            <a class="secondary-button" href="authentication.php?action=back">
                <span class="button-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6l-6 6 6 6"></path><path d="M4 12h16"></path></svg>
                </span>
                Back to Login
            </a>
        </form>

        <div class="auth-demo">Demo PIN: <strong>99999</strong></div>
    </main>
    <script src="assets/admin.js" defer></script>
</body>
</html>
