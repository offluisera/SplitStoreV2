<?php
// dashboard.splitstore.com.br/backend/webhooks/misticpay.php
header('Content-Type: application/json');

// Log de todas as requisições
error_log("=== WEBHOOK MISTICPAY RECEBIDO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
error_log("Headers: " . json_encode(getallheaders()));

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/misticpay.php';

try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_MISTICPAY_SIGNATURE'] ?? '';
    
    error_log("Payload recebido: $payload");
    error_log("Signature: $signature");
    
    if (empty($payload)) {
        error_log("❌ Payload vazio");
        http_response_code(400);
        die(json_encode(['error' => 'Payload vazio']));
    }
    
    // Verificar assinatura (se fornecida)
    $misticpay = new MisticPay();
    if ($signature && !$misticpay->verifyWebhookSignature($payload, $signature)) {
        error_log("❌ Assinatura inválida!");
        http_response_code(401);
        die(json_encode(['error' => 'Assinatura inválida']));
    }
    
    if ($signature) {
        error_log("✅ Assinatura válida");
    } else {
        error_log("⚠️ Webhook sem assinatura - processando mesmo assim");
    }
    
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON inválido: " . json_last_error_msg());
        http_response_code(400);
        die(json_encode(['error' => 'JSON inválido']));
    }
    
    $event = $data['event'] ?? $data['type'] ?? 'unknown';
    $payment = $data['data'] ?? $data['payment'] ?? $data;
    
    error_log("Event: $event");
    error_log("Payment ID: " . ($payment['id'] ?? 'N/A'));
    error_log("Payment Data: " . json_encode($payment));
    
    switch ($event) {
        case 'payment.approved':
        case 'payment.succeeded':
        case 'payment.completed':
        case 'payment.paid':
            handlePaymentApproved($pdo, $payment);
            break;
            
        case 'payment.failed':
        case 'payment.cancelled':
        case 'payment.expired':
            handlePaymentFailed($pdo, $payment);
            break;
            
        case 'payment.refunded':
            handlePaymentRefunded($pdo, $payment);
            break;
            
        default:
            error_log("⚠️ Evento não tratado: $event");
    }
    
    echo json_encode([
        'status' => 'received',
        'event' => $event,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("=== WEBHOOK ERROR ===");
    error_log("Erro: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao processar webhook',
        'message' => $e->getMessage()
    ]);
}

/**
 * Pagamento aprovado - Criar loja
 */
function handlePaymentApproved($pdo, $payment) {
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    $metadata = $payment['metadata'] ?? [];
    
    error_log("=== PROCESSANDO PAGAMENTO APROVADO ===");
    error_log("Payment ID: $paymentId");
    
    if (!$paymentId) {
        error_log("❌ Payment ID não encontrado");
        return;
    }
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE payment_id = ? AND status = 'pending'
    ");
    $stmt->execute([$paymentId]);
    $pendingStore = $stmt->fetch();
    
    if (!$pendingStore) {
        error_log("❌ Pending store não encontrada para payment: $paymentId");
        return;
    }
    
    error_log("✅ Pending Store encontrada - ID: {$pendingStore['id']}");
    
    try {
        $pdo->beginTransaction();
        
        // Criar a loja
        $stmt = $pdo->prepare("
            INSERT INTO stores (user_id, nome, slug, plano, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([
            $pendingStore['user_id'],
            $pendingStore['store_name'],
            $pendingStore['slug'],
            $pendingStore['plan_id']
        ]);
        $storeId = $pdo->lastInsertId();
        
        error_log("✅ Loja criada - ID: $storeId");
        
        // Criar transação
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (store_id, user_id, produto_nome, amount, status, payment_method, transaction_id, created_at)
            VALUES (?, ?, ?, ?, 'completed', 'pix', ?, NOW())
        ");
        $stmt->execute([
            $storeId,
            $pendingStore['user_id'],
            "Plano " . ucfirst($pendingStore['plan_id']),
            $pendingStore['amount'] - $pendingStore['discount'],
            $paymentId
        ]);
        
        error_log("✅ Transação criada");
        
        // Atualizar pending_store
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'completed', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$pendingStore['id']]);
        
        error_log("✅ Pending store atualizada");
        
        $pdo->commit();
        
        error_log("=== LOJA CRIADA COM SUCESSO ===");
        error_log("Store ID: $storeId");
        error_log("Store Slug: {$pendingStore['slug']}");
        error_log("User ID: {$pendingStore['user_id']}");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao criar loja: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Pagamento falhou
 */
function handlePaymentFailed($pdo, $payment) {
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    
    error_log("=== PROCESSANDO PAGAMENTO FALHOU ===");
    error_log("Payment ID: $paymentId");
    
    if (!$paymentId) return;
    
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'failed', updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([$paymentId]);
    
    error_log("✅ Pending store marcada como failed");
}

/**
 * Pagamento reembolsado
 */
function handlePaymentRefunded($pdo, $payment) {
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    
    error_log("=== PROCESSANDO REEMBOLSO ===");
    error_log("Payment ID: $paymentId");
    
    if (!$paymentId) return;
    
    try {
        $pdo->beginTransaction();
        
        // Buscar transação
        $stmt = $pdo->prepare("
            SELECT store_id FROM transactions 
            WHERE transaction_id = ?
        ");
        $stmt->execute([$paymentId]);
        $transaction = $stmt->fetch();
        
        if ($transaction) {
            // Desativar loja
            $stmt = $pdo->prepare("
                UPDATE stores 
                SET status = 'suspended', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transaction['store_id']]);
            
            error_log("✅ Loja suspensa - ID: {$transaction['store_id']}");
            
            // Atualizar transação
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'refunded', updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$paymentId]);
            
            error_log("✅ Transação marcada como refunded");
        }
        
        // Atualizar pending_store se existir
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'refunded', updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        
        $pdo->commit();
        
        error_log("=== REEMBOLSO PROCESSADO ===");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao processar reembolso: " . $e->getMessage());
        throw $e;
    }
}