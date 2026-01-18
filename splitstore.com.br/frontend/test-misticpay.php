<?php
// test-misticpay.php
// Execute este arquivo direto no navegador para testar a API MisticPay

header('Content-Type: text/html; charset=utf-8');

$clientId = 'ci_6wqrtigx1d8e430';
$clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';

echo "<h1>🧪 Teste MisticPay API</h1>";
echo "<hr>";

// TESTE 1: URL da API
echo "<h2>1️⃣ Testando URLs da API</h2>";

$urls = [
    'https://api.misticpay.com/api/payments',
    'https://api.misticpay.com/v1/payments',
];

foreach ($urls as $url) {
    echo "<h3>Testando: $url</h3>";
    
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Client-Id: ' . $clientId,
        'X-Client-Secret: ' . $clientSecret
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'amount' => 14.99,
        'currency' => 'BRL',
        'payment_method' => 'pix',
        'description' => 'Teste SplitStore',
        'customer' => [
            'name' => 'Teste Cliente',
            'email' => 'teste@splitstore.com.br'
        ]
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($error) {
        echo "<p style='color: red;'><strong>❌ Erro CURL:</strong> $error</p>";
    } else {
        echo "<p style='color: green;'>✅ Conexão OK</p>";
        echo "<pre>" . htmlspecialchars(print_r(json_decode($response, true), true)) . "</pre>";
    }
    
    echo "<hr>";
}

// TESTE 2: Formatos de autenticação
echo "<h2>2️⃣ Testando Formatos de Autenticação</h2>";

$authFormats = [
    'Formato 1: Headers separados' => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Client-Id: ' . $clientId,
        'X-Client-Secret: ' . $clientSecret
    ],
    'Formato 2: Bearer + Client-Id' => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $clientSecret,
        'X-Client-Id: ' . $clientId
    ],
    'Formato 3: Basic Auth' => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ],
];

$testUrl = 'https://api.misticpay.com/api/payments';

foreach ($authFormats as $name => $headers) {
    echo "<h3>$name</h3>";
    
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'amount' => 14.99,
        'currency' => 'BRL',
        'payment_method' => 'pix',
        'description' => 'Teste Auth SplitStore',
        'customer' => [
            'name' => 'Teste Cliente',
            'email' => 'teste@splitstore.com.br'
        ]
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($httpCode == 200 || $httpCode == 201) {
        echo "<p style='color: green; font-weight: bold;'>✅ ESTE FORMATO FUNCIONA!</p>";
        echo "<pre>" . htmlspecialchars(print_r(json_decode($response, true), true)) . "</pre>";
    } elseif ($httpCode == 401 || $httpCode == 403) {
        echo "<p style='color: red;'>❌ Não autorizado</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Outro erro</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
    echo "<hr>";
}

// TESTE 3: Testar criação de pagamento real
echo "<h2>3️⃣ Criar Pagamento Real (TESTE)</h2>";

$ch = curl_init('https://api.misticpay.com/api/payments');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-Client-Id: ' . $clientId,
    'X-Client-Secret: ' . $clientSecret
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => 0.01, // R$ 0,01 para teste
    'currency' => 'BRL',
    'payment_method' => 'pix',
    'description' => 'Teste REAL SplitStore',
    'customer' => [
        'name' => 'Cliente Teste',
        'email' => 'teste@splitstore.com.br'
    ],
    'metadata' => [
        'test' => true
    ],
    'webhook_url' => 'https://splitstore.com.br/webhooks/misticpay.php',
    'return_url' => 'https://dashboard.splitstore.com.br?status=success'
]));

// Adicionar verbose
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($httpCode == 200 || $httpCode == 201) {
    echo "<p style='color: green; font-weight: bold; font-size: 20px;'>✅ PAGAMENTO CRIADO COM SUCESSO!</p>";
    
    $data = json_decode($response, true);
    echo "<h3>Resposta da API:</h3>";
    echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    
    // Extrair PIX
    echo "<h3>📊 Dados do PIX:</h3>";
    echo "<ul>";
    echo "<li><strong>Payment ID:</strong> " . ($data['id'] ?? 'N/A') . "</li>";
    echo "<li><strong>PIX Code:</strong> " . (isset($data['pix']['qr_code']) ? substr($data['pix']['qr_code'], 0, 50) . '...' : 'N/A') . "</li>";
    echo "<li><strong>QR Code Image:</strong> " . (isset($data['pix']['qr_code_image']) ? 'SIM ✅' : 'NÃO ❌') . "</li>";
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>❌ Erro ao criar pagamento</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h4>CURL Verbose Log:</h4>";
echo "<pre>" . htmlspecialchars($verboseLog) . "</pre>";

echo "<hr>";
echo "<p><strong>💡 Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Verifique qual formato de autenticação funcionou acima</li>";
echo "<li>Atualize o arquivo misticpay.php com o formato correto</li>";
echo "<li>Verifique se a estrutura do PIX está correta</li>";
echo "<li>Configure o webhook em https://splitstore.com.br/webhooks/misticpay.php</li>";
echo "</ol>";