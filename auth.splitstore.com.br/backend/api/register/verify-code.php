<?php
// backend/api/register/verify-code.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

// Conexão com banco
$host = 'localhost';
$dbname = 'splitstore_clientes';
$username = 'splitstore_clientes';
$password = 'hRC5kmrhGRSm7CMZ';

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
    die(json_encode(['error' => 'Erro de conexão']));
}

// Redis
$redis = null;
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('def0b8c10b5bd0ba');
        if (!$redis->ping()) throw new Exception('Redis ping failed');
    }
} catch(Exception $e) {
    $redis = null;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    $email = trim($data['email'] ?? '');
    $code = trim($data['code'] ?? '');
    
    if (empty($email) || empty($code)) {
        http_response_code(400);
        echo json_encode(['error' => 'E-mail e código são obrigatórios']);
        exit;
    }
    
    // Buscar código de verificação
    $stmt = $pdo->prepare("
        SELECT id, code, expires_at, verified 
        FROM verification_codes 
        WHERE email = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $verification = $stmt->fetch();
    
    if (!$verification) {
        http_response_code(400);
        echo json_encode(['error' => 'Código não encontrado. Solicite um novo código.']);
        exit;
    }
    
    if ($verification['verified']) {
        http_response_code(400);
        echo json_encode(['error' => 'Este código já foi utilizado']);
        exit;
    }
    
    if (strtotime($verification['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Código expirado. Solicite um novo código.']);
        exit;
    }
    
    if ($verification['code'] !== $code) {
        http_response_code(400);
        echo json_encode(['error' => 'Código inválido']);
        exit;
    }
    
    // Buscar dados temporários
    $tempData = null;
    if ($redis) {
        $tempDataJson = $redis->get("temp_register:$email");
        if ($tempDataJson) {
            $tempData = json_decode($tempDataJson, true);
        }
    }
    
    if (!$tempData) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados do cadastro não encontrados. Inicie o processo novamente.']);
        exit;
    }
    
    // Criar usuário
    $nomeCompleto = $tempData['nome'] . ' ' . $tempData['sobrenome'];
    $senhaTemporaria = bin2hex(random_bytes(8));
    $senhaHash = password_hash($senhaTemporaria, PASSWORD_ARGON2ID);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (nome, sobrenome, email, telefone, cpf, senha, status, email_verified, verified_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', TRUE, NOW())
    ");
    $stmt->execute([
        $tempData['nome'],
        $tempData['sobrenome'],
        $email,
        $tempData['telefone'],
        $tempData['cpf'],
        $senhaHash
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Marcar código como verificado
    $stmt = $pdo->prepare("UPDATE verification_codes SET verified = TRUE WHERE id = ?");
    $stmt->execute([$verification['id']]);
    
    // Criar sessão
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $token, $expiresAt]);
    
    // Salvar no Redis
    if ($redis) {
        $redis->setex("session:$token", 2592000, json_encode([
            'user_id' => $userId,
            'nome' => $nomeCompleto,
            'email' => $email,
            'has_plan' => false // IMPORTANTE: Usuário não tem plano ainda
        ]));
        
        $redis->del("temp_register:$email");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'E-mail verificado com sucesso!',
        'token' => $token,
        'redirect_to' => 'plans', // REDIRECIONAR PARA PÁGINA DE PLANOS
        'user' => [
            'id' => $userId,
            'nome' => $nomeCompleto,
            'email' => $email,
            'has_plan' => false
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro no banco de dados']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar solicitação']);
}