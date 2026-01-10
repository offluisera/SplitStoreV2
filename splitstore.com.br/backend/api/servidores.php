<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$dbFile = __DIR__ . '/../includes/db.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
}

try {
    $servidores = [];
    
    if (isset($pdo)) {
        // Cache com Redis
        $cacheKey = 'servidores_public_v1';
        
        if (isset($redis) && $redis->exists($cacheKey)) {
            $cachedData = json_decode($redis->get($cacheKey), true);
            echo json_encode($cachedData);
            exit;
        }
        
        // Buscar servidores parceiros ativos
        $stmt = $pdo->query("
            SELECT 
                nome,
                sigla,
                logo_url,
                cor as color
            FROM servidores_parceiros 
            WHERE ativo = 1 
            ORDER BY ordem ASC
            LIMIT 10
        ");
        
        $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Salvar em cache por 30 minutos
        if (isset($redis)) {
            $redis->setex($cacheKey, 1800, json_encode($servidores));
        }
    }
    
    echo json_encode($servidores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar servidores',
        'message' => $e->getMessage()
    ]);
}