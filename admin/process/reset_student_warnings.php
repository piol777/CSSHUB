<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
guard_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cdsgahub/admin/students.php');
}

$student_id = (int)($_POST['student_id'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM student_warnings WHERE student_id = ?");
$stmt->execute([$student_id]);

set_flash('success', 'Warnings reset for this student.');
redirect('/cdsgahub/admin/students.php');