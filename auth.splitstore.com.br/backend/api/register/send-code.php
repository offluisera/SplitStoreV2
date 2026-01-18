<?php
// backend/api/register/send-code.php - VERSÃO FINAL CORRIGIDA

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

// Carregar PHPMailer ANTES de qualquer lógica
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
    // Ler input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido');
    }
    
    // Extrair dados
    $nome = trim($data['nome'] ?? '');
    $sobrenome = trim($data['sobrenome'] ?? '');
    $telefone = preg_replace('/\D/', '', $data['telefone'] ?? '');
    $email = trim($data['email'] ?? '');
    $cpf = preg_replace('/\D/', '', $data['cpf'] ?? '');
    
    // Validações
    $errors = [];
    
    if (empty($nome)) $errors['nome'] = 'Nome é obrigatório';
    if (empty($sobrenome)) $errors['sobrenome'] = 'Sobrenome é obrigatório';
    if (empty($telefone) || strlen($telefone) !== 11) $errors['telefone'] = 'Telefone inválido';
    
    if (empty($email)) {
        $errors['email'] = 'E-mail é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'E-mail inválido';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Este e-mail já está cadastrado';
        }
    }
    
    if (empty($cpf) || strlen($cpf) !== 11) {
        $errors['cpf'] = 'CPF inválido';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) {
            $errors['cpf'] = 'Este CPF já está cadastrado';
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['errors' => $errors]);
        exit;
    }
    
    // Gerar código
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
    
    // Salvar no Redis
    if ($redis) {
        $redis->setex("temp_register:$email", 900, json_encode([
            'nome' => $nome,
            'sobrenome' => $sobrenome,
            'telefone' => $telefone,
            'cpf' => $cpf
        ]));
    }
    
    // ============================================
    // ENVIAR E-MAIL COM PHPMAILER
    // ============================================
    
    $emailSent = false;
    $emailError = null;
    
    try {
        $emailConfig = require __DIR__ . '/../../includes/email-config.php';
        
        $mail = new PHPMailer(true);
        
        // Configurações
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $emailConfig['smtp_username'];
        $mail->Password = $emailConfig['smtp_password'];
        $mail->Port = $emailConfig['smtp_port'];
        
        // Adicionar SMTPSecure apenas se não estiver vazio
        if (!empty($emailConfig['smtp_secure'])) {
            $mail->SMTPSecure = $emailConfig['smtp_secure'];
        }
        
        $mail->CharSet = 'UTF-8';
        
        // Remetente e destinatário
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($email, "$nome $sobrenome");
        
        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Seu código de verificação - SplitStore';
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background-color: #000; color: #fff; padding: 20px; }
                .container { background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%); border: 2px solid #dc2626; border-radius: 15px; padding: 40px; max-width: 600px; margin: 0 auto; }
                .logo { font-size: 36px; font-weight: bold; text-align: center; margin-bottom: 30px; }
                .logo span { color: #dc2626; }
                .code-box { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 15px; padding: 30px; text-align: center; margin: 30px 0; }
                .code { font-size: 56px; font-weight: bold; color: #fff; letter-spacing: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='logo'>Split<span>Store</span></div>
                <h2 style='text-align: center; color: #dc2626;'>✉️ Verificação de E-mail</h2>
                <p>Olá <strong>$nome $sobrenome</strong>,</p>
                <p>Use o código abaixo para verificar seu e-mail:</p>
                <div class='code-box'><div class='code'>$code</div></div>
                <p><strong style='color: #dc2626;'>⏰ Este código expira em 10 minutos.</strong></p>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Olá $nome $sobrenome,\n\nSeu código de verificação é: $code\n\nEste código expira em 10 minutos.";
        
        $mail->send();
        $emailSent = true;
        error_log("✅ E-mail enviado com sucesso para: $email");
        
    } catch (Exception $e) {
        $emailError = $e->getMessage();
        error_log("❌ Erro ao enviar e-mail: $emailError");
    }
    
    // Log do código
    error_log("=== CÓDIGO DE VERIFICAÇÃO ===");
    error_log("Email: $email");
    error_log("Código: $code");
    error_log("E-mail enviado: " . ($emailSent ? 'SIM' : 'NÃO'));
    error_log("============================");
    
    // Resposta de sucesso
    $response = [
        'success' => true,
        'message' => $emailSent 
            ? 'Código enviado para seu e-mail!' 
            : 'Código gerado! Verifique seu e-mail.',
        'email_sent' => $emailSent
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar solicitação',
        'details' => $e->getMessage()
    ]);
}