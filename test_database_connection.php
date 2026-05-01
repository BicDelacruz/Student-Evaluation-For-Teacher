<?php
declare(strict_types=1);

require_once __DIR__ . "/database_connector.php";

try {
    $statement = $pdo->query("SELECT DATABASE() AS database_name");
    $result = $statement->fetch();

    echo "Database connected successfully.<br>";
    echo "Connected database: " . htmlspecialchars($result["database_name"]);
} catch (PDOException $error) {
    echo "Database connection failed.";
}