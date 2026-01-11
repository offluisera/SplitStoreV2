<?php
// backend/includes/db.php

$host = 'localhost';
$dbname = 'splitstore_auth';
$username = 'splitstore_auth';
$password = 'Hn2FY2823ZWGbAyH';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Erro ao conectar com o banco de dados'
    ]));
}
