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
    SELECT ats.id, ats.session_date,
        ROUND(AVG(CASE WHEN ar.status = 'present' THEN 100 ELSE 0 END), 0) AS present_pct
    FROM attendance_sessions ats
    JOIN class_sections cs ON cs.id = ats.class_section_id
    LEFT JOIN attendance_records ar ON ar.session_id = ats.id
    WHERE ats.class_section_id = ? AND cs.professor_id = ?
    GROUP BY ats.id
    ORDER BY ats.session_date DESC
");
$stmt->execute([$class_id, $professor_id]);
echo json_encode(['success' => true, 'sessions' => $stmt->fetchAll()]);