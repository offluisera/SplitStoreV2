<?php
// dashboard.splitstore.com.br/backend/api/checkout/check-payment.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/misticpay.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

try {
    // Validar token do usuário
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (empty($token)) {
        http_response_code(401);
        die(json_encode(['error' => 'Token não fornecido']));
    }
    
    // Buscar usuário pelo token
    $stmt = $pdo->prepare("
        SELECT u.id 
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
    
    // Extrair payment_id da URL
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('/\/check-payment\/([^\/\?]+)/', $uri, $matches);
    $paymentId = $matches[1] ?? '';
    
    if (empty($paymentId)) {
        http_response_code(400);
        die(json_encode(['error' => 'Payment ID não fornecido']));
    }
    
    error_log("=== VERIFICANDO STATUS DO PAGAMENTO ===");
    error_log("Payment ID: $paymentId");
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE payment_id = ? AND user_id = ?
    ");
    $stmt->execute([$paymentId, $user['id']]);
    $pendingStore = $stmt->fetch();
    
    if (!$pendingStore) {
        http_response_code(404);
        die(json_encode(['error' => 'Pagamento não encontrado']));
    }
    
    // Se já está completo, retornar sucesso
    if ($pendingStore['status'] === 'completed') {
        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'message' => 'Pagamento já aprovado'
        ]);
        exit;
    }
    
    // Consultar status na MisticPay
    $misticpay = new MisticPay();
    $paymentStatus = $misticpay->getPayment($paymentId);
    
    if (!$paymentStatus['success']) {
        error_log("Erro ao consultar pagamento: " . json_encode($paymentStatus));
        echo json_encode([
            'success' => true,
            'status' => 'pending',
            'message' => 'Aguardando pagamento'
        ]);
        exit;
    }
    
    $status = $paymentStatus['data']['status'] ?? 'pending';
    
    error_log("Status do pagamento: $status");
    
    echo json_encode([
        'success' => true,
        'status' => $status,
        'payment_id' => $paymentId,
        'pending_store_id' => $pendingStore['id']
    ]);
    
} catch (Exception $e) {
    error_log("=== CHECK PAYMENT ERROR ===");
    error_log("Erro: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao verificar pagamento',
        'message' => $e->getMessage()
    ]);
}