<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'change_password';
$flash = get_flash();
$hasPendingCode = isset($_SESSION['pending_password_hash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="change-password-container">
        <div class="change-password-card">
            <h1>Change Password</h1>

            <?php if ($flash): ?>
                <div class="change-password-flash <?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
            <?php endif; ?>

            <?php if (!$hasPendingCode): ?>
                <p class="change-password-hint">Enter your current and new password. A verification code will be sent to your email before the change is applied.</p>
                <form action="../api/request_password_change.php" method="POST">
                    <div class="modal-form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="modal-form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="8" required>
                    </div>
                    <div class="modal-form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="8" required>
                    </div>
                    <button type="submit" class="modal-submit-btn">Send Verification Code</button>
                </form>
            <?php else: ?>
                <p class="change-password-hint">We sent a 4-digit verification code to your email. Enter it below to confirm the password change.</p>
                <form action="../api/confirm_password_change.php" method="POST">
                    <div class="modal-form-group">
                        <label>Verification Code</label>
                        <input type="text" name="code" maxlength="4" required autocomplete="off">
                    </div>
                    <button type="submit" class="modal-submit-btn">Confirm Change</button>
                </form>
                <form action="../api/cancel_password_change.php" method="POST" style="margin-top:10px;">
                    <button type="submit" class="modal-cancel-btn">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>