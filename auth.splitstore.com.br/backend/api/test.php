<?php
// auth.splitstore.com.br/backend/api/test.php
// Arquivo temporário para testar roteamento

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => true,
    'message' => 'Roteamento está funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
        'PHP_SELF' => $_SERVER['PHP_SELF'],
        'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT']
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);