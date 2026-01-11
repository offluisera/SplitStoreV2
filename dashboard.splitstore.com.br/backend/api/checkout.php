<?php
/**
 * ============================================
 * CHECKOUT API - MULTI GATEWAY (PIX + CARTÃO + BOLETO)
 * ============================================
 * dashboard.splitstore.com.br/backend/api/checkout.php
 */

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
require_once __DIR__ . '/../includes/mercadopago.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método não permitido']));
}

try {
    error_log("=== CHECKOUT REQUEST INICIADO ===");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    error_log("Dados recebidos: " . json_encode($data));
    
    // Validar token do usuário
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (empty($token)) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Token não fornecido']));
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
        error_log("Token inválido ou expirado");
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Sessão inválida']));
    }
    
    error_log("Usuário autenticado: {$user['nome']} (ID: {$user['id']})");
    
    // Validar dados obrigatórios
    $planId = $data['plan_id'] ?? '';
    $storeName = trim($data['store_name'] ?? '');
    $storeSlug = trim($data['store_slug'] ?? '');
    $customerCpf = preg_replace('/[^0-9]/', '', $data['customer_cpf'] ?? '');
    $couponCode = trim($data['coupon_code'] ?? '');
    $paymentMethod = $data['payment_method'] ?? 'pix'; // pix, credit_card, debit_card, boleto
    
    error_log("Payment Method recebido: $paymentMethod");
    
    if (empty($planId) || empty($storeName) || empty($storeSlug) || empty($customerCpf)) {
        error_log("Dados incompletos - Plan: $planId, Store: $storeName, Slug: $storeSlug, CPF: $customerCpf");
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Dados incompletos']));
    }
    
    // Validar CPF (11 dígitos)
    if (strlen($customerCpf) !== 11) {
        error_log("CPF inválido: $customerCpf");
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'CPF inválido']));
    }
    
    // Verificar se slug já existe
    $stmt = $pdo->prepare("SELECT id FROM stores WHERE slug = ?");
    $stmt->execute([$storeSlug]);
    if ($stmt->fetch()) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Este slug já está em uso']));
    }
    
    // Buscar informações do plano
    $plans = [
        'starter' => ['name' => 'Starter', 'price' => 14.99],
        'enterprise' => ['name' => 'Enterprise', 'price' => 25.99],
        'gerencial' => ['name' => 'Gerencial', 'price' => 39.99]
    ];
    
    if (!isset($plans[$planId])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Plano inválido']));
    }
    
    $plan = $plans[$planId];
    $amount = $plan['price'];
    $discount = 0;
    
    // Aplicar cupom se fornecido
    if (!empty($couponCode)) {
        $stmt = $pdo->prepare("
            SELECT discount_percent 
            FROM coupons 
            WHERE code = ? 
            AND active = 1
            AND (valid_until IS NULL OR valid_until > NOW())
            AND (max_uses IS NULL OR used_count < max_uses)
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch();
        
        if ($coupon) {
            $discount = $amount * ($coupon['discount_percent'] / 100);
            $amount -= $discount;
            
            error_log("Cupom aplicado: $couponCode - Desconto: R$ $discount");
            
            // Incrementar uso do cupom
            $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
            $stmt->execute([$couponCode]);
        }
    }
    
    error_log("Plano: {$plan['name']} - Valor final: R$ $amount");
    error_log("Método de pagamento: $paymentMethod");
    
    // Criar registro de pending_store
    $stmt = $pdo->prepare("
        INSERT INTO pending_stores 
        (user_id, store_name, slug, plan_id, amount, discount, coupon_code, payment_method, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $user['id'],
        $storeName,
        $storeSlug,
        $planId,
        $amount + $discount,
        $discount,
        $couponCode ?: null,
        $paymentMethod
    ]);
    $pendingStoreId = $pdo->lastInsertId();
    
    error_log("Pending Store criada - ID: $pendingStoreId");
    
    // ============================================
    // PROCESSAR PAGAMENTO BASEADO NO MÉTODO
    // ============================================
    
    $response = null;
    
    if ($paymentMethod === 'pix') {
        error_log("Processando PIX...");
        
        // Usar MisticPay para PIX
        $misticpay = new MisticPay();
        $payment = $misticpay->createPayment([
            'amount' => $amount,
            'description' => "Assinatura {$plan['name']} - SplitStore",
            'plan_name' => "Plano {$plan['name']}",
            'plan_id' => $planId,
            'customer_name' => $user['nome'],
            'customer_email' => $user['email'],
            'customer_cpf' => $customerCpf,
            'store_slug' => $storeSlug,
            'store_name' => $storeName,
            'user_id' => $user['id'],
            'pending_store_id' => $pendingStoreId
        ]);
        
        if (!$payment['success']) {
            throw new Exception('Erro ao criar pagamento PIX: ' . ($payment['error'] ?? 'Erro desconhecido'));
        }
        
        $pixData = $misticpay->extractPixData($payment);
        
        $stmt = $pdo->prepare("UPDATE pending_stores SET payment_id = ? WHERE id = ?");
        $stmt->execute([$pixData['transaction_id'], $pendingStoreId]);
        
        $response = [
            'success' => true,
            'payment_method' => 'pix',
            'payment_id' => $pixData['transaction_id'],
            'pending_store_id' => $pendingStoreId,
            'pix_code' => $pixData['qr_code'],
            'qr_code_base64' => $pixData['qr_code_base64'],
            'amount' => number_format($amount, 2, ',', '.'),
            'plan_name' => "Plano {$plan['name']}",
            'store_name' => $storeName,
            'store_slug' => $storeSlug,
            'expires_in' => 600
        ];
        
    } elseif (in_array($paymentMethod, ['credit_card', 'debit_card', 'boleto'])) {
        error_log("Processando MercadoPago - Método: $paymentMethod");
        
        // Usar MercadoPago para Cartão/Boleto
        $mercadopago = new MercadoPago();
        
        // Criar preferência de pagamento
        $preference = $mercadopago->createPreference([
            'amount' => $amount,
            'description' => "Assinatura {$plan['name']} - SplitStore",
            'plan_name' => "Plano {$plan['name']}",
            'plan_id' => $planId,
            'customer_name' => $user['nome'],
            'customer_email' => $user['email'],
            'customer_cpf' => $customerCpf,
            'store_slug' => $storeSlug,
            'store_name' => $storeName,
            'user_id' => $user['id'],
            'pending_store_id' => $pendingStoreId
        ]);
        
        error_log("MercadoPago Response: " . json_encode($preference));
        
        if (!$preference['success']) {
            error_log("❌ Erro MercadoPago: " . ($preference['error'] ?? 'Unknown'));
            throw new Exception('Erro ao criar checkout MercadoPago: ' . ($preference['error'] ?? 'Erro desconhecido'));
        }
        
        $preferenceId = $preference['data']['id'] ?? null;
        $initPoint = $preference['data']['init_point'] ?? null;
        
        if (!$preferenceId || !$initPoint) {
            error_log("❌ Dados incompletos do MercadoPago - Preference ID: $preferenceId, Init Point: $initPoint");
            throw new Exception('Resposta incompleta do MercadoPago');
        }
        
        $stmt = $pdo->prepare("UPDATE pending_stores SET payment_id = ? WHERE id = ?");
        $stmt->execute([$preferenceId, $pendingStoreId]);
        
        error_log("✅ Checkout MercadoPago criado - Preference ID: $preferenceId");
        
        $response = [
            'success' => true,
            'payment_method' => $paymentMethod,
            'preference_id' => $preferenceId,
            'init_point' => $initPoint,
            'pending_store_id' => $pendingStoreId,
            'amount' => number_format($amount, 2, ',', '.'),
            'plan_name' => "Plano {$plan['name']}",
            'store_name' => $storeName,
            'store_slug' => $storeSlug,
            'public_key' => $mercadopago->getPublicKey()
        ];
        
    } else {
        throw new Exception('Método de pagamento inválido: ' . $paymentMethod);
    }
    
    error_log("=== CHECKOUT SUCCESS ===");
    error_log("Response: " . json_encode($response));
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("=== CHECKOUT DATABASE ERROR ===");
    error_log("Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de banco de dados',
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("=== CHECKOUT ERROR ===");
    error_log("Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>