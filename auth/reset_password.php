<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['pending_reset_user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['pending_reset_user_id'];
$stmt = $pdo->prepare("SELECT email, status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    unset($_SESSION['pending_reset_user_id']);
    redirect('login.php');
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CDSGA HUB</title>
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
        <p class="verify-subtext">code sent to "<?= sanitize($user['email']) ?>" please check</p>

        <?php if ($flash): ?>
            <div class="form-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form id="resetPasswordForm" action="process/reset_password_process.php" method="POST">
            <div class="digit-inputs">
                <input type="text" class="digit-box" maxlength="1" inputmode="numeric">
                <input type="text" class="digit-box" maxlength="1" inputmode="numeric">
                <input type="text" class="digit-box" maxlength="1" inputmode="numeric">
                <input type="text" class="digit-box" maxlength="1" inputmode="numeric">
                <input type="hidden" name="code" id="codeCombined">
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
            </div>

            <button type="submit" class="btn-primary">Reset Password</button>
        </form>

        <hr class="auth-divider">
        <div class="auth-link">
            Back to <a href="login.php">Login</a>
        </div>
    </div>

    <script src="../assets/js/reset_password.js"></script>
    <script src="../assets/js/auth-theme.js"></script>
</body>
</html>