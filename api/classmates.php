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

if (!$me || is_student_restricted($pdo, $student_id)) {
    echo json_encode(['success' => true, 'classmates' => []]);
    exit;
}

// Only classmates in the SAME course + year level + section — student can't browse other sections
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.profile_picture, u.last_active
    FROM users u
    JOIN students s ON s.user_id = u.id
    WHERE s.course_id = ? AND s.year_level = ? AND s.section_label = ? AND u.id != ?
    ORDER BY u.first_name ASC
");
$stmt->execute([$me['course_id'], $me['year_level'], $me['section_label'], $student_id]);
$classmates = $stmt->fetchAll();

$result = array_map(function ($s) {
    $isOnline = false;
    if ($s['last_active']) {
        $isOnline = (time() - strtotime($s['last_active'])) <= 120;
    }
    return [
        'id' => $s['id'],
        'first_name' => $s['first_name'],
        'last_name' => $s['last_name'],
        'profile_picture' => $s['profile_picture'],
        'online' => $isOnline,
    ];
}, $classmates);

echo json_encode(['success' => true, 'classmates' => $result]);