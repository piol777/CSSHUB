<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php');
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    set_flash('error', 'Please enter both email and password.');
    redirect('../login.php');
}

$stmt = $pdo->prepare("SELECT id, role, email, password_hash, first_name, last_name, status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    set_flash('error', 'Incorrect email or password.');
    redirect('../login.php');
}

if ($user['status'] === 'pending') {
    // Resend them to verify instead of logging in
    $_SESSION['pending_verification_user_id'] = $user['id'];
    set_flash('error', 'Please verify your email before logging in.');
    redirect('../verify.php');
}

if ($user['status'] === 'disabled') {
    set_flash('error', 'This account has been disabled. Contact the administrator.');
    redirect('../login.php');
}

// Regenerate session ID on login to prevent session fixation
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['email'] = $user['email'];

redirect('../../' . $user['role'] . '/dashboard.php');