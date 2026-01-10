<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';
    $lembrar = $data['lembrar'] ?? false;
    
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    }
    
    if (empty($senha)) {
        $errors['senha'] = 'Senha é obrigatória';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, nome, email, senha, status 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['errors' => ['email' => 'Email ou senha incorretos']]);
        exit;
    }
    
    if (!password_verify($senha, $user['senha'])) {
        http_response_code(401);
        echo json_encode(['errors' => ['senha' => 'Email ou senha incorretos']]);
        exit;
    }
    
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Conta desativada. Entre em contato com o suporte.']);
        exit;
    }
    
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime($lembrar ? '+30 days' : '+7 days'));
    
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user['id'], $token, $expiresAt]);
    
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    if (isset($redis)) {
        $ttl = $lembrar ? 2592000 : 604800;
        $redis->setex("session:$token", $ttl, json_encode([
            'user_id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email']
        ]));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao fazer login. Tente novamente.']);
}
