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
$announcement_id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$target_course_id = !empty($_POST['target_course_id']) ? (int)$_POST['target_course_id'] : null;
$target_year_level = !empty($_POST['target_year_level']) ? (int)$_POST['target_year_level'] : null;
$target_section_label = !empty($_POST['target_section_label']) ? trim($_POST['target_section_label']) : null;

if (!$announcement_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing post ID.']);
    exit;
}

if ($title === '' || $content === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and content are required.']);
    exit;
}

if (strlen($title) > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title is too long.']);
    exit;
}

try {
    // Ownership check first, separate from the UPDATE, so a "no actual change" edit
    // doesn't get mistaken for "not your post" (rowCount() would be 0 in both cases).
    $stmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND professor_id = ?");
    $stmt->execute([$announcement_id, $professor_id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found or you do not have permission to edit it.']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE announcements
        SET title = ?, content = ?, target_course_id = ?, target_year_level = ?, target_section_label = ?, updated_at = NOW()
        WHERE id = ? AND professor_id = ?
    ");
    $stmt->execute([$title, $content, $target_course_id, $target_year_level, $target_section_label, $announcement_id, $professor_id]);

    echo json_encode(['success' => true, 'message' => 'Announcement updated successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update post.']);
}