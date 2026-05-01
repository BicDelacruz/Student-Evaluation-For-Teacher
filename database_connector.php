<?php
declare(strict_types=1);

$database_host = "localhost";
$database_name = "student_evaluation_for_teacher_db";
$database_username = "root";
$database_password = "";
$database_charset = "utf8mb4";

$dsn = "mysql:host={$database_host};dbname={$database_name};charset={$database_charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $database_username, $database_password, $options);
} catch (PDOException $error) {
    error_log("Database connection error: " . $error->getMessage());
    die("Database connection failed. Please check your database settings.");
}