<?php
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
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Erro de conexão com banco de dados']));
}

// Redis com autenticação
$redis = null;
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('def0b8c10b5bd0ba');
        
        // Testar conexão
        if (!$redis->ping()) {
            throw new Exception('Redis ping failed');
        }
    }
} catch(Exception $e) {
    error_log("Redis Error: " . $e->getMessage());
    $redis = null;
}