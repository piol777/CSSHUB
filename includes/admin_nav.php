<?php if (!isset($currentPage)) { $currentPage = ''; } ?>
<div class="admin-sidebar">
    <span class="logo">CDSGA HUB</span>
    <a href="dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
    <a href="professors.php" class="<?= $currentPage === 'professors' ? 'active' : '' ?>">Professors</a>
    <a href="students.php" class="<?= $currentPage === 'students' ? 'active' : '' ?>">Students</a>
    <a href="../auth/logout.php">Sign Out</a>
</div>