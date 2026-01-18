<?php
// auth.splitstore.com.br/backend/test-db.php
// ARQUIVO TEMPORÁRIO PARA TESTAR CONEXÃO

header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'splitstore_auth';
$username = 'splitstore_auth';
$password = 'Hn2FY2823ZWGbAyH';

echo json_encode([
    'test' => 'Iniciando teste de conexão',
    'timestamp' => date('Y-m-d H:i:s')
]) . "\n\n";

try {
    echo "1. Tentando conectar ao MySQL...\n";
    
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "✅ Conexão estabelecida!\n\n";
    
    echo "2. Verificando tabelas...\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas encontradas: " . implode(', ', $tables) . "\n\n";
    
    echo "3. Verificando usuários...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "Total de usuários: {$result['total']}\n\n";
    
    echo "4. Listando usuários...\n";
    $stmt = $pdo->query("SELECT id, nome, email, status, created_at FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    foreach ($users as $user) {
        echo sprintf(
            "ID: %d | Nome: %s | Email: %s | Status: %s | Criado: %s\n",
            $user['id'],
            $user['nome'],
            $user['email'],
            $user['status'],
            $user['created_at']
        );
    }
    
    echo "\n5. Verificando estrutura da tabela users...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo sprintf(
            "- %s (%s) %s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
            $col['Key']
        );
    }
    
    echo "\n✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    
} catch(PDOException $e) {
    echo "❌ ERRO DE CONEXÃO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "\n";
    echo "Verifique:\n";
    echo "1. O MySQL está rodando?\n";
    echo "2. O banco de dados '$dbname' existe?\n";
    echo "3. O usuário '$username' tem permissão?\n";
    echo "4. A senha está correta?\n";
} catch (Exception $e) {
    echo "❌ ERRO GERAL:\n";
    echo $e->getMessage() . "\n";
}