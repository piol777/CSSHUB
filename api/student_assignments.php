<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT course_id, year_level, section_label FROM students WHERE user_id = ?");
$stmt->execute([$student_id]);
$me = $stmt->fetch();

if (!$me) {
    echo json_encode(['success' => true, 'assignments' => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.points, a.due_date,
        cs.subject_name, cs.color_hex,
        u.first_name AS prof_first_name, u.last_name AS prof_last_name,
        sub.id AS submission_id, sub.file_path, sub.submitted_at, sub.grade, sub.status
    FROM class_sections cs
    JOIN assignments a ON a.class_section_id = cs.id
    JOIN users u ON u.id = cs.professor_id
    LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
    WHERE cs.course_id = ? AND cs.year_level = ? AND cs.section_label = ?
    ORDER BY a.due_date IS NULL, a.due_date ASC
");
$stmt->execute([$student_id, $me['course_id'], $me['year_level'], $me['section_label']]);
echo json_encode(['success' => true, 'assignments' => $stmt->fetchAll()]);