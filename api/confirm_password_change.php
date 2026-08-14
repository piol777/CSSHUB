<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    redirect('/cdsgahub/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['pending_password_hash'])) {
    redirect('/cdsgahub/professor/change_password.php');
}

$user_id = $_SESSION['user_id'];
$code = sanitize($_POST['code'] ?? '');

$stmt = $pdo->prepare("
    SELECT id, expires_at, verified_at
    FROM email_verifications
    WHERE user_id = ? AND code = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$user_id, $code]);
$record = $stmt->fetch();

if (!$record) {
    set_flash('error', 'Invalid verification code.');
    redirect('/cdsgahub/professor/change_password.php');
}

if ($record['verified_at'] !== null) {
    set_flash('error', 'This code was already used.');
    redirect('/cdsgahub/professor/change_password.php');
}

if (strtotime($record['expires_at']) < time()) {
    set_flash('error', 'This code has expired. Please request a new one.');
    unset($_SESSION['pending_password_hash']);
    redirect('/cdsgahub/professor/change_password.php');
}

$pdo->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE id = ?")->execute([$record['id']]);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$_SESSION['pending_password_hash'], $user_id]);

unset($_SESSION['pending_password_hash']);

set_flash('success', 'Your password has been changed successfully.');
redirect('/cdsgahub/professor/change_password.php');