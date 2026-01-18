<?php
$host = 'localhost';
$dbname = 'splitstore_db';
$username = 'splitstore_db';  // Altere conforme seu banco
$password = 'Hn2FY2823ZWGbAyH';  // Altere conforme seu banco

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    $pdo = null;
}

// Redis (opcional)
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
} catch(Exception $e) {
    error_log("Redis Error: " . $e->getMessage());
    $redis = null;
}