<?php
/**
 * ============================================
 * WEBHOOK MISTICPAY - VERSÃO ROBUSTA
 * ============================================
 * dashboard.splitstore.com.br/backend/webhooks/misticpay.php
 */

// Log TUDO que chegar
$logFile = __DIR__ . '/../logs/webhook_misticpay.log';
$timestamp = date('Y-m-d H:i:s');

file_put_contents($logFile, "\n=== WEBHOOK RECEBIDO: $timestamp ===\n", FILE_APPEND);
file_put_contents($logFile, "Headers: " . json_encode(getallheaders()) . "\n", FILE_APPEND);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/misticpay.php';

try {
    $payload = file_get_contents('php://input');
    
    file_put_contents($logFile, "Payload RAW: $payload\n", FILE_APPEND);
    
    if (empty($payload)) {
        file_put_contents($logFile, "❌ Payload vazio\n", FILE_APPEND);
        
        // Aceitar mesmo assim (MisticPay pode enviar vazio)
        http_response_code(200);
        echo json_encode(['status' => 'received', 'message' => 'Payload vazio, mas aceito']);
        exit;
    }
    
    $data = json_decode($payload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents($logFile, "❌ JSON inválido: " . json_last_error_msg() . "\n", FILE_APPEND);
        http_response_code(200); // Aceitar mesmo assim
        echo json_encode(['status' => 'received', 'error' => 'JSON inválido']);
        exit;
    }
    
    file_put_contents($logFile, "Data parsed: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    
    // Extrair transaction_id de várias formas possíveis
    $transactionId = $data['transactionId'] 
                  ?? $data['transaction_id'] 
                  ?? $data['id'] 
                  ?? $data['data']['transactionId']
                  ?? $data['data']['id']
                  ?? null;
    
    file_put_contents($logFile, "Transaction ID: $transactionId\n", FILE_APPEND);
    
    if (!$transactionId) {
        file_put_contents($logFile, "❌ Transaction ID não encontrado\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'received', 'warning' => 'No transaction ID']);
        exit;
    }
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE payment_id = ? OR id = ?
    ");
    $stmt->execute([$transactionId, $transactionId]);
    $pendingStore = $stmt->fetch();
    
    if (!$pendingStore) {
        file_put_contents($logFile, "⚠️ Pending store não encontrada para transaction: $transactionId\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'received', 'warning' => 'Pending store not found']);
        exit;
    }
    
    file_put_contents($logFile, "✅ Pending Store encontrada - ID: {$pendingStore['id']}\n", FILE_APPEND);
    
    // Verificar se já foi processada
    if ($pendingStore['status'] === 'completed') {
        file_put_contents($logFile, "ℹ️ Já foi processada anteriormente\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(['status' => 'received', 'info' => 'Already processed']);
        exit;
    }
    
    // Extrair status de várias formas
    $status = $data['transactionState'] 
           ?? $data['status'] 
           ?? $data['data']['transactionState']
           ?? $data['data']['status']
           ?? 'PENDENTE';
    
    file_put_contents($logFile, "Status recebido: $status\n", FILE_APPEND);
    
    // Verificar se foi aprovado (vários status possíveis)
    $approvedStatuses = ['PAGO', 'CONCLUIDO', 'APROVADO', 'COMPLETO', 'paid', 'approved', 'completed'];
    
    if (in_array(strtoupper($status), array_map('strtoupper', $approvedStatuses))) {
        file_put_contents($logFile, "🎉 PAGAMENTO APROVADO!\n", FILE_APPEND);
        
        // Processar aprovação
        $pdo->beginTransaction();
        
        try {
            // 1. Criar loja
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
            
            file_put_contents($logFile, "✅ Loja criada - ID: $storeId\n", FILE_APPEND);
            
            // 2. Criar transação
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
                $transactionId
            ]);
            
            file_put_contents($logFile, "✅ Transação criada\n", FILE_APPEND);
            
            // 3. Atualizar pending_store
            $stmt = $pdo->prepare("
                UPDATE pending_stores 
                SET status = 'completed', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$pendingStore['id']]);
            
            file_put_contents($logFile, "✅ Pending store atualizada\n", FILE_APPEND);
            
            // 4. Criar banco de dados da loja
            $createDbFile = __DIR__ . '/../includes/create_store_database.php';
            if (file_exists($createDbFile)) {
                require_once $createDbFile;
                
                $dbResult = createStoreDatabase(
                    $pendingStore['slug'],
                    $pendingStore['store_name']
                );
                
                if ($dbResult['success']) {
                    file_put_contents($logFile, "✅ Banco criado: {$dbResult['database_name']}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "⚠️ Erro ao criar banco: {$dbResult['error']}\n", FILE_APPEND);
                }
            }
            
            $pdo->commit();
            
            file_put_contents($logFile, "=== PROCESSAMENTO COMPLETO! ===\n", FILE_APPEND);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Pagamento processado com sucesso',
                'store_id' => $storeId
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            file_put_contents($logFile, "❌ ERRO: " . $e->getMessage() . "\n", FILE_APPEND);
            
            http_response_code(200); // Aceitar mesmo com erro
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        file_put_contents($logFile, "ℹ️ Status não é aprovado: $status\n", FILE_APPEND);
        
        http_response_code(200);
        echo json_encode(['status' => 'received', 'payment_status' => $status]);
    }
    
} catch (Exception $e) {
    file_put_contents($logFile, "❌ ERRO GERAL: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Stack: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    
    http_response_code(200);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>