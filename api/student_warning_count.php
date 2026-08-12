<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SESSION['role'] === 'professor') {
    $student_id = (int)($_GET['student_id'] ?? 0);
} else {
    $student_id = $_SESSION['user_id'];
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM student_warnings WHERE student_id = ?");
$stmt->execute([$student_id]);
$count = (int)$stmt->fetchColumn();

$reasonsStmt = $pdo->prepare("
    SELECT sw.reason, sw.created_at, u.first_name, u.last_name
    FROM student_warnings sw
    JOIN users u ON u.id = sw.professor_id
    WHERE sw.student_id = ?
    ORDER BY sw.created_at DESC
");
$reasonsStmt->execute([$student_id]);

echo json_encode([
    'success' => true,
    'warning_count' => $count,
    'restricted' => $count >= 3,
    'warnings' => $reasonsStmt->fetchAll()
]);