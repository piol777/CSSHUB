<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$course_id = !empty($_GET['course_id']) ? (int)$_GET['course_id'] : null;
$year_level = !empty($_GET['year_level']) ? (int)$_GET['year_level'] : null;
$section_label = !empty($_GET['section_label']) ? trim($_GET['section_label']) : null;

$sql = "
    SELECT u.id, u.first_name, u.last_name, u.last_active, u.profile_picture
    FROM users u
    JOIN students s ON s.user_id = u.id
    WHERE (? IS NULL OR s.course_id = ?)
      AND (? IS NULL OR s.year_level = ?)
      AND (? IS NULL OR s.section_label = ?)
    ORDER BY u.first_name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$course_id, $course_id, $year_level, $year_level, $section_label, $section_label]);
$students = $stmt->fetchAll();

$result = array_map(function ($s) {
    $isOnline = false;
    if ($s['last_active']) {
        $diff = time() - strtotime($s['last_active']);
        $isOnline = $diff <= 120; // active within last 2 minutes
    }
    return [
        'id' => $s['id'],
        'first_name' => $s['first_name'],
        'last_name' => $s['last_name'],
        'online' => $isOnline,
        'profile_picture' => $s['profile_picture']
    ];
}, $students);

echo json_encode(['success' => true, 'students' => $result]);