<?php
// backend/webhooks/misticpay.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/misticpay.php';

try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_MISTICPAY_SIGNATURE'] ?? '';
    
    // Log do webhook recebido
    error_log("MisticPay Webhook Received: " . $payload);
    
    // Verificar assinatura
    $misticpay = new MisticPay();
    if (!$misticpay->verifyWebhookSignature($payload, $signature)) {
        http_response_code(401);
        die(json_encode(['error' => 'Assinatura inválida']));
    }
    
    $data = json_decode($payload, true);
    $event = $data['event'] ?? '';
    $payment = $data['data'] ?? [];
    
    error_log("Processing event: " . $event);
    
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
            
        default:
            error_log("Unknown event type: " . $event);
    }
    
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar webhook']);
}

function handlePaymentApproved($pdo, $payment) {
    $paymentId = $payment['id'] ?? null;
    $metadata = $payment['metadata'] ?? [];
    
    if (!$paymentId) {
        error_log("Payment ID not found in webhook");
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
        error_log("Pending store not found for payment: " . $paymentId);
        return;
    }
    
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
        
        // Atualizar pending_store
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'completed', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$pendingStore['id']]);
        
        $pdo->commit();
        
        error_log("Store created successfully: " . $storeId);
        
        // TODO: Enviar email de boas-vindas
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error creating store: " . $e->getMessage());
        throw $e;
    }
}

function handlePaymentFailed($pdo, $payment) {
    $paymentId = $payment['id'] ?? null;
    
    if (!$paymentId) return;
    
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'failed', updated_at = NOW()
        WHERE payment_id = ?
    ");
    $stmt->execute([$paymentId]);
    
    error_log("Payment failed: " . $paymentId);
    
    // TODO: Enviar email de falha no pagamento
}

function handlePaymentRefunded($pdo, $payment) {
    $paymentId = $payment['id'] ?? null;
    
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
            
            // Atualizar transação
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'refunded', updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$paymentId]);
        }
        
        // Atualizar pending_store se existir
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'cancelled', updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        
        $pdo->commit();
        
        error_log("Payment refunded: " . $paymentId);
        
        // TODO: Enviar email de reembolso
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error handling refund: " . $e->getMessage());
        throw $e;
    }
}