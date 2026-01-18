<?php
/**
 * ============================================
 * TESTE - SIMULAR WEBHOOK DA MISTICPAY
 * ============================================
 * dashboard.splitstore.com.br/backend/test-webhook-call.php
 * 
 * Acesse: https://dashboard.splitstore.com.br/backend/test-webhook-call.php?transaction_id=65
 */

$transactionId = $_GET['transaction_id'] ?? null;

if (!$transactionId) {
    die('❌ Informe transaction_id na URL: ?transaction_id=65');
}

echo "<h1>🧪 Teste de Webhook MisticPay</h1>";
echo "<p>Transaction ID: <strong>$transactionId</strong></p>";
echo "<hr>";

// Simular payload da MisticPay
$payload = [
    'transactionId' => $transactionId,
    'transactionState' => 'PAGO',
    'transactionAmount' => 14.99,
    'payer' => [
        'name' => 'LUIS FERNANDO BORDIGNON DA SILVA',
        'document' => '50.729.756'
    ]
];

echo "<h3>📤 Payload que será enviado:</h3>";
echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// Fazer requisição para o webhook
$ch = curl_init('https://dashboard.splitstore.com.br/backend/webhooks/misticpay.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

echo "<h3>📥 Chamando webhook...</h3>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<h3>📨 Resposta do webhook:</h3>";
echo "<pre>$response</pre>";
echo "<hr>";

// Verificar se funcionou
require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM pending_stores WHERE payment_id = ? OR id = ?");
$stmt->execute([$transactionId, $transactionId]);
$pendingStore = $stmt->fetch();

if ($pendingStore) {
    echo "<h3>✅ Status no Banco de Dados:</h3>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$pendingStore['id']}</li>";
    echo "<li><strong>Status:</strong> <span style='color: " . ($pendingStore['status'] === 'completed' ? 'green' : 'orange') . "'>{$pendingStore['status']}</span></li>";
    echo "<li><strong>Loja:</strong> {$pendingStore['store_name']}</li>";
    echo "<li><strong>Slug:</strong> {$pendingStore['slug']}</li>";
    echo "</ul>";
    
    if ($pendingStore['status'] === 'completed') {
        // Buscar loja criada
        $stmt = $pdo->prepare("SELECT * FROM stores WHERE slug = ?");
        $stmt->execute([$pendingStore['slug']]);
        $store = $stmt->fetch();
        
        if ($store) {
            echo "<h3>🎉 Loja Criada com Sucesso!</h3>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> {$store['id']}</li>";
            echo "<li><strong>Nome:</strong> {$store['nome']}</li>";
            echo "<li><strong>Plano:</strong> {$store['plano']}</li>";
            echo "<li><strong>URL:</strong> <a href='https://{$store['slug']}.splitstore.com.br' target='_blank'>https://{$store['slug']}.splitstore.com.br</a></li>";
            echo "</ul>";
            
            echo "<div style='background: #10b981; color: white; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
            echo "<h2>✅ SUCESSO TOTAL!</h2>";
            echo "<p>O webhook foi processado corretamente e a loja foi criada!</p>";
            echo "<p><strong>Próximo passo:</strong> Volte para a tela do QR Code que ela deve detectar automaticamente.</p>";
            echo "</div>";
        }
    }
} else {
    echo "<h3>❌ Pending Store não encontrada</h3>";
}

echo "<hr>";
echo "<h3>📋 Ver Logs:</h3>";
echo "<p><a href='/backend/logs/webhook_misticpay.log' target='_blank'>Ver arquivo de log</a></p>";
?>