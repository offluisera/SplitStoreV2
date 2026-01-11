<?php
/**
 * ============================================
 * WEBHOOK MERCADOPAGO
 * ============================================
 * dashboard.splitstore.com.br/backend/webhooks/mercadopago.php
 * 
 * Configure esta URL no painel do MercadoPago:
 * https://dashboard.splitstore.com.br/backend/webhooks/mercadopago.php
 */

header('Content-Type: application/json');

error_log("=== WEBHOOK MERCADOPAGO RECEBIDO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
error_log("Headers: " . json_encode(getallheaders()));

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mercadopago.php';

try {
    $payload = file_get_contents('php://input');
    error_log("Payload recebido: $payload");
    
    if (empty($payload)) {
        error_log("❌ Payload vazio");
        http_response_code(400);
        die(json_encode(['error' => 'Payload vazio']));
    }
    
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON inválido: " . json_last_error_msg());
        http_response_code(400);
        die(json_encode(['error' => 'JSON inválido']));
    }
    
    // MercadoPago envia notificações em diferentes formatos
    // Tipo de notificação
    $type = $data['type'] ?? $_GET['topic'] ?? 'unknown';
    $dataId = $data['data']['id'] ?? $_GET['id'] ?? null;
    
    error_log("Notification Type: $type");
    error_log("Data ID: " . ($dataId ?? 'N/A'));
    
    // Processar apenas notificações de pagamento
    if ($type === 'payment' && $dataId) {
        handlePaymentNotification($pdo, $dataId);
    } else {
        error_log("⚠️ Tipo de notificação não processado: $type");
    }
    
    // Sempre retornar 200 OK para o MercadoPago
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("=== WEBHOOK ERROR ===");
    error_log("Erro: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    // Retornar 200 mesmo com erro para não ficar re-enviando
    http_response_code(200);
    echo json_encode(['error' => 'Erro processado']);
}

/**
 * Processar notificação de pagamento
 */
function handlePaymentNotification($pdo, $paymentId) {
    error_log("=== PROCESSANDO NOTIFICAÇÃO DE PAGAMENTO ===");
    error_log("Payment ID: $paymentId");
    
    $mercadopago = new MercadoPago();
    
    // Buscar informações do pagamento na API
    $paymentInfo = $mercadopago->getPayment($paymentId);
    
    if (!$paymentInfo['success']) {
        error_log("❌ Erro ao buscar pagamento: " . ($paymentInfo['error'] ?? 'Unknown'));
        return;
    }
    
    $payment = $paymentInfo['data'];
    $status = $payment['status'] ?? 'unknown';
    $externalReference = $payment['external_reference'] ?? null;
    
    error_log("Status: $status");
    error_log("External Reference: " . ($externalReference ?? 'N/A'));
    error_log("Full Payment Data: " . json_encode($payment, JSON_PRETTY_PRINT));
    
    if (!$externalReference) {
        error_log("❌ External reference não encontrado");
        return;
    }
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE id = ? OR payment_id = ?
    ");
    $stmt->execute([$externalReference, $paymentId]);
    $pendingStore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pendingStore) {
        error_log("❌ Pending store não encontrada");
        return;
    }
    
    error_log("✅ Pending Store encontrada - ID: {$pendingStore['id']}");
    
    // Processar baseado no status
    switch ($status) {
        case 'approved':
            handlePaymentApproved($pdo, $pendingStore, $payment);
            break;
            
        case 'in_process':
        case 'pending':
            handlePaymentPending($pdo, $pendingStore, $payment);
            break;
            
        case 'rejected':
        case 'cancelled':
            handlePaymentFailed($pdo, $pendingStore, $payment);
            break;
            
        case 'refunded':
        case 'charged_back':
            handlePaymentRefunded($pdo, $pendingStore, $payment);
            break;
            
        default:
            error_log("⚠️ Status não tratado: $status");
    }
}

/**
 * Pagamento aprovado - Criar loja
 */
function handlePaymentApproved($pdo, $pendingStore, $payment) {
    error_log("=== PROCESSANDO PAGAMENTO APROVADO ===");
    
    // Verificar se já foi processado
    if ($pendingStore['status'] === 'completed') {
        error_log("ℹ️ Pagamento já foi processado anteriormente");
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
        
        error_log("✅ Loja criada - ID: $storeId");
        
        // Criar transação
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (store_id, user_id, produto_nome, amount, status, payment_method, transaction_id, created_at)
            VALUES (?, ?, ?, ?, 'completed', ?, ?, NOW())
        ");
        $stmt->execute([
            $storeId,
            $pendingStore['user_id'],
            "Plano " . ucfirst($pendingStore['plan_id']),
            $pendingStore['amount'] - $pendingStore['discount'],
            $pendingStore['payment_method'],
            $payment['id']
        ]);
        
        error_log("✅ Transação criada");
        
        // Atualizar pending_store
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'completed', 
                payment_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['id'], $pendingStore['id']]);
        
        error_log("✅ Pending store atualizada");
        
        $pdo->commit();
        
        error_log("=== LOJA CRIADA COM SUCESSO ===");
        error_log("Store ID: $storeId");
        error_log("Store Slug: {$pendingStore['slug']}");
        
        // TODO: Enviar email de confirmação
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao criar loja: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Pagamento pendente (ex: boleto aguardando pagamento)
 */
function handlePaymentPending($pdo, $pendingStore, $payment) {
    error_log("=== PROCESSANDO PAGAMENTO PENDENTE ===");
    
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'pending',
            payment_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment['id'], $pendingStore['id']]);
    
    error_log("✅ Pending store atualizada para status pending");
}

/**
 * Pagamento falhou
 */
function handlePaymentFailed($pdo, $pendingStore, $payment) {
    error_log("=== PROCESSANDO PAGAMENTO FALHOU ===");
    
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'failed',
            payment_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment['id'], $pendingStore['id']]);
    
    error_log("✅ Pending store marcada como failed");
}

/**
 * Pagamento reembolsado
 */
function handlePaymentRefunded($pdo, $pendingStore, $payment) {
    error_log("=== PROCESSANDO REEMBOLSO ===");
    
    try {
        $pdo->beginTransaction();
        
        // Buscar loja criada
        $stmt = $pdo->prepare("
            SELECT id FROM stores 
            WHERE user_id = ? AND slug = ?
        ");
        $stmt->execute([$pendingStore['user_id'], $pendingStore['slug']]);
        $store = $stmt->fetch();
        
        if ($store) {
            // Suspender loja
            $stmt = $pdo->prepare("
                UPDATE stores 
                SET status = 'suspended', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$store['id']]);
            
            error_log("✅ Loja suspensa - ID: {$store['id']}");
            
            // Atualizar transação
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'refunded', updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$payment['id']]);
        }
        
        // Atualizar pending_store
        $stmt = $pdo->prepare("
            UPDATE pending_stores 
            SET status = 'refunded',
                payment_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$payment['id'], $pendingStore['id']]);
        
        $pdo->commit();
        
        error_log("=== REEMBOLSO PROCESSADO ===");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Erro ao processar reembolso: " . $e->getMessage());
        throw $e;
    }
}
?>