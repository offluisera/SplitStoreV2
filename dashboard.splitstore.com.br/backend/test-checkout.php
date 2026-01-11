<?php
/**
 * SCRIPT DE TESTE DO CHECKOUT
 * dashboard.splitstore.com.br/backend/test-checkout.php
 * 
 * Acesse:          gvfc
 */

header('Content-Type: application/json');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/misticpay.php';

echo json_encode([
    'test' => 'Checkout Test',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
], JSON_PRETTY_PRINT);

$tests = [];

// ========================================
// TESTE 1: Conexão com banco de dados
// ========================================
try {
    $stmt = $pdo->query("SELECT 1");
    $tests['database'] = [
        'status' => 'OK',
        'message' => 'Conexão com banco de dados funcionando'
    ];
} catch (Exception $e) {
    $tests['database'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// ========================================
// TESTE 2: Verificar tabelas
// ========================================
try {
    $tables = ['users', 'sessions', 'stores', 'pending_stores', 'coupons', 'transactions'];
    $existing = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existing[] = $table;
        }
    }
    
    $tests['tables'] = [
        'status' => count($existing) === count($tables) ? 'OK' : 'WARNING',
        'expected' => $tables,
        'existing' => $existing,
        'missing' => array_diff($tables, $existing)
    ];
} catch (Exception $e) {
    $tests['tables'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// ========================================
// TESTE 3: Testar MisticPay (SEM criar pagamento real)
// ========================================
try {
    $misticpay = new MisticPay();
    
    $tests['misticpay'] = [
        'status' => 'OK',
        'message' => 'Classe MisticPay carregada',
        'client_id' => 'ci_6wqrtigx1d8e430',
        'api_url' => 'https://api.misticpay.com/api'
    ];
} catch (Exception $e) {
    $tests['misticpay'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// ========================================
// TESTE 4: Verificar cupons
// ========================================
try {
    $stmt = $pdo->query("SELECT code, discount_percent, active FROM coupons LIMIT 5");
    $coupons = $stmt->fetchAll();
    
    $tests['coupons'] = [
        'status' => 'OK',
        'count' => count($coupons),
        'samples' => $coupons
    ];
} catch (Exception $e) {
    $tests['coupons'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// ========================================
// TESTE 5: Verificar pending_stores
// ========================================
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pending_stores");
    $result = $stmt->fetch();
    
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM pending_stores GROUP BY status");
    $byStatus = $stmt->fetchAll();
    
    $tests['pending_stores'] = [
        'status' => 'OK',
        'total' => $result['total'],
        'by_status' => $byStatus
    ];
} catch (Exception $e) {
    $tests['pending_stores'] = [
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// ========================================
// TESTE 6: Verificar logs de erros
// ========================================
$errorLog = ini_get('error_log');
if (file_exists($errorLog)) {
    $lastErrors = array_slice(file($errorLog), -10);
    $tests['error_log'] = [
        'status' => 'INFO',
        'path' => $errorLog,
        'last_10_lines' => $lastErrors
    ];
} else {
    $tests['error_log'] = [
        'status' => 'WARNING',
        'message' => 'Error log not found or not accessible'
    ];
}

// ========================================
// RESULTADO FINAL
// ========================================
$allOk = true;
foreach ($tests as $test) {
    if ($test['status'] === 'ERROR') {
        $allOk = false;
        break;
    }
}

echo json_encode([
    'overall_status' => $allOk ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => $tests
], JSON_PRETTY_PRINT);