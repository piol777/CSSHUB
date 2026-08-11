<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
guard_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../professors.php');
}

$first_name = sanitize($_POST['first_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$department = sanitize($_POST['department'] ?? '');

if (!$first_name || !$last_name || !$email || !$department) {
    set_flash('error', 'Please fill in all fields.');
    redirect('../professors.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Invalid email address.');
    redirect('../professors.php');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    set_flash('error', 'Email is already in use.');
    redirect('../professors.php');
}

$plainPassword = generate_random_password(10);
$passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (role, email, password_hash, first_name, last_name, status)
        VALUES ('professor', ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$email, $passwordHash, $first_name, $last_name]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO professors (user_id, department) VALUES (?, ?)");
    $stmt->execute([$user_id, $department]);

    $pdo->commit();

    // Store credentials temporarily to display once on the professors page
    $_SESSION['new_professor_credentials'] = [
        'email' => $email,
        'password' => $plainPassword
    ];

    set_flash('success', 'Professor account created successfully.');
    redirect('../professors.php');

} catch (PDOException $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to create professor account.');
    redirect('../professors.php');
}