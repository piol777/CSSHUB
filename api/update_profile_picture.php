<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$userId = $_SESSION['user_id'];

if (empty($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please choose an image to upload.']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

$fileType = $_FILES['profile_picture']['type'];
$fileSize = $_FILES['profile_picture']['size'];
$tmpName = $_FILES['profile_picture']['tmp_name'];

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed.']);
    exit;
}

if ($fileSize > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image must be under 5MB.']);
    exit;
}

$uploadDir = __DIR__ . '/../assets/images/uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
$safeName = 'profile_' . $userId . '_' . time() . '.' . strtolower($ext);
$destination = $uploadDir . $safeName;

if (!move_uploaded_file($tmpName, $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save the image.']);
    exit;
}

$newPath = 'assets/images/uploads/profiles/' . $safeName;

// Delete the old profile picture file (if any) to avoid piling up unused files
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$userId]);
$old = $stmt->fetchColumn();
if ($old && file_exists(__DIR__ . '/../' . $old)) {
    @unlink(__DIR__ . '/../' . $old);
}

$stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
$stmt->execute([$newPath, $userId]);

echo json_encode(['success' => true, 'profile_picture' => $newPath . '?v=' . time()]);