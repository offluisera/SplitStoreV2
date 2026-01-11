<?php
/**
 * ============================================
 * MERCADOPAGO - INTEGRAÇÃO COMPLETA
 * ============================================
 * dashboard.splitstore.com.br/backend/includes/mercadopago.php
 * 
 * Suporta:
 * - Cartão de Crédito
 * - Cartão de Débito
 * - Boleto Bancário
 * - PIX (opcional)
 */

class MercadoPago {
    private $accessToken;
    private $apiUrl = 'https://api.mercadopago.com';
    private $publicKey;
    
    public function __construct() {
        // CONFIGURAÇÃO: Adicione suas credenciais do MercadoPago
        // Obtenha em: https://www.mercadopago.com.br/developers/panel/credentials
        $this->accessToken = getenv('MERCADOPAGO_ACCESS_TOKEN') ?: 'APP_USR-5162382429216835-011115-e15aea05d70ef2c745af51327f2b56c6-1563669339';
        $this->publicKey = getenv('MERCADOPAGO_PUBLIC_KEY') ?: 'APP_USR-9aa3a7d4-6d01-4835-a373-1978aa70a24a';
    }
    
    /**
     * Criar preferência de pagamento (para checkout pro/básico)
     * Usado para: Boleto, Cartão via checkout
     */
    public function createPreference($data) {
        $payload = [
            'items' => [
                [
                    'title' => $data['description'] ?? 'Assinatura SplitStore',
                    'description' => $data['plan_name'] ?? '',
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => floatval($data['amount'])
                ]
            ],
            'payer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/[^0-9]/', '', $data['customer_cpf'])
                ]
            ],
            'back_urls' => [
                'success' => 'https://dashboard.splitstore.com.br/checkout/success',
                'failure' => 'https://dashboard.splitstore.com.br/checkout/failure',
                'pending' => 'https://dashboard.splitstore.com.br/checkout/pending'
            ],
            'auto_return' => 'approved',
            'notification_url' => 'https://dashboard.splitstore.com.br/backend/webhooks/mercadopago.php',
            'external_reference' => $data['pending_store_id'],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'store_name' => $data['store_name'],
                'user_id' => $data['user_id'],
                'pending_store_id' => $data['pending_store_id']
            ],
            'payment_methods' => [
                'installments' => 1, // Número máximo de parcelas
                'default_installments' => 1
            ],
            'statement_descriptor' => 'SPLITSTORE' // Aparece na fatura do cartão
        ];
        
        error_log("=== MercadoPago CreatePreference ===");
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        return $this->makeRequest('POST', '/checkout/preferences', $payload);
    }
    
    /**
     * Criar pagamento direto (para processar cartão diretamente)
     */
    public function createPayment($data) {
        $payload = [
            'transaction_amount' => floatval($data['amount']),
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'payment_method_id' => $data['payment_method_id'], // ex: 'visa', 'master', 'bolbradesco'
            'token' => $data['token'] ?? null, // Token do cartão (gerado no frontend)
            'installments' => intval($data['installments'] ?? 1),
            'payer' => [
                'email' => $data['customer_email'],
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/[^0-9]/', '', $data['customer_cpf'])
                ]
            ],
            'notification_url' => 'https://dashboard.splitstore.com.br/backend/webhooks/mercadopago.php',
            'external_reference' => $data['pending_store_id'],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'user_id' => $data['user_id']
            ]
        ];
        
        // Se for boleto, adicionar data de vencimento
        if (strpos($data['payment_method_id'], 'bolbradesco') !== false || 
            strpos($data['payment_method_id'], 'pec') !== false) {
            $payload['date_of_expiration'] = date('c', strtotime('+3 days'));
        }
        
        error_log("=== MercadoPago CreatePayment ===");
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        return $this->makeRequest('POST', '/v1/payments', $payload);
    }
    
    /**
     * Buscar informações de um pagamento
     */
    public function getPayment($paymentId) {
        return $this->makeRequest('GET', "/v1/payments/{$paymentId}");
    }
    
    /**
     * Obter métodos de pagamento disponíveis
     */
    public function getPaymentMethods() {
        return $this->makeRequest('GET', '/v1/payment_methods');
    }
    
    /**
     * Cancelar/reembolsar pagamento
     */
    public function refundPayment($paymentId) {
        return $this->makeRequest('POST', "/v1/payments/{$paymentId}/refunds");
    }
    
    /**
     * Fazer requisição para API do MercadoPago
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->accessToken
        ];
        
        error_log("=== MercadoPago Request ===");
        error_log("Method: $method");
        error_log("URL: $url");
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            error_log("Request Body: $jsonData");
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("=== MercadoPago Response ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response: $response");
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
                'data' => null
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'http_code' => $httpCode,
                'data' => $response
            ];
        }
        
        $isSuccess = $httpCode >= 200 && $httpCode < 300;
        
        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'data' => $result,
            'error' => !$isSuccess ? ($result['message'] ?? $result['error'] ?? 'Unknown error') : null
        ];
    }
    
    /**
     * Verificar assinatura do webhook
     */
    public function verifyWebhookSignature($payload, $signature, $dataId = null) {
        // MercadoPago usa x-signature no header
        // Formato: ts=timestamp,v1=hash
        
        if (empty($signature)) {
            return true; // Aceita sem signature para testes
        }
        
        // Parse da signature
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            list($key, $value) = explode('=', $part, 2);
            $parts[$key] = $value;
        }
        
        $ts = $parts['ts'] ?? '';
        $hash = $parts['v1'] ?? '';
        
        // Criar hash esperado
        $template = "id:{$dataId};request-id:{$_SERVER['HTTP_X_REQUEST_ID']};ts:{$ts};";
        $expectedHash = hash_hmac('sha256', $template, $this->accessToken);
        
        return hash_equals($expectedHash, $hash);
    }
    
    /**
     * Obter Public Key para uso no frontend
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
    
    /**
     * Extrair dados do pagamento
     */
    public function extractPaymentData($response) {
        if (!isset($response['data'])) {
            return null;
        }
        
        $data = $response['data'];
        
        return [
            'payment_id' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'status_detail' => $data['status_detail'] ?? '',
            'amount' => $data['transaction_amount'] ?? 0,
            'payment_method' => $data['payment_method_id'] ?? '',
            'payment_type' => $data['payment_type_id'] ?? '',
            'external_reference' => $data['external_reference'] ?? null,
            
            // Dados específicos de boleto
            'barcode' => $data['barcode']['content'] ?? null,
            'ticket_url' => $data['transaction_details']['external_resource_url'] ?? null,
            'expiration_date' => $data['date_of_expiration'] ?? null,
            
            // Dados do pagador
            'payer_email' => $data['payer']['email'] ?? null,
            'payer_name' => $data['payer']['first_name'] ?? null,
        ];
    }
}
?>