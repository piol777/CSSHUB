<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$subject_name = trim($_POST['subject_name'] ?? '');
$year_level = (int)($_POST['year_level'] ?? 0);
$section_label = trim($_POST['section_label'] ?? '');
$semester_label = trim($_POST['semester_label'] ?? '');
$color_hex = trim($_POST['color_hex'] ?? '#7c5cff');

if ($subject_name === '' || $section_label === '' || $semester_label === '' || $course_id <= 0 || $year_level < 1 || $year_level > 4) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields correctly.']);
    exit;
}

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color_hex)) {
    $color_hex = '#7c5cff';
}

$stmt = $pdo->prepare("
    INSERT INTO class_sections (professor_id, course_id, subject_name, year_level, section_label, semester_label, color_hex)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$professor_id, $course_id, $subject_name, $year_level, $section_label, $semester_label, $color_hex]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);