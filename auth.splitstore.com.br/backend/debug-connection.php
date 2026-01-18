<?php
// backend/debug-connection.php
// Arquivo temporário para testar conexão

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo "=== TESTE DE CONEXÃO ===\n\n";

// Teste 1: Verificar extensões PHP
echo "1. Extensões PHP:\n";
echo "- PDO: " . (extension_loaded('pdo') ? '✅ OK' : '❌ FALTANDO') . "\n";
echo "- PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ OK' : '❌ FALTANDO') . "\n";
echo "- MySQLi: " . (extension_loaded('mysqli') ? '✅ OK' : '❌ FALTANDO') . "\n\n";

// Teste 2: Configurações
$host = 'localhost';
$dbname = 'splitstore_clientes';
$username = 'splitstore_clientes';
$password = 'hRC5kmrhGRSm7CMZ';

echo "2. Configurações:\n";
echo "- Host: $host\n";
echo "- Database: $dbname\n";
echo "- Username: $username\n";
echo "- Password: " . (strlen($password) > 0 ? str_repeat('*', strlen($password)) : 'VAZIA') . "\n\n";

// Teste 3: Tentar conexão PDO
echo "3. Testando PDO:\n";
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
    echo "✅ CONEXÃO PDO: SUCESSO!\n\n";
    
    // Teste 4: Verificar tabelas
    echo "4. Tabelas no banco:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // Teste 5: Testar query
    echo "5. Testando query na tabela users:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "✅ Total de usuários: " . $result['total'] . "\n\n";
    
    // Teste 6: Testar query na tabela verification_codes
    echo "6. Testando query na tabela verification_codes:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM verification_codes");
    $result = $stmt->fetch();
    echo "✅ Total de códigos: " . $result['total'] . "\n\n";
    
    echo "=== ✅ TODOS OS TESTES PASSARAM! ===\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO PDO:\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    // Sugestões de correção
    echo "=== POSSÍVEIS SOLUÇÕES ===\n";
    
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "❌ ERRO DE AUTENTICAÇÃO\n";
        echo "Soluções:\n";
        echo "1. Verifique usuário e senha no MySQL\n";
        echo "2. Execute: SHOW GRANTS FOR '$username'@'localhost';\n";
        echo "3. Crie o usuário se não existir:\n";
        echo "   CREATE USER '$username'@'localhost' IDENTIFIED BY 'sua_senha';\n";
        echo "   GRANT ALL PRIVILEGES ON $dbname.* TO '$username'@'localhost';\n";
        echo "   FLUSH PRIVILEGES;\n\n";
    }
    
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "❌ BANCO DE DADOS NÃO ENCONTRADO\n";
        echo "Soluções:\n";
        echo "1. Crie o banco: CREATE DATABASE $dbname;\n";
        echo "2. Verifique se o nome está correto\n\n";
    }
    
    if (strpos($e->getMessage(), "Can't connect") !== false) {
        echo "❌ NÃO CONSEGUE CONECTAR AO MYSQL\n";
        echo "Soluções:\n";
        echo "1. Verifique se MySQL está rodando: sudo systemctl status mysql\n";
        echo "2. Inicie o MySQL: sudo systemctl start mysql\n";
        echo "3. Verifique o socket: ls -la /var/run/mysqld/mysqld.sock\n\n";
    }
}

// Teste 7: Verificar arquivo db.php
echo "7. Verificando arquivo db.php:\n";
$dbFile = __DIR__ . '/includes/db.php';
if (file_exists($dbFile)) {
    echo "✅ Arquivo existe: $dbFile\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($dbFile)), -4) . "\n";
    echo "Dono: " . posix_getpwuid(fileowner($dbFile))['name'] . "\n";
} else {
    echo "❌ Arquivo NÃO existe: $dbFile\n";
    echo "Crie o arquivo com:\n";
    echo "nano $dbFile\n";
}
echo "\n";

// Informações do servidor
echo "8. Informações do Servidor:\n";
echo "- PHP Version: " . phpversion() . "\n";
echo "- OS: " . php_uname() . "\n";
echo "- Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";