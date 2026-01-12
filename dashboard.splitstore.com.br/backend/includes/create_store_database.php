<?php
/**
 * ============================================
 * CRIAR BANCO DE DADOS DA LOJA
 * ============================================
 * dashboard.splitstore.com.br/backend/includes/create_store_database.php
 */

function createStoreDatabase($storeSlug, $storeName) {
    try {
        error_log("=== CRIANDO BANCO DE DADOS DA LOJA ===");
        error_log("Slug: $storeSlug");
        error_log("Nome: $storeName");
        
        // Sanitizar slug para nome do banco
        $dbName = preg_replace('/[^a-z0-9_]/', '_', strtolower($storeSlug));
        $dbName = preg_replace('/_+/', '_', $dbName); // Remove underscores duplos
        $dbName = trim($dbName, '_'); // Remove underscores das pontas
        
        error_log("Nome do banco: $dbName");
        
        // Credenciais do servidor MySQL (root)
        $host = 'localhost';
        $rootUser = 'root';
        $rootPassword = getenv('MYSQL_ROOT_PASSWORD') ?: 'sua_senha_root_aqui';
        
        // Conectar como root para criar banco e usuário
        $pdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $rootUser,
            $rootPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Criar banco de dados
        error_log("Criando banco de dados...");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` 
                    CHARACTER SET utf8mb4 
                    COLLATE utf8mb4_unicode_ci");
        
        // Criar usuário para este banco (mesmo nome do banco)
        $dbUser = $dbName;
        $dbPassword = bin2hex(random_bytes(16)); // Senha aleatória segura
        
        error_log("Criando usuário do banco...");
        
        // Dropar usuário se já existir
        try {
            $pdo->exec("DROP USER IF EXISTS '$dbUser'@'localhost'");
        } catch (Exception $e) {
            // Ignore se não existir
        }
        
        // Criar novo usuário
        $pdo->exec("CREATE USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPassword'");
        
        // Conceder permissões
        $pdo->exec("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUser'@'localhost'");
        $pdo->exec("FLUSH PRIVILEGES");
        
        error_log("✅ Banco e usuário criados com sucesso!");
        
        // Conectar no novo banco para criar as tabelas
        $storePdo = new PDO(
            "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
            $dbUser,
            $dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Ler schema SQL
        $schemaPath = __DIR__ . '/../database/store_schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception("Schema SQL não encontrado: $schemaPath");
        }
        
        $schema = file_get_contents($schemaPath);
        
        // Executar schema (criar tabelas)
        error_log("Criando tabelas...");
        
        // Dividir por ponto e vírgula e executar cada statement
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^(--|\/\*|SET|START|COMMIT)/', $stmt);
            }
        );
        
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    $storePdo->exec($statement);
                } catch (PDOException $e) {
                    error_log("⚠️ Erro ao executar statement: " . $e->getMessage());
                    error_log("Statement: " . substr($statement, 0, 200));
                    // Continuar mesmo com erros (triggers/events podem falhar)
                }
            }
        }
        
        // Inserir configurações iniciais
        error_log("Inserindo configurações iniciais...");
        $stmt = $storePdo->prepare("
            INSERT INTO store_settings (setting_key, setting_value, setting_type) VALUES
            ('store_name', ?, 'text'),
            ('store_slug', ?, 'text'),
            ('primary_color', '#ef4444', 'text'),
            ('maintenance_mode', '0', 'boolean')
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$storeName, $storeSlug]);
        
        error_log("✅ Tabelas criadas com sucesso!");
        
        // Salvar credenciais no banco principal
        require_once __DIR__ . '/db.php'; // Banco principal
        
        $stmt = $pdo->prepare("
            INSERT INTO store_databases 
            (store_slug, database_name, db_user, db_password, db_host, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $storeSlug,
            $dbName,
            $dbUser,
            $dbPassword,
            $host
        ]);
        
        error_log("✅ Credenciais salvas no banco principal!");
        
        return [
            'success' => true,
            'database_name' => $dbName,
            'db_user' => $dbUser,
            'db_host' => $host,
            'message' => 'Banco de dados criado com sucesso'
        ];
        
    } catch (PDOException $e) {
        error_log("❌ Erro ao criar banco de dados: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'Erro ao criar banco de dados: ' . $e->getMessage()
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro geral: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obter conexão com banco da loja
 */
function getStoreConnection($storeSlug) {
    try {
        // Buscar credenciais no banco principal
        require_once __DIR__ . '/db.php';
        
        $stmt = $pdo->prepare("
            SELECT database_name, db_user, db_password, db_host 
            FROM store_databases 
            WHERE store_slug = ?
        ");
        $stmt->execute([$storeSlug]);
        $credentials = $stmt->fetch();
        
        if (!$credentials) {
            throw new Exception("Credenciais do banco não encontradas para loja: $storeSlug");
        }
        
        // Conectar no banco da loja
        $storePdo = new PDO(
            "mysql:host={$credentials['db_host']};dbname={$credentials['database_name']};charset=utf8mb4",
            $credentials['db_user'],
            $credentials['db_password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        return [
            'success' => true,
            'connection' => $storePdo,
            'database_name' => $credentials['database_name']
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro ao conectar com banco da loja: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Deletar banco de dados da loja
 */
function deleteStoreDatabase($storeSlug) {
    try {
        error_log("=== DELETANDO BANCO DE DADOS DA LOJA ===");
        error_log("Slug: $storeSlug");
        
        // Buscar nome do banco
        require_once __DIR__ . '/db.php';
        
        $stmt = $pdo->prepare("
            SELECT database_name, db_user 
            FROM store_databases 
            WHERE store_slug = ?
        ");
        $stmt->execute([$storeSlug]);
        $credentials = $stmt->fetch();
        
        if (!$credentials) {
            throw new Exception("Banco não encontrado: $storeSlug");
        }
        
        $dbName = $credentials['database_name'];
        $dbUser = $credentials['db_user'];
        
        // Conectar como root
        $host = 'localhost';
        $rootUser = 'root';
        $rootPassword = getenv('MYSQL_ROOT_PASSWORD') ?: 'sua_senha_root_aqui';
        
        $rootPdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $rootUser,
            $rootPassword,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Dropar banco
        $rootPdo->exec("DROP DATABASE IF EXISTS `$dbName`");
        
        // Dropar usuário
        $rootPdo->exec("DROP USER IF EXISTS '$dbUser'@'localhost'");
        $rootPdo->exec("FLUSH PRIVILEGES");
        
        // Remover do registro
        $stmt = $pdo->prepare("DELETE FROM store_databases WHERE store_slug = ?");
        $stmt->execute([$storeSlug]);
        
        error_log("✅ Banco deletado com sucesso!");
        
        return [
            'success' => true,
            'message' => 'Banco de dados deletado com sucesso'
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro ao deletar banco: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>