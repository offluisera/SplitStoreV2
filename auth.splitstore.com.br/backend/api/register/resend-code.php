<?php
// auth.splitstore.com.br/backend/api/register/resend-code.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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
    die(json_encode(['error' => 'Erro de conexão com banco de dados']));
}

// Redis
$redis = null;
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('def0b8c10b5bd0ba');
        if (!$redis->ping()) {
            throw new Exception('Redis ping failed');
        }
    }
} catch(Exception $e) {
    error_log("Redis Error: " . $e->getMessage());
    $redis = null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    $email = trim($data['email'] ?? '');
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'E-mail é obrigatório']);
        exit;
    }
    
    // Verificar se há dados temporários
    $tempData = null;
    if (isset($redis) && $redis) {
        $tempDataJson = $redis->get("temp_register:$email");
        if ($tempDataJson) {
            $tempData = json_decode($tempDataJson, true);
        }
    }
    
    if (!$tempData) {
        http_response_code(400);
        echo json_encode(['error' => 'Sessão expirada. Inicie o cadastro novamente.']);
        exit;
    }
    
    // Verificar rate limit (máximo 1 código a cada 60 segundos)
    $stmt = $pdo->prepare("
        SELECT created_at 
        FROM verification_codes 
        WHERE email = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $lastCode = $stmt->fetch();
    
    if ($lastCode) {
        $timeSinceLastCode = time() - strtotime($lastCode['created_at']);
        if ($timeSinceLastCode < 60) {
            $waitTime = 60 - $timeSinceLastCode;
            http_response_code(429);
            echo json_encode([
                'error' => "Aguarde $waitTime segundos antes de solicitar um novo código"
            ]);
            exit;
        }
    }
    
    // Gerar novo código
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Deletar códigos antigos
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE email = ?");
    $stmt->execute([$email]);
    
    // Inserir novo código
    $stmt = $pdo->prepare("
        INSERT INTO verification_codes (email, code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $code, $expiresAt]);
    
    // Enviar e-mail
    $to = $email;
    $subject = "Novo código de verificação - SplitStore";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
            .container { background-color: white; border-radius: 10px; padding: 30px; max-width: 600px; margin: 0 auto; }
            .header { text-align: center; margin-bottom: 30px; }
            .logo { font-size: 32px; font-weight: bold; color: #dc2626; }
            .code-box { background-color: #fee2e2; border: 2px dashed #dc2626; border-radius: 10px; padding: 20px; text-align: center; margin: 30px 0; }
            .code { font-size: 48px; font-weight: bold; color: #dc2626; letter-spacing: 10px; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>SplitStore</div>
                <h2>Novo Código de Verificação</h2>
            </div>
            
            <p>Olá <strong>{$tempData['nome']} {$tempData['sobrenome']}</strong>,</p>
            
            <p>Você solicitou um novo código de verificação:</p>
            
            <div class='code-box'>
                <div class='code'>$code</div>
            </div>
            
            <p><strong>Este código expira em 10 minutos.</strong></p>
            
            <p>Se você não solicitou este código, ignore este e-mail.</p>
            
            <div class='footer'>
                <p>© 2026 SplitStore - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: SplitStore <noreply@splitstore.com.br>',
        'Reply-To: suporte@splitstore.com.br'
    ];
    
    mail($to, $subject, $message, implode("\r\n", $headers));
    
    echo json_encode([
        'success' => true,
        'message' => 'Novo código enviado!'
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