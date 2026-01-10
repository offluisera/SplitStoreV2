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

// Planos fixos (não precisam estar no banco)
$plans = [
    [
        'id' => 'starter',
        'name' => 'STARTER',
        'description' => 'Perfeito para começar',
        'price' => '14.99',
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
        'price' => '25.99',
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
        'price' => '39.99',
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