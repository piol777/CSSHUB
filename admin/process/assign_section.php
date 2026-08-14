<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
guard_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cdsgahub/admin/sections.php');
}

$professor_id = (int)($_POST['professor_id'] ?? 0);
$course_id = (int)($_POST['course_id'] ?? 0);
$subject_name = trim($_POST['subject_name'] ?? '');
$year_level = (int)($_POST['year_level'] ?? 0);
$section_label = trim($_POST['section_label'] ?? '');
$semester_label = trim($_POST['semester_label'] ?? '');

if (!$professor_id || !$course_id || !$subject_name || !$year_level || !$section_label || !$semester_label) {
    set_flash('error', 'Please fill in all fields.');
    redirect('/cdsgahub/admin/sections.php');
}

$stmt = $pdo->prepare("
    INSERT INTO class_sections (professor_id, course_id, subject_name, year_level, section_label, semester_label, color_hex)
    VALUES (?, ?, ?, ?, ?, ?, '#7c5cff')
");
$stmt->execute([$professor_id, $course_id, $subject_name, $year_level, $section_label, $semester_label]);

set_flash('success', 'Section assigned successfully.');
redirect('/cdsgahub/admin/sections.php');