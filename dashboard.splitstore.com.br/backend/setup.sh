#!/bin/bash
# setup.sh - Criar todos os arquivos necessários para o checkout

echo "🚀 SETUP SPLITSTORE - CHECKOUT MISTICPAY"
echo "========================================="
echo ""

BASE_DIR="/www/wwwroot/dashboard.splitstore.com.br/backend"
cd "$BASE_DIR" || exit 1

# Cores
GREEN='\033[0;32m'
NC='\033[0m'

# 1. CRIAR ESTRUTURA
echo "📁 Criando estrutura de diretórios..."
mkdir -p api includes logs webhooks
chmod 755 api includes webhooks
chmod 777 logs
echo -e "${GREEN}✅ Estrutura criada${NC}"

# 2. CRIAR db.php
echo "💾 Criando db.php..."
cat > includes/db.php << 'DBEOF'
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
DBEOF
echo -e "${GREEN}✅ db.php criado${NC}"

# 3. CRIAR misticpay.php
echo "💳 Criando misticpay.php..."
cat > includes/misticpay.php << 'MPEOF'
<?php
// backend/includes/misticpay.php

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com/api';
    
    public function createPayment($data) {
        $payload = [
            'amount' => floatval($data['amount']),
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'customer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email']
            ],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'store_name' => $data['store_name'],
                'user_id' => $data['user_id'],
                'pending_store_id' => $data['pending_store_id']
            ],
            'webhook_url' => 'https://splitstore.com.br/webhooks/misticpay.php',
            'return_url' => 'https://dashboard.splitstore.com.br?status=success'
        ];
        
        error_log("MisticPay Request: " . json_encode($payload));
        
        $result = $this->makeRequest('POST', '/payments', $payload);
        
        error_log("MisticPay Response: " . json_encode($result));
        
        return $result;
    }
    
    public function getPayment($paymentId) {
        return $this->makeRequest('GET', "/payments/{$paymentId}");
    }
    
    public function cancelPayment($paymentId) {
        return $this->makeRequest('POST', "/payments/{$paymentId}/cancel");
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Client-Id: ' . $this->clientId,
            'X-Client-Secret: ' . $this->clientSecret
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'data' => null
            ];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $result ?? $response
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        $expectedSignature = hash_hmac('sha256', $payload, $this->clientSecret);
        return hash_equals($expectedSignature, $signature);
    }
}
MPEOF
echo -e "${GREEN}✅ misticpay.php criado${NC}"

# 4. CRIAR checkout.php
echo "🛒 Criando checkout.php..."
cat > api/checkout.php << 'COEOF'
<?php
// backend/api/checkout.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/misticpay.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (empty($token)) {
        http_response_code(401);
        die(json_encode(['error' => 'Token não fornecido']));
    }
    
    // Buscar usuário
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.email 
        FROM users u
        JOIN sessions s ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(401);
        die(json_encode(['error' => 'Sessão inválida']));
    }
    
    // Validar dados
    $planId = $data['plan_id'] ?? '';
    $storeName = trim($data['store_name'] ?? '');
    $storeSlug = trim($data['store_slug'] ?? '');
    $couponCode = trim($data['coupon_code'] ?? '');
    
    if (empty($planId) || empty($storeName) || empty($storeSlug)) {
        http_response_code(400);
        die(json_encode(['error' => 'Dados incompletos']));
    }
    
    // Verificar slug
    $stmt = $pdo->prepare("SELECT id FROM stores WHERE slug = ?");
    $stmt->execute([$storeSlug]);
    if ($stmt->fetch()) {
        http_response_code(400);
        die(json_encode(['error' => 'Este slug já está em uso']));
    }
    
    // Planos
    $plans = [
        'starter' => ['name' => 'Starter', 'price' => 14.99],
        'enterprise' => ['name' => 'Enterprise', 'price' => 25.99],
        'gerencial' => ['name' => 'Gerencial', 'price' => 39.99]
    ];
    
    if (!isset($plans[$planId])) {
        http_response_code(400);
        die(json_encode(['error' => 'Plano inválido']));
    }
    
    $plan = $plans[$planId];
    $amount = $plan['price'];
    $discount = 0;
    
    // Aplicar cupom
    if (!empty($couponCode)) {
        $stmt = $pdo->prepare("
            SELECT discount_percent 
            FROM coupons 
            WHERE code = ? AND active = 1
            AND (valid_until IS NULL OR valid_until > NOW())
            AND (max_uses IS NULL OR used_count < max_uses)
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch();
        
        if ($coupon) {
            $discount = $amount * ($coupon['discount_percent'] / 100);
            $amount -= $discount;
            
            $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
            $stmt->execute([$couponCode]);
        }
    }
    
    // Criar pending_store
    $stmt = $pdo->prepare("
        INSERT INTO pending_stores 
        (user_id, store_name, slug, plan_id, amount, discount, coupon_code, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $user['id'],
        $storeName,
        $storeSlug,
        $planId,
        $amount + $discount,
        $discount,
        $couponCode ?: null
    ]);
    $pendingStoreId = $pdo->lastInsertId();
    
    // Criar pagamento MisticPay
    $misticpay = new MisticPay();
    $payment = $misticpay->createPayment([
        'amount' => $amount,
        'description' => "Assinatura {$plan['name']} - SplitStore",
        'plan_name' => "Plano {$plan['name']}",
        'plan_id' => $planId,
        'customer_name' => $user['nome'],
        'customer_email' => $user['email'],
        'store_slug' => $storeSlug,
        'store_name' => $storeName,
        'user_id' => $user['id'],
        'pending_store_id' => $pendingStoreId
    ]);
    
    if (!$payment['success']) {
        $stmt = $pdo->prepare("UPDATE pending_stores SET status = 'failed' WHERE id = ?");
        $stmt->execute([$pendingStoreId]);
        
        http_response_code(500);
        die(json_encode([
            'error' => 'Erro ao criar pagamento',
            'details' => $payment['data']
        ]));
    }
    
    // Extrair dados PIX
    $paymentData = $payment['data'];
    $paymentId = $paymentData['id'] ?? null;
    $pixCode = $paymentData['pix']['qr_code'] ?? $paymentData['pix_code'] ?? null;
    $qrCodeBase64 = $paymentData['pix']['qr_code_image'] ?? $paymentData['qr_code_base64'] ?? null;
    
    // Atualizar com payment_id
    if ($paymentId) {
        $stmt = $pdo->prepare("UPDATE pending_stores SET payment_id = ? WHERE id = ?");
        $stmt->execute([$paymentId, $pendingStoreId]);
    }
    
    echo json_encode([
        'success' => true,
        'payment_id' => $paymentId,
        'pending_store_id' => $pendingStoreId,
        'pix_code' => $pixCode,
        'qr_code_base64' => $qrCodeBase64,
        'amount' => number_format($amount, 2, ',', '.'),
        'plan_name' => "Plano {$plan['name']}",
        'store_name' => $storeName,
        'store_slug' => $storeSlug,
        'expires_in' => 600
    ]);
    
} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar checkout',
        'message' => $e->getMessage()
    ]);
}
COEOF
echo -e "${GREEN}✅ checkout.php criado${NC}"

# 5. AJUSTAR PERMISSÕES
echo "🔐 Ajustando permissões..."
chmod 644 includes/*.php
chmod 644 api/*.php
chmod 777 logs
echo -e "${GREEN}✅ Permissões ajustadas${NC}"

# 6. TESTAR
echo ""
echo "🧪 Testando configuração..."
php -r 'require_once "/www/wwwroot/dashboard.splitstore.com.br/backend/includes/db.php"; echo "✅ db.php OK\n";'
php -r 'require_once "/www/wwwroot/dashboard.splitstore.com.br/backend/includes/misticpay.php"; $mp = new MisticPay(); echo "✅ MisticPay OK\n";'

echo ""
echo "✅ SETUP COMPLETO!"
echo ""
echo "📋 Próximos passos:"
echo "1. Acesse: https://dashboard.splitstore.com.br"
echo "2. Faça login"
echo "3. Tente criar uma loja"
echo ""
echo "📊 Para ver logs:"
echo "tail -f /www/wwwroot/dashboard.splitstore.com.br/backend/logs/php_errors.log"
