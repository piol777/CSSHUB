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
$class_id = (int)($_GET['class_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.points, a.due_date,
        (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id AND sub.status != 'pending') AS submitted_count,
        (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id) AS total_count
    FROM assignments a
    JOIN class_sections cs ON cs.id = a.class_section_id
    WHERE a.class_section_id = ? AND cs.professor_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$class_id, $professor_id]);
echo json_encode(['success' => true, 'assignments' => $stmt->fetchAll()]);