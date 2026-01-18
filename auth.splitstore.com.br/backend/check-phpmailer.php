<?php
// /www/wwwroot/auth.splitstore.com.br/backend/check-phpmailer.php

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Verificação do PHPMailer</h1>";
echo "<pre style='background: #000; color: #0f0; padding: 20px; font-family: monospace;'>";

// 1. Verificar autoload
echo "1. Verificando autoload...\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "✅ Arquivo autoload.php existe: $autoloadPath\n\n";
    require $autoloadPath;
} else {
    echo "❌ Arquivo autoload.php NÃO EXISTE: $autoloadPath\n";
    echo "   SOLUÇÃO: Execute 'composer require phpmailer/phpmailer'\n\n";
}

// 2. Verificar pasta vendor
echo "2. Verificando pasta vendor...\n";
$vendorPath = __DIR__ . '/vendor';
if (is_dir($vendorPath)) {
    echo "✅ Pasta vendor existe\n";
    
    $phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
    if (is_dir($phpmailerPath)) {
        echo "✅ PHPMailer instalado em: $phpmailerPath\n\n";
    } else {
        echo "❌ PHPMailer NÃO está instalado\n";
        echo "   Pasta esperada: $phpmailerPath\n\n";
    }
} else {
    echo "❌ Pasta vendor NÃO existe\n\n";
}

// 3. Listar conteúdo da pasta vendor
echo "3. Conteúdo da pasta vendor:\n";
if (is_dir($vendorPath)) {
    $contents = scandir($vendorPath);
    foreach ($contents as $item) {
        if ($item != '.' && $item != '..') {
            echo "   - $item\n";
        }
    }
    echo "\n";
} else {
    echo "   (pasta não existe)\n\n";
}

// 4. Tentar carregar PHPMailer
echo "4. Tentando carregar PHPMailer...\n";
try {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $version = PHPMailer\PHPMailer\PHPMailer::VERSION;
        echo "✅ PHPMailer carregado com sucesso!\n";
        echo "✅ Versão: $version\n\n";
    } else {
        echo "❌ Classe PHPMailer não encontrada\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao carregar: " . $e->getMessage() . "\n\n";
}

// 5. Verificar email-config.php
echo "5. Verificando email-config.php...\n";
$configPath = __DIR__ . '/includes/email-config.php';
if (file_exists($configPath)) {
    echo "✅ Arquivo existe: $configPath\n";
    $config = require $configPath;
    echo "✅ Configurações carregadas:\n";
    echo "   - Host: " . ($config['smtp_host'] ?? 'N/A') . "\n";
    echo "   - Port: " . ($config['smtp_port'] ?? 'N/A') . "\n";
    echo "   - Username: " . ($config['smtp_username'] ?? 'N/A') . "\n";
    echo "   - Password: " . (isset($config['smtp_password']) ? str_repeat('*', strlen($config['smtp_password'])) : 'N/A') . "\n\n";
} else {
    echo "❌ Arquivo NÃO existe: $configPath\n";
    echo "   SOLUÇÃO: Crie o arquivo email-config.php\n\n";
}

// 6. Verificar permissões
echo "6. Verificando permissões...\n";
$perms = substr(sprintf('%o', fileperms(__DIR__)), -4);
echo "   Permissões da pasta backend: $perms\n";
if (is_dir($vendorPath)) {
    $vendorPerms = substr(sprintf('%o', fileperms($vendorPath)), -4);
    echo "   Permissões da pasta vendor: $vendorPerms\n";
}
echo "\n";

// 7. Verificar Composer
echo "7. Verificando Composer...\n";
exec('which composer', $output, $return);
if ($return === 0) {
    echo "✅ Composer instalado: " . $output[0] . "\n";
    exec('composer --version', $composerVersion);
    echo "✅ Versão: " . $composerVersion[0] . "\n\n";
} else {
    echo "❌ Composer NÃO está instalado\n";
    echo "   SOLUÇÃO: Instale o Composer primeiro\n\n";
}

// 8. Comandos para corrigir
echo "========================================\n";
echo "🔧 COMANDOS PARA CORRIGIR:\n";
echo "========================================\n\n";

if (!is_dir($vendorPath) || !is_dir($phpmailerPath)) {
    echo "# Instalar PHPMailer:\n";
    echo "cd /www/wwwroot/auth.splitstore.com.br/backend\n";
    echo "composer require phpmailer/phpmailer\n\n";
}

if (!file_exists($configPath)) {
    echo "# Criar email-config.php:\n";
    echo "nano /www/wwwroot/auth.splitstore.com.br/backend/includes/email-config.php\n\n";
}

echo "# Ajustar permissões:\n";
echo "cd /www/wwwroot/auth.splitstore.com.br\n";
echo "chmod -R 755 backend/\n";
echo "chown -R www:www backend/\n\n";

echo "</pre>";