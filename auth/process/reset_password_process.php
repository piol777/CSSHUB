<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['pending_reset_user_id'])) {
    redirect('../login.php');
}

$user_id = $_SESSION['pending_reset_user_id'];
$code = sanitize($_POST['code'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (strlen($code) !== 4) {
    set_flash('error', 'Please enter the full 4-digit code.');
    redirect('../reset_password.php');
}

if (strlen($new_password) < 8) {
    set_flash('error', 'Password must be at least 8 characters.');
    redirect('../reset_password.php');
}

if ($new_password !== $confirm_password) {
    set_flash('error', 'Password and Confirm Password do not match.');
    redirect('../reset_password.php');
}

$stmt = $pdo->prepare("
    SELECT id, expires_at, used_at
    FROM password_resets
    WHERE user_id = ? AND code = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user_id, $code]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'Invalid reset code.');
    redirect('../reset_password.php');
}

if ($record['used_at'] !== null) {
    set_flash('error', 'This code was already used.');
    redirect('../reset_password.php');
}

if (strtotime($record['expires_at']) < time()) {
    set_flash('error', 'This code has expired. Please request a new one.');
    redirect('../forgot_password.php');
}

$pdo->beginTransaction();
try {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$password_hash, $user_id]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$record['id']]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to reset password. Please try again.');
    redirect('../reset_password.php');
}

unset($_SESSION['pending_reset_user_id']);
set_flash('success', 'Password reset successful! You can now log in with your new password.');
redirect('../login.php');