<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, event_type, event_date, event_time, target_course_id, target_year_level, target_section_label
    FROM upcoming_events
    WHERE professor_id = ?
    ORDER BY event_date ASC, event_time ASC
");
$stmt->execute([$_SESSION['user_id']]);
echo json_encode(['success' => true, 'events' => $stmt->fetchAll()]);