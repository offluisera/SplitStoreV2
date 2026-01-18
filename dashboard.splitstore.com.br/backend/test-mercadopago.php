<?php
// backend/test-mercadopago.php
require_once __DIR__ . '/includes/mercadopago.php';

header('Content-Type: application/json');

$mp = new MercadoPago();

echo "=== TESTE MERCADOPAGO ===\n\n";

// Teste 1: Obter métodos de pagamento
echo "1. Obtendo métodos de pagamento...\n";
$methods = $mp->getPaymentMethods();
echo "Status: " . ($methods['success'] ? 'OK ✅' : 'ERRO ❌') . "\n";
echo "HTTP Code: " . $methods['http_code'] . "\n\n";

// Teste 2: Criar preferência de teste
echo "2. Criando preferência de teste...\n";
$preference = $mp->createPreference([
    'amount' => 0.10,
    'description' => 'TESTE - Assinatura SplitStore',
    'plan_name' => 'Teste',
    'plan_id' => 'starter',
    'customer_name' => 'Teste',
    'customer_email' => 'teste@splitstore.com.br',
    'customer_cpf' => '12345678900',
    'store_slug' => 'loja-teste',
    'store_name' => 'Loja Teste',
    'user_id' => 1,
    'pending_store_id' => 999
]);

echo "Status: " . ($preference['success'] ? 'OK ✅' : 'ERRO ❌') . "\n";
echo "HTTP Code: " . $preference['http_code'] . "\n";

if ($preference['success']) {
    echo "Preference ID: " . $preference['data']['id'] . "\n";
    echo "Init Point: " . $preference['data']['init_point'] . "\n";
}

echo "\n=== FIM DOS TESTES ===\n";
?>