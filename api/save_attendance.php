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
$session_id = (int)($_POST['session_id'] ?? 0);
$records = json_decode($_POST['records'] ?? '[]', true);
$validStatuses = ['present', 'absent', 'late', 'excused'];

// Verify this session belongs to a class owned by this professor
$stmt = $pdo->prepare("
    SELECT ats.id FROM attendance_sessions ats
    JOIN class_sections cs ON cs.id = ats.class_section_id
    WHERE ats.id = ? AND cs.professor_id = ?
");
$stmt->execute([$session_id, $professor_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Session not found.']);
    exit;
}

$updateStmt = $pdo->prepare("UPDATE attendance_records SET status = ? WHERE session_id = ? AND student_id = ?");
foreach ($records as $r) {
    $status = in_array($r['status'] ?? '', $validStatuses, true) ? $r['status'] : 'present';
    $updateStmt->execute([$status, $session_id, (int)$r['student_id']]);
}

echo json_encode(['success' => true]);