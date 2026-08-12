<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if ($query === '') {
    echo json_encode(['success' => true, 'students' => []]);
    exit;
}

$like = '%' . $query . '%';

$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.profile_picture,
           s.year_level, s.section_label, c.code AS course_code
    FROM users u
    JOIN students s ON s.user_id = u.id
    LEFT JOIN courses c ON c.id = s.course_id
    WHERE u.first_name LIKE ? OR u.last_name LIKE ?
       OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
    ORDER BY u.first_name ASC, u.last_name ASC
    LIMIT 8
");
$stmt->execute([$like, $like, $like]);
$students = $stmt->fetchAll();

$result = array_map(function ($s) {
    return [
        'id' => $s['id'],
        'first_name' => $s['first_name'],
        'last_name' => $s['last_name'],
        'profile_picture' => $s['profile_picture'],
        'course_code' => $s['course_code'],
        'year_level' => $s['year_level'],
        'section_label' => $s['section_label'],
    ];
}, $students);

echo json_encode(['success' => true, 'students' => $result]);