<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir conexão com banco (se existir)
$dbFile = __DIR__ . '/../includes/db.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
}

try {
    $stats = [
        'lojas_ativas' => 0,
        'faturamento_total' => 0,
        'uptime' => 99.9,
        'total_clientes' => 0
    ];
    
    // Se tiver conexão com banco, buscar dados reais
    if (isset($pdo)) {
        // Cache com Redis (se disponível)
        $cacheKey = 'stats_public_v1';
        
        if (isset($redis) && $redis->exists($cacheKey)) {
            $cachedData = json_decode($redis->get($cacheKey), true);
            echo json_encode($cachedData);
            exit;
        }
        
        // Buscar do banco
        // Total de lojas ativas
        $stmt = $pdo->query("SELECT COUNT(*) FROM stores WHERE status = 'active'");
        $stats['lojas_ativas'] = (int)$stmt->fetchColumn();
        
        // Total de clientes
        $stmt = $pdo->query("SELECT COUNT(*) FROM stores");
        $stats['total_clientes'] = (int)$stmt->fetchColumn();
        
        // Faturamento total processado
        $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed'");
        $stats['faturamento_total'] = (float)$stmt->fetchColumn();
        
        // Uptime (pode ser dinâmico de um sistema de monitoramento)
        $stats['uptime'] = 99.9;
        
        // Salvar em cache por 5 minutos
        if (isset($redis)) {
            $redis->setex($cacheKey, 300, json_encode($stats));
        }
    } else {
        // Valores padrão se não tiver banco
        $stats = [
            'lojas_ativas' => 250,
            'faturamento_total' => 2000000,
            'uptime' => 99.9,
            'total_clientes' => 300
        ];
    }
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar estatísticas',
        'message' => $e->getMessage()
    ]);
}