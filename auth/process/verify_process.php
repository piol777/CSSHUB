<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['pending_verification_user_id'])) {
    redirect('../login.php');
}

$user_id = $_SESSION['pending_verification_user_id'];
$code = sanitize($_POST['code'] ?? '');

if (strlen($code) !== 4) {
    set_flash('error', 'Please enter the full 4-digit code.');
    redirect('../verify.php');
}

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
    redirect('../verify.php');
}

if ($record['verified_at'] !== null) {
    set_flash('error', 'This code was already used.');
    redirect('../verify.php');
}

if (strtotime($record['expires_at']) < time()) {
    set_flash('error', 'This code has expired. Please register again or request a new code.');
    redirect('../verify.php');
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE id = ?");
    $stmt->execute([$record['id']]);

    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    set_flash('error', 'Verification failed. Please try again.');
    redirect('../verify.php');
}

unset($_SESSION['pending_verification_user_id']);
set_flash('success', 'Email verified! You can now log in.');
redirect('../login.php');