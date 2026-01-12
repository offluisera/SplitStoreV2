<?php
/**
 * ============================================
 * CHECK PAYMENT STATUS - CORRIGIDO
 * ============================================
 * dashboard.splitstore.com.br/backend/api/checkout/check-payment.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método não permitido']));
}

try {
    error_log("=== CHECK PAYMENT STATUS ===");
    error_log("Timestamp: " . date('Y-m-d H:i:s'));
    
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
    
    // Extrair payment_id da URL
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('/\/check-payment\/([^\/\?]+)/', $uri, $matches);
    $paymentId = $matches[1] ?? '';
    
    if (empty($paymentId)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Payment ID não fornecido']));
    }
    
    error_log("Payment ID: $paymentId");
    error_log("User ID: {$user['id']}");
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE (payment_id = ? OR id = ?) AND user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$paymentId, $paymentId, $user['id']]);
    $pendingStore = $stmt->fetch();
    
    if (!$pendingStore) {
        error_log("❌ Pending store não encontrada");
        http_response_code(404);
        die(json_encode([
            'success' => false,
            'error' => 'Pagamento não encontrado',
            'status' => 'not_found'
        ]));
    }
    
    error_log("Pending Store ID: {$pendingStore['id']}");
    error_log("Status atual: {$pendingStore['status']}");
    
    // Se já está completo, retornar sucesso imediatamente
    if ($pendingStore['status'] === 'completed') {
        error_log("✅ Pagamento JÁ APROVADO");
        
        // Buscar dados da loja criada
        $stmt = $pdo->prepare("
            SELECT id, nome, slug, plano 
            FROM stores 
            WHERE user_id = ? AND slug = ?
        ");
        $stmt->execute([$user['id'], $pendingStore['slug']]);
        $store = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'message' => 'Pagamento aprovado!',
            'store' => [
                'id' => $store['id'] ?? null,
                'name' => $pendingStore['store_name'],
                'slug' => $pendingStore['slug'],
                'plan' => $pendingStore['plan_id']
            ],
            'payment_data' => [
                'payment_id' => $paymentId,
                'amount' => number_format($pendingStore['amount'] - $pendingStore['discount'], 2, ',', '.'),
                'plan_name' => 'Plano ' . ucfirst($pendingStore['plan_id'])
            ]
        ]);
        exit;
    }
    
    // Se está pendente, tentar verificar com a API de pagamento
    if ($pendingStore['status'] === 'pending') {
        error_log("Status PENDING - Verificando com API de pagamento...");
        
        // Tentar verificar com MisticPay se for PIX
        if ($pendingStore['payment_method'] === 'pix' && $pendingStore['payment_gateway'] === 'misticpay') {
            require_once __DIR__ . '/../../includes/misticpay.php';
            
            $misticpay = new MisticPay();
            $paymentStatus = $misticpay->getPayment($paymentId);
            
            error_log("MisticPay Response: " . json_encode($paymentStatus));
            
            // Verificar se foi aprovado
            $status = $paymentStatus['data']['status'] ?? 'pending';
            
            if (in_array($status, ['paid', 'approved', 'completed'])) {
                error_log("🎉 PAGAMENTO APROVADO NA API!");
                
                // Processar aprovação
                require_once __DIR__ . '/../../webhooks/misticpay.php';
                
                // Chamar função de aprovação diretamente
                handlePaymentApproved($pdo, $paymentId, $paymentStatus['data']);
                
                // Buscar loja criada
                $stmt = $pdo->prepare("
                    SELECT id, nome, slug, plano 
                    FROM stores 
                    WHERE user_id = ? AND slug = ?
                ");
                $stmt->execute([$user['id'], $pendingStore['slug']]);
                $store = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'status' => 'approved',
                    'message' => 'Pagamento aprovado com sucesso!',
                    'store' => [
                        'id' => $store['id'] ?? null,
                        'name' => $pendingStore['store_name'],
                        'slug' => $pendingStore['slug'],
                        'plan' => $pendingStore['plan_id']
                    ],
                    'payment_data' => [
                        'payment_id' => $paymentId,
                        'amount' => number_format($pendingStore['amount'] - $pendingStore['discount'], 2, ',', '.'),
                        'plan_name' => 'Plano ' . ucfirst($pendingStore['plan_id'])
                    ]
                ]);
                exit;
            }
        }
    }
    
    // Ainda pendente
    error_log("⏳ Ainda PENDING");
    
    echo json_encode([
        'success' => true,
        'status' => 'pending',
        'message' => 'Aguardando confirmação do pagamento',
        'payment_id' => $paymentId,
        'pending_store_id' => $pendingStore['id']
    ]);
    
} catch (PDOException $e) {
    error_log("=== CHECK PAYMENT DATABASE ERROR ===");
    error_log("Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro de banco de dados',
        'details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    error_log("=== CHECK PAYMENT ERROR ===");
    error_log("Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao verificar pagamento',
        'message' => $e->getMessage()
    ]);
}
?>