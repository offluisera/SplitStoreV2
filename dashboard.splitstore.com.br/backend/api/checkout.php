<?php
// dashboard.splitstore.com.br/backend/api/checkout.php
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
    error_log("=== CHECKOUT REQUEST INICIADO ===");
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar token do usuário
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
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
        error_log("Token inválido ou expirado");
        http_response_code(401);
        die(json_encode(['error' => 'Sessão inválida']));
    }
    
    error_log("Usuário autenticado: {$user['nome']} (ID: {$user['id']})");
    
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
    
    error_log("Plano: {$plan['name']} - Valor: R$ $amount");
    
    // Criar registro de pending_store
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
    
    error_log("Pending Store criada - ID: $pendingStoreId");
    
    // Criar pagamento na MisticPay
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
        error_log("Erro ao criar pagamento na MisticPay: " . json_encode($payment));
        
        // Marcar pending_store como failed
        $stmt = $pdo->prepare("UPDATE pending_stores SET status = 'failed' WHERE id = ?");
        $stmt->execute([$pendingStoreId]);
        
        http_response_code(500);
        die(json_encode([
            'error' => 'Erro ao criar pagamento',
            'details' => $payment['data'] ?? null
        ]));
    }
    
    // Atualizar pending_store com payment_id
    $paymentId = $payment['data']['id'] ?? null;
    $checkoutUrl = $payment['data']['checkout_url'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE pending_stores SET payment_id = ? WHERE id = ?");
    $stmt->execute([$paymentId, $pendingStoreId]);
    
    error_log("Pagamento criado - ID: $paymentId");
    error_log("Checkout URL: $checkoutUrl");
    
    echo json_encode([
        'success' => true,
        'payment_url' => $checkoutUrl,
        'payment_id' => $paymentId,
        'pending_store_id' => $pendingStoreId,
        'message' => 'Checkout criado com sucesso!'
    ]);
    
} catch (Exception $e) {
    error_log("=== CHECKOUT ERROR ===");
    error_log("Erro: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar checkout',
        'message' => $e->getMessage()
    ]);
}