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
$class_id = (int)($_POST['class_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$points = (int)($_POST['points'] ?? 100);
$due_date = trim($_POST['due_date'] ?? '');

if ($title === '' || $points <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and points are required.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM class_sections WHERE id = ? AND professor_id = ?");
$stmt->execute([$class_id, $professor_id]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Class not found.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO assignments (class_section_id, title, description, points, due_date) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$class_id, $title, $description, $points, $due_date !== '' ? $due_date : null]);
$assignment_id = $pdo->lastInsertId();

// Seed a pending submission row for every enrolled student
$stmt = $pdo->prepare("SELECT user_id FROM students WHERE course_id = ? AND year_level = ? AND section_label = ?");
$stmt->execute([$class['course_id'], $class['year_level'], $class['section_label']]);
$students = $stmt->fetchAll();

$seedStmt = $pdo->prepare("INSERT IGNORE INTO assignment_submissions (assignment_id, student_id, status) VALUES (?, ?, 'pending')");
foreach ($students as $s) {
    $seedStmt->execute([$assignment_id, $s['user_id']]);
}

echo json_encode(['success' => true, 'id' => $assignment_id]);