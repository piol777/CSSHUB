<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../register.php');
}

$first_name = sanitize($_POST['first_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$course_id = (int)($_POST['course_id'] ?? 0);
$year_level = (int)($_POST['year_level'] ?? 0);
$section_label = sanitize($_POST['section_label'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!$first_name || !$last_name || !$email || !$course_id || !$year_level || !$section_label) {
    set_flash('error', 'Please fill in all fields.');
    redirect('../register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Invalid email address.');
    redirect('../register.php');
}

if (!preg_match('/^[0-9]+-[0-9]+$/', $section_label)) {
    set_flash('error', 'Section must be in the format like 1-1.');
    redirect('../register.php');
}

if (strlen($password) < 8) {
    set_flash('error', 'Password must be at least 8 characters.');
    redirect('../register.php');
}

if ($password !== $confirm_password) {
    set_flash('error', 'Password and Confirm Password do not match.');
    redirect('../register.php');
}

$maxAttempts = 5;
$registered = false;

for ($attempt = 1; $attempt <= $maxAttempts && !$registered; $attempt++) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            if ($existingUser['status'] === 'pending') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$existingUser['id']]);
            } else {
                set_flash('error', 'Email is already registered.');
                $pdo->rollBack();
                redirect('../register.php');
            }
        }

        $student_id_number = generate_next_student_id($pdo);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (role, email, password_hash, first_name, last_name, status)
            VALUES ('student', ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$email, $password_hash, $first_name, $last_name]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO students (user_id, student_id_number, course_id, section_label, year_level)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $student_id_number, $course_id, $section_label, $year_level]);

        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $stmt = $pdo->prepare("
            INSERT INTO email_verifications (user_id, code, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $code, $expires_at]);

        $pdo->commit();
        $registered = true;

        $emailSent = sendVerificationEmail($email, $first_name, $code);

        if (!$emailSent) {
            set_flash('error', 'Account created, but the verification email failed to send. Check config/mail.php credentials.');
        }

        $_SESSION['pending_verification_user_id'] = $user_id;
        redirect('../verify.php');

    } catch (PDOException $e) {
        $pdo->rollBack();

        // Duplicate student_id_number = race condition (two people registered at the exact same time).
        // Retry with a freshly generated ID instead of failing the user out.
        if ($e->errorInfo[1] === 1062 && $attempt < $maxAttempts) {
            continue;
        }

        set_flash('error', 'Registration failed. Please try again.');
        redirect('../register.php');
    }
}