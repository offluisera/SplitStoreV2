<?php
// backend/includes/db.php

$host = 'localhost';
$dbname = 'splitstore_auth'; // Usando o banco que você já tem
$username = 'splitstore_auth'; // Ajuste conforme seu usuário do MySQL
$password = 'Hn2FY2823ZWGbAyH'; // Altere para sua senha real

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