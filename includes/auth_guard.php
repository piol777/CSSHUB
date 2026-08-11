<?php
// Include this at the TOP of every protected page (student/professor/admin dashboards etc.)
// Usage: require_once __DIR__ . '/../includes/auth_guard.php'; guard_role('student');

require_once __DIR__ . '/functions.php';

function guard_role(string $requiredRole): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        redirect('/cdsgahub/auth/login.php');
    }

    if ($_SESSION['role'] !== $requiredRole) {
        // Logged in, but wrong role trying to access this page
        redirect('/cdsgahub/' . $_SESSION['role'] . '/dashboard.php');
    }
}