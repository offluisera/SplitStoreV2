<?php
// backend/includes/misticpay.php

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com/v1';
    
    public function createPayment($data) {
        $payload = [
            'amount' => $data['amount'],
            'currency' => 'BRL',
            'customer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'document' => $data['customer_document'] ?? null
            ],
            'items' => [
                [
                    'name' => $data['plan_name'],
                    'quantity' => 1,
                    'unit_price' => $data['amount']
                ]
            ],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'user_id' => $data['user_id']
            ],
            'callback_url' => 'https://splitstore.com.br/webhooks/misticpay.php'
        ];
        
        return $this->makeRequest('POST', '/payments', $payload);
    }
    
    public function getPayment($paymentId) {
        return $this->makeRequest('GET', "/payments/{$paymentId}");
    }
    
    public function cancelPayment($paymentId) {
        return $this->makeRequest('POST', "/payments/{$paymentId}/cancel");
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $ch = curl_init($this->apiUrl . $endpoint);
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
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
                'error' => $error
            ];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $result
        ];
    }
    
    public function verifyWebhookSignature($payload, $signature) {
        $expectedSignature = hash_hmac('sha256', $payload, $this->clientSecret);
        return hash_equals($expectedSignature, $signature);
    }
}

// backend/api/checkout.php
<?php
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
    
    // Validar token do usuário
    $token = $data['token'] ?? '';
    if (empty($token)) {
        http_response_code(401);
        die(json_encode(['error' => 'Token não fornecido']));
    }
    
    // Buscar usuário pelo token
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
    
    // Verificar se slug já existe
    $stmt = $pdo->prepare("SELECT id FROM stores WHERE slug = ?");
    $stmt->execute([$storeSlug]);
    if ($stmt->fetch()) {
        http_response_code(400);
        die(json_encode(['error' => 'Este slug já está em uso']));
    }
    
    // Buscar informações do plano
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
    
    // Aplicar cupom se fornecido
    if (!empty($couponCode)) {
        $stmt = $pdo->prepare("
            SELECT discount_type, discount_value 
            FROM coupons 
            WHERE code = ? AND status = 'active' 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch();
        
        if ($coupon) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount = $amount * ($coupon['discount_value'] / 100);
            } else {
                $discount = $coupon['discount_value'];
            }
            $amount -= $discount;
        }
    }
    
    // Criar loja pendente
    $stmt = $pdo->prepare("
        INSERT INTO stores (user_id, nome, slug, plano, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$user['id'], $storeName, $storeSlug, $planId]);
    $storeId = $pdo->lastInsertId();
    
    // Criar pagamento na MisticPay
    $misticpay = new MisticPay();
    $payment = $misticpay->createPayment([
        'amount' => $amount,
        'plan_name' => "Plano {$plan['name']} - SplitStore",
        'plan_id' => $planId,
        'customer_name' => $user['nome'],
        'customer_email' => $user['email'],
        'store_slug' => $storeSlug,
        'user_id' => $user['id']
    ]);
    
    if (!$payment['success']) {
        // Reverter criação da loja
        $stmt = $pdo->prepare("DELETE FROM stores WHERE id = ?");
        $stmt->execute([$storeId]);
        
        http_response_code(500);
        die(json_encode(['error' => 'Erro ao criar pagamento']));
    }
    
    // Salvar transação
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            store_id, user_id, produto_nome, amount, status, 
            payment_method, transaction_id, created_at
        ) VALUES (?, ?, ?, ?, 'pending', 'misticpay', ?, NOW())
    ");
    $stmt->execute([
        $storeId,
        $user['id'],
        "Plano {$plan['name']}",
        $amount,
        $payment['data']['id'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'payment_url' => $payment['data']['checkout_url'] ?? null,
        'payment_id' => $payment['data']['id'] ?? null,
        'store_id' => $storeId
    ]);
    
} catch (Exception $e) {
    error_log("Checkout Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar checkout']);
}

// backend/webhooks/misticpay.php
<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/misticpay.php';

try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_MISTICPAY_SIGNATURE'] ?? '';
    
    // Verificar assinatura
    $misticpay = new MisticPay();
    if (!$misticpay->verifyWebhookSignature($payload, $signature)) {
        http_response_code(401);
        die(json_encode(['error' => 'Assinatura inválida']));
    }
    
    $data = json_decode($payload, true);
    $event = $data['event'] ?? '';
    $payment = $data['data'] ?? [];
    
    // Log do webhook
    error_log("MisticPay Webhook: " . json_encode($data));
    
    switch ($event) {
        case 'payment.approved':
            handlePaymentApproved($pdo, $payment);
            break;
            
        case 'payment.failed':
        case 'payment.cancelled':
            handlePaymentFailed($pdo, $payment);
            break;
            
        case 'payment.refunded':
            handlePaymentRefunded($pdo, $payment);
            break;
    }
    
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar webhook']);
}

function handlePaymentApproved($pdo, $payment) {
    $transactionId = $payment['id'] ?? null;
    $metadata = $payment['metadata'] ?? [];
    
    if (!$transactionId) return;
    
    // Atualizar transação
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'completed', updated_at = NOW()
        WHERE transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    
    // Ativar loja
    $storeId = $metadata['store_id'] ?? null;
    $planId = $metadata['plan_id'] ?? 'starter';
    
    if ($storeId) {
        $stmt = $pdo->prepare("
            UPDATE stores 
            SET status = 'active', plano = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$planId, $storeId]);
    }
    
    // TODO: Enviar email de boas-vindas
}

function handlePaymentFailed($pdo, $payment) {
    $transactionId = $payment['id'] ?? null;
    
    if (!$transactionId) return;
    
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'cancelled', updated_at = NOW()
        WHERE transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    
    // TODO: Enviar email de falha no pagamento
}

function handlePaymentRefunded($pdo, $payment) {
    $transactionId = $payment['id'] ?? null;
    
    if (!$transactionId) return;
    
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET status = 'refunded', updated_at = NOW()
        WHERE transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    
    // Desativar loja
    $stmt = $pdo->prepare("
        UPDATE stores s
        JOIN transactions t ON s.id = t.store_id
        SET s.status = 'suspended'
        WHERE t.transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    
    // TODO: Enviar email de reembolso
}
?>