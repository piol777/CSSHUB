<?php
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, send straight to their dashboard
if (isset($_SESSION['user_id'])) {
    redirect('/cdsgahub/' . $_SESSION['role'] . '/dashboard.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <button class="auth-theme-toggle" id="authThemeToggle" title="Toggle theme">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
        </svg>
    </button>
    <div class="auth-card">
        <h1>CDSGA HUB</h1>
        <p class="verify-subtext">Enter the Gmail you used to register. We'll send you a code to reset your password.</p>

        <?php if ($flash): ?>
            <div class="form-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form action="process/forgot_password_process.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
            </div>

            <button type="submit" class="btn-primary">Send Reset Code</button>
        </form>

        <hr class="auth-divider">
        <div class="auth-link">
            Back to <a href="login.php">Login</a>
        </div>
    </div>
<script src="../assets/js/auth-theme.js"></script>
</body>
</html>