<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$student_id = $_SESSION['user_id'];
$assignment_id = (int)($_POST['assignment_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, status FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $student_id]);
$submission = $stmt->fetch();

if (!$submission) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Assignment not found for your section.']);
    exit;
}

if ($submission['status'] === 'graded') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This assignment has already been graded and can no longer be resubmitted.']);
    exit;
}

if (empty($_FILES['file']['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please choose a file to submit.']);
    exit;
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
    exit;
}

$allowedExt = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
$maxSize = 20 * 1024 * 1024;
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That file type is not allowed.']);
    exit;
}

if ($_FILES['file']['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File must be under 20MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../assets/uploads/assignments/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeName = 'sub_' . $student_id . '_' . $assignment_id . '_' . time() . '.' . $ext;
$destination = $uploadDir . $safeName;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$filePath = 'assets/uploads/assignments/' . $safeName;

$stmt = $pdo->prepare("UPDATE assignment_submissions SET file_path = ?, submitted_at = NOW(), status = 'submitted' WHERE id = ?");
$stmt->execute([$filePath, $submission['id']]);

echo json_encode(['success' => true]);