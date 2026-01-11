<?php
// dashboard.splitstore.com.br/backend/includes/misticpay.php

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com/api'; // URL CORRIGIDA
    
    /**
     * Criar um novo pagamento
     */
    public function createPayment($data) {
        // Payload segundo documentação MisticPay
        $payload = [
            'amount' => number_format($data['amount'], 2, '.', ''), // Garantir formato decimal
            'currency' => 'BRL',
            'payment_method' => 'pix', // Método de pagamento
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'customer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'document' => $data['customer_document'] ?? null
            ],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'store_name' => $data['store_name'],
                'user_id' => $data['user_id'],
                'pending_store_id' => $data['pending_store_id']
            ],
            'callback_url' => 'https://dashboard.splitstore.com.br/webhooks/misticpay',
            'return_url' => 'https://dashboard.splitstore.com.br?status=success'
        ];
        
        error_log("=== MisticPay CreatePayment Request ===");
        error_log("URL: {$this->apiUrl}/payments");
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        $result = $this->makeRequest('POST', '/payments', $payload);
        
        error_log("=== MisticPay CreatePayment Response ===");
        error_log("HTTP Code: " . ($result['http_code'] ?? 'N/A'));
        error_log("Response: " . json_encode($result, JSON_PRETTY_PRINT));
        
        return $result;
    }
    
    /**
     * Buscar informações de um pagamento
     */
    public function getPayment($paymentId) {
        error_log("=== MisticPay GetPayment ===");
        error_log("Payment ID: $paymentId");
        
        return $this->makeRequest('GET', "/payments/{$paymentId}");
    }
    
    /**
     * Cancelar um pagamento
     */
    public function cancelPayment($paymentId) {
        return $this->makeRequest('POST', "/payments/{$paymentId}/cancel");
    }
    
    /**
     * Fazer requisição para API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        // Autenticação Basic Auth
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $auth
        ];
        
        error_log("=== MisticPay Request ===");
        error_log("Method: $method");
        error_log("URL: $url");
        error_log("Headers: " . json_encode($headers));
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Ativar modo verbose para debug
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            error_log("Request Body: $jsonData");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        error_log("=== MisticPay Response ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response Body: $response");
        
        if ($error) {
            error_log("CURL Error: $error");
            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0,
                'data' => null
            ];
        }
        
        $result = json_decode($response, true);
        
        // Verificar se a resposta é válida
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'http_code' => $httpCode,
                'data' => $response
            ];
        }
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $result
        ];
    }
    
    /**
     * Verificar assinatura do webhook
     */
    public function verifyWebhookSignature($payload, $signature) {
        $expectedSignature = hash_hmac('sha256', $payload, $this->clientSecret);
        return hash_equals($expectedSignature, $signature);
    }
}