<?php
// splitstore.com.br/webhooks/misticpay.php
// ⚠️ Este arquivo deve estar em splitstore.com.br/webhooks/misticpay.php
header('Content-Type: application/json');

// Log de todas as requisições
$logFile = __DIR__ . '/../logs/webhook_misticpay.log';
$timestamp = date('Y-m-d H:i:s');

file_put_contents($logFile, "\n=== WEBHOOK MISTICPAY RECEBIDO ===" . PHP_EOL, FILE_APPEND);
file_put_contents($logFile, "Timestamp: $timestamp" . PHP_EOL, FILE_APPEND);
file_put_contents($logFile, "Method: " . $_SERVER['REQUEST_METHOD'] . PHP_EOL, FILE_APPEND);
file_put_contents($logFile, "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . PHP_EOL, FILE_APPEND);
file_put_contents($logFile, "Headers: " . json_encode(getallheaders()) . PHP_EOL, FILE_APPEND);

// ✅ Importar do dashboard (ajuste o caminho se necessário)
require_once __DIR__ . '/../dashboard/backend/includes/db.php';
require_once __DIR__ . '/../dashboard/backend/includes/misticpay.php';

try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_MISTICPAY_SIGNATURE'] ?? '';
    
    file_put_contents($logFile, "Payload recebido: $payload" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Signature: $signature" . PHP_EOL, FILE_APPEND);
    
    if (empty($payload)) {
        file_put_contents($logFile, "❌ Payload vazio" . PHP_EOL, FILE_APPEND);
        http_response_code(400);
        die(json_encode(['error' => 'Payload vazio']));
    }
    
    // Verificar assinatura (se fornecida)
    $misticpay = new MisticPay();
    if ($signature && !$misticpay->verifyWebhookSignature($payload, $signature)) {
        file_put_contents($logFile, "❌ Assinatura inválida!" . PHP_EOL, FILE_APPEND);
        http_response_code(401);
        die(json_encode(['error' => 'Assinatura inválida']));
    }
    
    if ($signature) {
        file_put_contents($logFile, "✅ Assinatura válida" . PHP_EOL, FILE_APPEND);
    } else {
        file_put_contents($logFile, "⚠️ Webhook sem assinatura - processando mesmo assim" . PHP_EOL, FILE_APPEND);
    }
    
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents($logFile, "❌ JSON inválido: " . json_last_error_msg() . PHP_EOL, FILE_APPEND);
        http_response_code(400);
        die(json_encode(['error' => 'JSON inválido']));
    }
    
    $event = $data['event'] ?? $data['type'] ?? 'unknown';
    $payment = $data['data'] ?? $data['payment'] ?? $data;
    
    file_put_contents($logFile, "Event: $event" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Payment ID: " . ($payment['id'] ?? 'N/A') . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Payment Data: " . json_encode($payment) . PHP_EOL, FILE_APPEND);
    
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
            file_put_contents($logFile, "⚠️ Evento não tratado: $event" . PHP_EOL, FILE_APPEND);
    }
    
    echo json_encode([
        'status' => 'received',
        'event' => $event,
        'timestamp' => $timestamp
    ]);
    
} catch (Exception $e) {
    file_put_contents($logFile, "=== WEBHOOK ERROR ===" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Erro: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Stack: " . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
    
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
    global $logFile;
    
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    
    file_put_contents($logFile, "=== PROCESSANDO PAGAMENTO APROVADO ===" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Payment ID: $paymentId" . PHP_EOL, FILE_APPEND);
    
    if (!$paymentId) {
        file_put_contents($logFile, "❌ Payment ID não encontrado" . PHP_EOL, FILE_APPEND);
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
        file_put_contents($logFile, "❌ Pending store não encontrada para payment: $paymentId" . PHP_EOL, FILE_APPEND);
        return;
    }
    
    file_put_contents($logFile, "✅ Pending Store encontrada - ID: {$pendingStore['id']}" . PHP_EOL, FILE_APPEND);
    
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
        
        file_put_contents($logFile, "✅ Loja criada - ID: $storeId" . PHP_EOL, FILE_APPEND);
        
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
        
        file_put_contents($logFile, "✅ Transação criada" . PHP_EOL, FILE_APPEND);
        
        // Atualizar pending_store
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'completed', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$pendingStore['id']]);
        
        file_put_contents($logFile, "✅ Pending store atualizada" . PHP_EOL, FILE_APPEND);
        
        $pdo->commit();
        
        file_put_contents($logFile, "=== LOJA CRIADA COM SUCESSO ===" . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, "Store ID: $storeId" . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, "Store Slug: {$pendingStore['slug']}" . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, "User ID: {$pendingStore['user_id']}" . PHP_EOL, FILE_APPEND);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents($logFile, "❌ Erro ao criar loja: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        throw $e;
    }
}

/**
 * Pagamento falhou
 */
function handlePaymentFailed($pdo, $payment) {
    global $logFile;
    
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    
    file_put_contents($logFile, "=== PROCESSANDO PAGAMENTO FALHOU ===" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Payment ID: $paymentId" . PHP_EOL, FILE_APPEND);
    
    if (!$paymentId) return;
    
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'failed', updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([$paymentId]);
    
    file_put_contents($logFile, "✅ Pending store marcada como failed" . PHP_EOL, FILE_APPEND);
}

/**
 * Pagamento reembolsado
 */
function handlePaymentRefunded($pdo, $payment) {
    global $logFile;
    
    $paymentId = $payment['id'] ?? $payment['transaction_id'] ?? null;
    
    file_put_contents($logFile, "=== PROCESSANDO REEMBOLSO ===" . PHP_EOL, FILE_APPEND);
    file_put_contents($logFile, "Payment ID: $paymentId" . PHP_EOL, FILE_APPEND);
    
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
            
            file_put_contents($logFile, "✅ Loja suspensa - ID: {$transaction['store_id']}" . PHP_EOL, FILE_APPEND);
            
            // Atualizar transação
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'refunded', updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$paymentId]);
            
            file_put_contents($logFile, "✅ Transação marcada como refunded" . PHP_EOL, FILE_APPEND);
        }
        
        // Atualizar pending_store se existir
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'refunded', updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        
        $pdo->commit();
        
        file_put_contents($logFile, "=== REEMBOLSO PROCESSADO ===" . PHP_EOL, FILE_APPEND);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        file_put_contents($logFile, "❌ Erro ao processar reembolso: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        throw $e;
    }
}