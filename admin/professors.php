<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('admin');

$currentPage = 'professors';
$flash = get_flash();

$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.created_at, p.department
    FROM users u
    JOIN professors p ON p.user_id = u.id
    WHERE u.role = 'professor'
    ORDER BY u.created_at DESC
");
$professors = $stmt->fetchAll();

// Show generated credentials only right after creation (one-time display)
$newCredentials = $_SESSION['new_professor_credentials'] ?? null;
unset($_SESSION['new_professor_credentials']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Professors - CDSGA HUB Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="admin-content">
        <h1>Manage Professors</h1>

        <?php if ($flash): ?>
            <div class="admin-card" style="border-color:<?= $flash['type'] === 'error' ? '#ff4757' : '#46ff82' ?>;">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($newCredentials): ?>
            <div class="credential-box">
                <strong>Account created successfully.</strong> Share these login credentials with the professor (shown only once):<br>
                Email: <strong><?= sanitize($newCredentials['email']) ?></strong><br>
                Temporary Password: <strong><?= sanitize($newCredentials['password']) ?></strong>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <h2 style="color:#fff; font-size:16px; margin-bottom:16px;">Create Professor Account</h2>
            <form action="process/create_professor.php" method="POST">
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="admin-form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" placeholder="e.g. College of Computer Studies" required>
                    </div>
                </div>
                <button type="submit" class="admin-btn">Create Account</button>
            </form>
        </div>

        <div class="admin-card">
            <h2 style="color:#fff; font-size:16px; margin-bottom:16px;">All Professors</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($professors)): ?>
                        <tr><td colspan="5" style="color:#9a97b8;">No professors yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($professors as $prof): ?>
                            <tr>
                                <td>Prof. <?= sanitize($prof['first_name'] . ' ' . $prof['last_name']) ?></td>
                                <td><?= sanitize($prof['email']) ?></td>
                                <td><?= sanitize($prof['department']) ?></td>
                                <td><span class="badge-active"><?= sanitize($prof['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($prof['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>