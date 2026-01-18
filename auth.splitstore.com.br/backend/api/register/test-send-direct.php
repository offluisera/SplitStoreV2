<?php
// /www/wwwroot/auth.splitstore.com.br/backend/api/register/test-send-direct.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Direto de Envio</h1>";
echo "<pre>";

// Simular dados
$testData = [
    'nome' => 'Luis',
    'sobrenome' => 'Fernando',
    'telefone' => '35998979351',
    'email' => 'patetagames2013@gmail.com',
    'cpf' => '13367805688'
];

echo "1. Dados de teste:\n";
print_r($testData);
echo "\n";

// Conexão
echo "2. Testando conexão...\n";
$host = 'localhost';
$dbname = 'splitstore_clientes';
$username = 'splitstore_clientes';
$password = 'hRC5kmrhGRSm7CMZ';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✅ Conexão OK\n\n";
} catch(PDOException $e) {
    die("❌ Erro: " . $e->getMessage());
}

// Verificar PHPMailer
echo "3. Verificando PHPMailer...\n";
if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    die("❌ Autoload não encontrado\n");
}

require __DIR__ . '/../../vendor/autoload.php';
echo "✅ Autoload carregado\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "✅ PHPMailer disponível\n\n";

// Verificar config
echo "4. Verificando configuração...\n";
if (file_exists(__DIR__ . '/../../includes/email-config.php')) {
    $emailConfig = require __DIR__ . '/../../includes/email-config.php';
    echo "✅ Config carregada\n";
    echo "   Host: " . $emailConfig['smtp_host'] . "\n";
    echo "   Port: " . $emailConfig['smtp_port'] . "\n\n";
} else {
    die("❌ email-config.php não encontrado\n");
}

// Gerar código
echo "5. Gerando código...\n";
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
echo "✅ Código: $code\n\n";

// Tentar enviar e-mail
echo "6. Tentando enviar e-mail...\n";
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $emailConfig['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $emailConfig['smtp_username'];
    $mail->Password = $emailConfig['smtp_password'];
    $mail->Port = $emailConfig['smtp_port'];
    
    if (!empty($emailConfig['smtp_secure'])) {
        $mail->SMTPSecure = $emailConfig['smtp_secure'];
    }
    
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
    $mail->addAddress($testData['email'], $testData['nome'] . ' ' . $testData['sobrenome']);
    
    $mail->isHTML(true);
    $mail->Subject = 'Teste de Código - SplitStore';
    $mail->Body = "<h1>Seu código: <strong>$code</strong></h1>";
    
    $mail->send();
    echo "✅ E-mail enviado com sucesso!\n\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao enviar: " . $e->getMessage() . "\n";
    echo "ErrorInfo: " . $mail->ErrorInfo . "\n\n";
}

echo "7. Teste completo!\n";
echo "</pre>";

echo "<hr>";
echo "<h2>Agora teste o endpoint real:</h2>";
echo "<button onclick='testAPI()'>Testar API</button>";
echo "<div id='result'></div>";

echo "<script>
function testAPI() {
    fetch('/api/register/send-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nome: 'Luis',
            sobrenome: 'Fernando',
            telefone: '35998979351',
            email: 'patelagames2013@gmail.com',
            cpf: '13367805688'
        })
    })
    .then(response => response.text())
    .then(text => {
        document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
    })
    .catch(error => {
        document.getElementById('result').innerHTML = '<pre style=\"color:red\">' + error + '</pre>';
    });
}
</script>";