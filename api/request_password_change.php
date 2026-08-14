<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mail.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    redirect('/cdsgahub/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cdsgahub/professor/change_password.php');
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (strlen($new_password) < 8) {
    set_flash('error', 'New password must be at least 8 characters.');
    redirect('/cdsgahub/professor/change_password.php');
}

if ($new_password !== $confirm_password) {
    set_flash('error', 'New password and confirmation do not match.');
    redirect('/cdsgahub/professor/change_password.php');
}

$stmt = $pdo->prepare("SELECT password_hash, email, first_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password_hash'])) {
    set_flash('error', 'Your current password is incorrect.');
    redirect('/cdsgahub/professor/change_password.php');
}

$code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

$stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, code, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $code, $expiresAt]);

sendVerificationEmail($user['email'], $user['first_name'], $code);

$_SESSION['pending_password_hash'] = password_hash($new_password, PASSWORD_DEFAULT);

set_flash('success', 'A verification code was sent to your email.');
redirect('/cdsgahub/professor/change_password.php');