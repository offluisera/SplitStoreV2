<?php
// backend/api/plans.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$plans = [
    [
        'id' => 'starter',
        'name' => 'STARTER',
        'description' => 'Perfeito para começar',
        'price' => '14,99',
        'price_numeric' => 14.99,
        'features' => [
            '1 Servidor Minecraft',
            'Checkout Responsivo',
            'Suporte via Ticket',
            'Plugin de Entrega',
            'Painel Administrativo',
            'Relatórios Básicos'
        ],
        'limits' => [
            'servers' => 1,
            'products' => 25,
            'categories' => 5
        ]
    ],
    [
        'id' => 'enterprise',
        'name' => 'ENTERPRISE',
        'description' => 'Para redes sérias',
        'price' => '25,99',
        'price_numeric' => 25.99,
        'features' => [
            '5 Servidores',
            'Checkout Customizável',
            'Suporte Prioritário 24/7',
            'Analytics Avançado',
            'API de Integração',
            'Automação de Marketing',
            'Relatórios Detalhados'
        ],
        'highlight' => '🔥 Mais escolhido pelos profissionais',
        'isPopular' => true,
        'limits' => [
            'servers' => 5,
            'products' => 100,
            'categories' => 20
        ]
    ],
    [
        'id' => 'gerencial',
        'name' => 'GERENCIAL',
        'description' => 'Soluções enterprise',
        'price' => '39,99',
        'price_numeric' => 39.99,
        'features' => [
            'Servidores Ilimitados',
            'Whitelabel Completo',
            'Gerente de Contas',
            'Integrações Custom',
            'SLA Garantido',
            'Suporte Dedicado',
            'Consultoria Mensal'
        ],
        'limits' => [
            'servers' => -1, // ilimitado
            'products' => -1,
            'categories' => -1
        ]
    ]
];

echo json_encode([
    'success' => true,
    'plans' => $plans
]);

// backend/api/store.php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// GET /api/store/check-slug/{slug}
if ($method === 'GET' && preg_match('/\/check-slug\/(.+)$/', $uri, $matches)) {
    $slug = $matches[1];
    
    $stmt = $pdo->prepare("SELECT id FROM stores WHERE slug = ?");
    $stmt->execute([$slug]);
    $exists = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'available' => !$exists,
        'slug' => $slug
    ]);
    exit;
}

// GET /api/store/my-store
if ($method === 'GET' && strpos($uri, '/my-store') !== false) {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        http_response_code(401);
        die(json_encode(['error' => 'Token não fornecido']));
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, u.nome as owner_name, u.email as owner_email
        FROM stores s
        JOIN sessions sess ON s.user_id = sess.user_id
        JOIN users u ON s.user_id = u.id
        WHERE sess.token = ? AND sess.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $store = $stmt->fetch();
    
    if (!$store) {
        http_response_code(404);
        die(json_encode(['error' => 'Loja não encontrada']));
    }
    
    echo json_encode([
        'success' => true,
        'store' => $store
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint não encontrado']);

// backend/api/checkout/validate-coupon.php
<?php
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
        AND status = 'active'
        AND (expires_at IS NULL OR expires_at > NOW())
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
        'discount_type' => $coupon['discount_type'],
        'discount_value' => (float) $coupon['discount_value']
    ]);
    
} catch (Exception $e) {
    error_log("Validate Coupon Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao validar cupom']);
}
?>