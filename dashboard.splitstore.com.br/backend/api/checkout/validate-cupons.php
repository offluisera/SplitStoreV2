<?php
// backend/api/checkout/validate-coupon.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Método não permitido']));
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = strtoupper(trim($data['code'] ?? ''));
    
    if (empty($code)) {
        http_response_code(400);
        die(json_encode(['error' => 'Código do cupom não fornecido']));
    }
    
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND active = 1
        AND (valid_until IS NULL OR valid_until > NOW())
        AND (max_uses IS NULL OR used_count < max_uses)
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        http_response_code(400);
        die(json_encode([
            'valid' => false,
            'error' => 'Cupom inválido ou expirado'
        ]));
    }
    
    echo json_encode([
        'valid' => true,
        'code' => $coupon['code'],
        'discount_type' => 'percentage',
        'discount_value' => (float) $coupon['discount_percent']
    ]);
    
} catch (Exception $e) {
    error_log("Validate Coupon Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao validar cupom']);
}