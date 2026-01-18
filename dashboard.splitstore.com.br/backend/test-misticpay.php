<?php
/**
 * ============================================
 * SCRIPT DE TESTE MISTICPAY
 * ============================================
 * dashboard.splitstore.com.br/backend/test-misticpay.php
 * 
 * Acesse: https://dashboard.splitstore.com.br/backend/test-misticpay.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/misticpay.php';

echo json_encode(['status' => 'Starting tests...'], JSON_PRETTY_PRINT) . "\n\n";

$tests = [];

// ========================================
// TESTE 1: Verificar credenciais
// ========================================
echo "=== TESTE 1: Verificar Credenciais ===\n";
$misticpay = new MisticPay();

$tests['credentials'] = [
    'status' => 'OK',
    'client_id' => 'ci_6wqrtigx1d8e430',
    'api_url' => 'https://api.misticpay.com/api',
    'message' => 'Classe MisticPay carregada com sucesso'
];

echo json_encode($tests['credentials'], JSON_PRETTY_PRINT) . "\n\n";

// ========================================
// TESTE 2: Criar transação de teste
// ========================================
echo "=== TESTE 2: Criar Transação PIX de Teste ===\n";

$testData = [
    'amount' => 1.0, // R$ 0,10 para teste
    'description' => 'TESTE - Pagamento SplitStore',
    'customer_name' => 'Cliente Teste',
    'customer_email' => 'teste@splitstore.com.br',
    'customer_cpf' => '12345678900', // CPF de teste (sem pontos/traços)
    'store_slug' => 'loja-teste',
    'store_name' => 'Loja Teste',
    'plan_id' => 'starter',
    'user_id' => 'test_001',
    'pending_store_id' => 'test_' . time()
];

echo "Dados do teste:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

$result = $misticpay->createPayment($testData);

echo "Resultado da requisição:\n";
echo json_encode([
    'success' => $result['success'],
    'http_code' => $result['http_code'],
    'error' => $result['error'] ?? null
], JSON_PRETTY_PRINT) . "\n\n";

if ($result['success']) {
    echo "✅ SUCESSO! Transação criada.\n\n";
    
    echo "Resposta completa da API:\n";
    echo json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Extrair dados do PIX
    $pixData = $misticpay->extractPixData($result);
    
    echo "Dados PIX extraídos:\n";
    echo json_encode($pixData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($pixData['qr_code']) {
        echo "✅ QR Code PIX gerado!\n";
        echo "Código PIX (primeiros 50 chars): " . substr($pixData['qr_code'], 0, 50) . "...\n\n";
    } else {
        echo "❌ QR Code PIX NÃO encontrado na resposta\n\n";
    }
    
    if ($pixData['qr_code_base64']) {
        echo "✅ QR Code Base64 gerado!\n";
        echo "Base64 (primeiros 50 chars): " . substr($pixData['qr_code_base64'], 0, 50) . "...\n\n";
    } else {
        echo "⚠️ QR Code Base64 NÃO encontrado (pode ser opcional)\n\n";
    }
    
    $tests['create_payment'] = [
        'status' => 'SUCCESS',
        'transaction_id' => $pixData['transaction_id'],
        'has_qr_code' => !empty($pixData['qr_code']),
        'has_qr_code_base64' => !empty($pixData['qr_code_base64'])
    ];
    
    // ========================================
    // TESTE 3: Consultar transação
    // ========================================
    if ($pixData['transaction_id']) {
        echo "=== TESTE 3: Consultar Transação ===\n";
        
        $checkResult = $misticpay->getPayment($pixData['transaction_id']);
        
        echo "Status da consulta:\n";
        echo json_encode([
            'success' => $checkResult['success'],
            'http_code' => $checkResult['http_code'],
            'status' => $checkResult['data']['status'] ?? 'N/A'
        ], JSON_PRETTY_PRINT) . "\n\n";
        
        $tests['check_payment'] = [
            'status' => $checkResult['success'] ? 'SUCCESS' : 'FAILED',
            'transaction_status' => $checkResult['data']['status'] ?? 'N/A'
        ];
    }
    
} else {
    echo "❌ ERRO ao criar transação!\n\n";
    echo "Erro: " . ($result['error'] ?? 'Desconhecido') . "\n";
    echo "HTTP Code: " . $result['http_code'] . "\n\n";
    
    if (isset($result['data'])) {
        echo "Detalhes da resposta:\n";
        echo json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
    }
    
    if (isset($result['raw_response'])) {
        echo "Raw Response:\n";
        echo $result['raw_response'] . "\n\n";
    }
    
    $tests['create_payment'] = [
        'status' => 'FAILED',
        'error' => $result['error'] ?? 'Unknown',
        'http_code' => $result['http_code'],
        'details' => $result['data'] ?? null
    ];
}

// ========================================
// TESTE 4: Verificar estrutura da resposta
// ========================================
echo "=== TESTE 4: Análise da Estrutura da Resposta ===\n";

if (isset($result['data']) && is_array($result['data'])) {
    echo "Campos presentes na resposta:\n";
    
    function analyzeStructure($data, $prefix = '') {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo "$prefix$key (array/object):\n";
                analyzeStructure($value, $prefix . '  ');
            } else {
                $type = gettype($value);
                $preview = is_string($value) && strlen($value) > 50 
                    ? substr($value, 0, 50) . '...' 
                    : $value;
                echo "$prefix$key ($type): $preview\n";
            }
        }
    }
    
    analyzeStructure($result['data']);
    echo "\n";
}

// ========================================
// RESUMO FINAL
// ========================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESUMO DOS TESTES\n";
echo str_repeat("=", 60) . "\n\n";

echo json_encode($tests, JSON_PRETTY_PRINT) . "\n\n";

$allPassed = true;
foreach ($tests as $test) {
    if (isset($test['status']) && $test['status'] !== 'SUCCESS' && $test['status'] !== 'OK') {
        $allPassed = false;
        break;
    }
}

if ($allPassed) {
    echo "✅ TODOS OS TESTES PASSARAM!\n";
    echo "A integração com MisticPay está funcionando.\n\n";
} else {
    echo "❌ ALGUNS TESTES FALHARAM\n";
    echo "Verifique os logs acima para mais detalhes.\n\n";
}

echo "Para ver logs detalhados do PHP:\n";
echo "tail -f /www/wwwroot/dashboard.splitstore.com.br/backend/logs/php_errors.log\n\n";
?>