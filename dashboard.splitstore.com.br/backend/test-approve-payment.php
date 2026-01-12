<?php
/**
 * ============================================
 * TESTE - SIMULAR APROVAÇÃO DE PAGAMENTO
 * ============================================
 * dashboard.splitstore.com.br/backend/test-approve-payment.php
 * 
 * Acesse: https://dashboard.splitstore.com.br/backend/test-approve-payment.php?payment_id=SEU_PAYMENT_ID
 */

header('Content-Type: application/json');

require_once __DIR__ . '/includes/db.php';

// Pegar payment_id da URL
$paymentId = $_GET['payment_id'] ?? null;

if (!$paymentId) {
    die(json_encode([
        'error' => 'Informe o payment_id na URL',
        'exemplo' => 'test-approve-payment.php?payment_id=53'
    ]));
}

try {
    error_log("=== TESTE DE APROVAÇÃO DE PAGAMENTO ===");
    error_log("Payment ID: $paymentId");
    
    // Buscar pending_store
    $stmt = $pdo->prepare("
        SELECT * FROM pending_stores 
        WHERE payment_id = ? OR id = ?
    ");
    $stmt->execute([$paymentId, $paymentId]);
    $pendingStore = $stmt->fetch();
    
    if (!$pendingStore) {
        die(json_encode([
            'error' => 'Pending store não encontrada',
            'payment_id' => $paymentId
        ]));
    }
    
    error_log("Pending Store encontrada - ID: {$pendingStore['id']}");
    error_log("Status atual: {$pendingStore['status']}");
    
    // Verificar se já foi processada
    if ($pendingStore['status'] === 'completed') {
        die(json_encode([
            'error' => 'Este pagamento já foi aprovado anteriormente',
            'pending_store' => $pendingStore
        ]));
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // 1. Criar a loja
    error_log("Criando loja...");
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
    
    // 2. Criar transação
    error_log("Criando transação...");
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
        $paymentId
    ]);
    
    error_log("✅ Transação criada");
    
    // 3. Atualizar pending_store
    error_log("Atualizando pending_store...");
    $stmt = $pdo->prepare("
        UPDATE pending_stores 
        SET status = 'completed', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$pendingStore['id']]);
    
    error_log("✅ Pending store atualizada");
    
    // 4. Criar banco de dados da loja (se o arquivo existir)
    $createDbFile = __DIR__ . '/includes/create_store_database.php';
    if (file_exists($createDbFile)) {
        error_log("Criando banco de dados da loja...");
        require_once $createDbFile;
        
        $dbResult = createStoreDatabase(
            $pendingStore['slug'],
            $pendingStore['store_name']
        );
        
        if ($dbResult['success']) {
            error_log("✅ Banco de dados criado: {$dbResult['database_name']}");
        } else {
            error_log("⚠️ Erro ao criar banco: {$dbResult['error']}");
        }
    }
    
    $pdo->commit();
    
    error_log("=== PAGAMENTO APROVADO COM SUCESSO ===");
    
    echo json_encode([
        'success' => true,
        'message' => 'Pagamento aprovado com sucesso! ✅',
        'store' => [
            'id' => $storeId,
            'name' => $pendingStore['store_name'],
            'slug' => $pendingStore['slug'],
            'plan' => $pendingStore['plan_id']
        ],
        'next_step' => 'Agora acesse a página de checkout e o status deve atualizar automaticamente',
        'test_url' => "https://dashboard.splitstore.com.br/backend/api/checkout/check-payment/{$paymentId}"
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("❌ Erro no banco de dados: " . $e->getMessage());
    
    echo json_encode([
        'error' => 'Erro no banco de dados',
        'message' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("❌ Erro: " . $e->getMessage());
    
    echo json_encode([
        'error' => 'Erro ao processar',
        'message' => $e->getMessage()
    ]);
}
?>