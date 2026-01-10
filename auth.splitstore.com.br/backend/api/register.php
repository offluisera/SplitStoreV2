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
    
    $nome = trim($data['nome'] ?? '');
    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';
    $confirmarSenha = $data['confirmarSenha'] ?? '';
    
    $errors = [];
    
    if (empty($nome)) {
        $errors['nome'] = 'Nome completo é obrigatório';
    } elseif (strlen($nome) < 3) {
        $errors['nome'] = 'Nome deve ter no mínimo 3 caracteres';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Este email já está cadastrado';
        }
    }
    
    if (empty($senha)) {
        $errors['senha'] = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $errors['senha'] = 'Senha deve ter no mínimo 6 caracteres';
    } elseif ($senha !== $confirmarSenha) {
        $errors['confirmarSenha'] = 'As senhas não coincidem';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit;
    }
    
    $senhaHash = password_hash($senha, PASSWORD_ARGON2ID);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (nome, email, senha, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$nome, $email, $senhaHash]);
    $userId = $pdo->lastInsertId();
    
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $token, $expiresAt]);
    
    if (isset($redis)) {
        $redis->setex("session:$token", 2592000, json_encode([
            'user_id' => $userId,
            'nome' => $nome,
            'email' => $email
        ]));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Conta criada com sucesso!',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'nome' => $nome,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Register Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao criar conta. Tente novamente.']);
}
