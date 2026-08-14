<?php
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../config/database.php';
guard_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cdsgahub/admin/sections.php');
}

$section_id = (int)($_POST['section_id'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM class_sections WHERE id = ?");
$stmt->execute([$section_id]);

set_flash('success', 'Section removed.');
redirect('/cdsgahub/admin/sections.php');