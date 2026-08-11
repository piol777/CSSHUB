<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <button class="auth-theme-toggle" id="authThemeToggle" title="Toggle theme">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
        </svg>
    </button>
    <div class="auth-card">
        <h1>CDSGA HUB</h1>

        <?php if ($flash): ?>
            <div class="form-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" action="process/register_process.php" method="POST" autocomplete="off">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Enter first name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Enter last name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="student_id_number">Student ID</label>
                <input type="text" id="student_id_number" name="student_id_number" placeholder="e.g. 2023-00123" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select id="course_id" name="course_id" required>
                        <option value="" data-code="">Select your course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" data-code="<?= sanitize($course['code']) ?>">
                                <?= sanitize($course['name']) ?> (<?= sanitize($course['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="section_label">Section</label>
                <div class="section-input-wrapper">
                    <span class="section-prefix" id="sectionPrefix">--/</span>
                    <input type="text" id="section_label" name="section_label" placeholder="1-1" maxlength="10" disabled required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required minlength="8">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required minlength="8">
            </div>

            <button type="submit" class="btn-primary">Register</button>
        </form>

        <hr class="auth-divider">
        <div class="auth-link">
            <a href="login.php">Log in your account</a>
        </div>
    </div>

    <script src="../assets/js/register.js"></script>
    <script src="../assets/js/auth-theme.js"></script>
</body>
</html>