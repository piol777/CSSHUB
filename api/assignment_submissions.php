<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$professor_id = $_SESSION['user_id'];
$assignment_id = (int)($_GET['assignment_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT sub.id AS submission_id, sub.student_id, u.first_name, u.last_name,
        sub.file_path, sub.submitted_at, sub.grade, sub.status, sub.feedback
    FROM assignment_submissions sub
    JOIN assignments a ON a.id = sub.assignment_id
    JOIN class_sections cs ON cs.id = a.class_section_id
    JOIN users u ON u.id = sub.student_id
    WHERE sub.assignment_id = ? AND cs.professor_id = ?
    ORDER BY u.last_name ASC
");
$stmt->execute([$assignment_id, $professor_id]);
echo json_encode(['success' => true, 'submissions' => $stmt->fetchAll()]);