<?php
date_default_timezone_set('Asia/Manila');

$DB_HOST = 'localhost';
$DB_NAME = 'cdsgahub';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}