<?php
// backend/api/register/send-code-debug.php
// Arquivo temporário para debug

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$log = [];
$log[] = "=== DEBUG SEND CODE ===";
$log[] = "Timestamp: " . date('Y-m-d H:i:s');
$log[] = "Method: " . $_SERVER['REQUEST_METHOD'];

// 1. Verificar input
$input = file_get_contents('php://input');
$log[] = "Input recebido: " . $input;

$data = json_decode($input, true);
$log[] = "JSON decode: " . (json_last_error() === JSON_ERROR_NONE ? 'OK' : 'ERRO - ' . json_last_error_msg());

// 2. Testar conexão com banco
$host = 'localhost';
$dbname = 'splitstore_clientes';
$username = 'splitstore_clientes';
$password = 'hRC5kmrhGRSm7CMZ';

$log[] = "Testando conexão com banco...";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    $log[] = "✅ Conexão PDO: OK";
} catch(PDOException $e) {
    $log[] = "❌ Erro PDO: " . $e->getMessage();
    echo json_encode([
        'error' => 'Erro de conexão',
        'debug' => $log
    ]);
    exit;
}

// 3. Verificar dados recebidos
$nome = trim($data['nome'] ?? '');
$sobrenome = trim($data['sobrenome'] ?? '');
$telefone = preg_replace('/\D/', '', $data['telefone'] ?? '');
$email = trim($data['email'] ?? '');
$cpf = preg_replace('/\D/', '', $data['cpf'] ?? '');

$log[] = "Dados extraídos:";
$log[] = "- Nome: $nome";
$log[] = "- Sobrenome: $sobrenome";
$log[] = "- Telefone: $telefone";
$log[] = "- Email: $email";
$log[] = "- CPF: $cpf";

// 4. Validações básicas
$errors = [];

if (empty($nome)) $errors['nome'] = 'Nome é obrigatório';
if (empty($sobrenome)) $errors['sobrenome'] = 'Sobrenome é obrigatório';
if (empty($telefone) || strlen($telefone) !== 11) $errors['telefone'] = 'Telefone inválido';
if (empty($email)) {
    $errors['email'] = 'E-mail é obrigatório';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'E-mail inválido';
}
if (empty($cpf) || strlen($cpf) !== 11) $errors['cpf'] = 'CPF inválido';

if (!empty($errors)) {
    $log[] = "❌ Erros de validação: " . json_encode($errors);
    echo json_encode([
        'errors' => $errors,
        'debug' => $log
    ]);
    exit;
}

$log[] = "✅ Validações: OK";

// 5. Verificar se email já existe
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $log[] = "❌ E-mail já cadastrado";
        echo json_encode([
            'errors' => ['email' => 'Este e-mail já está cadastrado'],
            'debug' => $log
        ]);
        exit;
    }
    $log[] = "✅ E-mail disponível";
} catch (PDOException $e) {
    $log[] = "❌ Erro ao verificar e-mail: " . $e->getMessage();
}

// 6. Verificar se CPF já existe
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE cpf = ?");
    $stmt->execute([$cpf]);
    if ($stmt->fetch()) {
        $log[] = "❌ CPF já cadastrado";
        echo json_encode([
            'errors' => ['cpf' => 'Este CPF já está cadastrado'],
            'debug' => $log
        ]);
        exit;
    }
    $log[] = "✅ CPF disponível";
} catch (PDOException $e) {
    $log[] = "❌ Erro ao verificar CPF: " . $e->getMessage();
}

// 7. Gerar código
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$log[] = "✅ Código gerado: $code";
$log[] = "✅ Expira em: $expiresAt";

// 8. Verificar estrutura da tabela verification_codes
try {
    $stmt = $pdo->query("DESCRIBE verification_codes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $log[] = "✅ Colunas da tabela verification_codes: " . implode(', ', $columns);
} catch (PDOException $e) {
    $log[] = "❌ Erro ao verificar tabela: " . $e->getMessage();
}

// 9. Tentar deletar códigos antigos
try {
    $stmt = $pdo->prepare("DELETE FROM verification_codes WHERE email = ?");
    $stmt->execute([$email]);
    $log[] = "✅ Códigos antigos deletados";
} catch (PDOException $e) {
    $log[] = "❌ Erro ao deletar códigos antigos: " . $e->getMessage();
}

// 10. Tentar inserir novo código
try {
    $stmt = $pdo->prepare("
        INSERT INTO verification_codes (email, code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $code, $expiresAt]);
    $log[] = "✅ Código inserido no banco";
} catch (PDOException $e) {
    $log[] = "❌ Erro ao inserir código: " . $e->getMessage();
    echo json_encode([
        'error' => 'Erro ao salvar código',
        'details' => $e->getMessage(),
        'debug' => $log
    ]);
    exit;
}

// 11. Verificar Redis
$redis = null;
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->auth('def0b8c10b5bd0ba');
        if ($redis->ping()) {
            $redis->setex("temp_register:$email", 900, json_encode([
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                'telefone' => $telefone,
                'cpf' => $cpf
            ]));
            $log[] = "✅ Redis: Dados salvos";
        } else {
            $log[] = "⚠️ Redis: Ping falhou";
        }
    } else {
        $log[] = "⚠️ Redis: Extensão não disponível";
    }
} catch(Exception $e) {
    $log[] = "⚠️ Redis: " . $e->getMessage();
}

// 12. Sucesso
$log[] = "✅ PROCESSO CONCLUÍDO COM SUCESSO";

echo json_encode([
    'success' => true,
    'message' => 'Código gerado com sucesso! (DEBUG MODE)',
    'code' => $code, // REMOVER EM PRODUÇÃO
    'expires_at' => $expiresAt,
    'debug' => $log
], JSON_PRETTY_PRINT);