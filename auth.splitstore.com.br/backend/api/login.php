<?php
// auth.splitstore.com.br/backend/api/login.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Log do método recebido
$method = $_SERVER['REQUEST_METHOD'];
error_log("=== LOGIN REQUEST ===");
error_log("Método: $method");
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A'));
error_log("HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'N/A'));

// Responder OPTIONS para CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

// Aceitar POST ou requisições que parecem POST
if ($method !== 'POST') {
    error_log("Método não é POST: $method");
    error_log("POST data: " . print_r($_POST, true));
    error_log("php://input: " . file_get_contents('php://input'));
    
    http_response_code(405);
    echo json_encode([
        'error' => 'Método não permitido',
        'received' => $method,
        'expected' => 'POST'
    ]);
    exit;
}

try {
    // Ler dados do corpo da requisição
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: $rawInput");
    
    if (empty($rawInput)) {
        error_log("Input vazio - tentando ler de \$_POST");
        $data = $_POST;
    } else {
        $data = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao decodificar JSON: " . json_last_error_msg());
            throw new Exception("Erro ao processar dados JSON");
        }
    }
    
    error_log("Dados recebidos: " . print_r($data, true));
    
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
    
    // Buscar usuário
    $stmt = $pdo->prepare("
        SELECT id, nome, email, senha, status 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        error_log("Usuário não encontrado: $email");
        http_response_code(401);
        echo json_encode(['errors' => ['email' => 'Email ou senha incorretos']]);
        exit;
    }
    
    // Verificar senha
    if (!password_verify($senha, $user['senha'])) {
        error_log("Senha incorreta");
        http_response_code(401);
        echo json_encode(['errors' => ['senha' => 'Email ou senha incorretos']]);
        exit;
    }
    
    // Verificar status
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['error' => 'Conta desativada. Entre em contato com o suporte.']);
        exit;
    }
    
    // Gerar token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime($lembrar ? '+30 days' : '+7 days'));
    
    // Salvar sessão
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user['id'], $token, $expiresAt]);
    
    // Atualizar last_login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Redis (opcional)
    if (isset($redis)) {
        $ttl = $lembrar ? 2592000 : 604800;
        $redis->setex("session:$token", $ttl, json_encode([
            'user_id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email']
        ]));
    }
    
    $response = [
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email']
        ]
    ];
    
    error_log("LOGIN SUCESSO - Token: " . substr($token, 0, 20) . "...");
    error_log("Response: " . json_encode($response));
    
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao fazer login. Tente novamente.',
        'details' => $e->getMessage()
    ]);
}