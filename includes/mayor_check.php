<?php
function is_current_user_mayor(PDO $pdo): bool {
    if (($_SESSION['role'] ?? '') !== 'student') return false;
    $stmt = $pdo->prepare("SELECT is_mayor FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (bool)$stmt->fetchColumn();
}