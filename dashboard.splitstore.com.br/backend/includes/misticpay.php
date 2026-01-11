<?php
// dashboard.splitstore.com.br/backend/includes/misticpay.php

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com/v1';
    
    /**
     * Criar um novo pagamento
     */
    public function createPayment($data) {
        $payload = [
            'amount' => $data['amount'],
            'currency' => 'BRL',
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'customer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'document' => $data['customer_document'] ?? null
            ],
            'items' => [
                [
                    'name' => $data['plan_name'],
                    'quantity' => 1,
                    'unit_price' => $data['amount']
                ]
            ],
            'metadata' => [
                'plan_id' => $data['plan_id'],
                'store_slug' => $data['store_slug'],
                'store_name' => $data['store_name'],
                'user_id' => $data['user_id'],
                'pending_store_id' => $data['pending_store_id']
            ],
            'payment_method_types' => ['pix'],
            'success_url' => 'https://dashboard.splitstore.com.br?status=success',
            'cancel_url' => 'https://dashboard.splitstore.com.br?status=cancel',
            'webhook_url' => 'https://splitstore.com.br/webhooks/misticpay.php'
        ];
        
        error_log("MisticPay CreatePayment Request: " . json_encode($payload));
        
        $result = $this->makeRequest('POST', '/payments', $payload);
        
        error_log("MisticPay CreatePayment Response: " . json_encode($result));
        
        return $result;
    }
    
    /**
     * Buscar informações de um pagamento
     */
    public function getPayment($paymentId) {
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
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];
        
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            error_log("MisticPay CURL Error: " . $error);
            return [
                'success' => false,
                'error' => $error,
                'http_code' => 0
            ];
        }
        
        $result = json_decode($response, true);
        
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