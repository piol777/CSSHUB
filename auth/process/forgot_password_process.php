<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../forgot_password.php');
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect('../forgot_password.php');
}

$stmt = $pdo->prepare("SELECT id, first_name, status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'No account found with that email.');
    redirect('../forgot_password.php');
}

if ($user['status'] === 'pending') {
    $_SESSION['pending_verification_user_id'] = $user['id'];
    set_flash('error', 'Please verify your email first before resetting your password.');
    redirect('../verify.php');
}

if ($user['status'] === 'disabled') {
    set_flash('error', 'This account has been disabled. Contact the administrator.');
    redirect('../forgot_password.php');
}

$code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $pdo->prepare("
    INSERT INTO password_resets (user_id, code, expires_at)
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $code, $expires_at]);

$emailSent = sendPasswordResetEmail($email, $user['first_name'], $code);

if (!$emailSent) {
    set_flash('error', 'Failed to send the reset email. Please try again.');
    redirect('../forgot_password.php');
}

$_SESSION['pending_reset_user_id'] = $user['id'];
set_flash('success', 'A reset code has been sent to your email.');
redirect('../reset_password.php');