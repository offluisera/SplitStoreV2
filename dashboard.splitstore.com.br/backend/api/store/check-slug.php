<?php
// backend/api/store/check-slug.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

// Extrair slug da URL
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/check-slug\/([^\/]+)/', $uri, $matches);
$slug = $matches[1] ?? '';

if (empty($slug)) {
    http_response_code(400);
    die(json_encode(['error' => 'Slug não fornecido']));
}

try {
    $stmt = $pdo->prepare("SELECT id FROM stores WHERE slug = ?");
    $stmt->execute([$slug]);
    $exists = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'slug' => $slug
    ]);
    
} catch (Exception $e) {
    error_log("Check Slug Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar slug']);
}