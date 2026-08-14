<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
guard_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cdsgahub/admin/students.php');
}

$student_id = (int)($_POST['student_id'] ?? 0);
$new_status = $_POST['new_status'] ?? '';

if (!in_array($new_status, ['active', 'disabled'], true)) {
    set_flash('error', 'Invalid status.');
    redirect('/cdsgahub/admin/students.php');
}

$stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'student'");
$stmt->execute([$new_status, $student_id]);

set_flash('success', 'Student status updated.');
redirect('/cdsgahub/admin/students.php');