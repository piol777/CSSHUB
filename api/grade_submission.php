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
$submission_id = (int)($_POST['submission_id'] ?? 0);
$grade = (float)($_POST['grade'] ?? -1);
$feedback = trim($_POST['feedback'] ?? '');

if ($grade < 0 || $grade > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Grade must be between 0 and 100.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT sub.id FROM assignment_submissions sub
    JOIN assignments a ON a.id = sub.assignment_id
    JOIN class_sections cs ON cs.id = a.class_section_id
    WHERE sub.id = ? AND cs.professor_id = ?
");
$stmt->execute([$submission_id, $professor_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Submission not found.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE assignment_submissions SET grade = ?, feedback = ?, status = 'graded' WHERE id = ?");
$stmt->execute([$grade, $feedback, $submission_id]);

echo json_encode(['success' => true]);