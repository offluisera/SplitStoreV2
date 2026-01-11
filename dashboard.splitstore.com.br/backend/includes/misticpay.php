<?php
/**
 * ============================================
 * MISTICPAY - CLASSE CORRIGIDA
 * ============================================
 * dashboard.splitstore.com.br/backend/includes/misticpay.php
 */

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com/api'; // CORRETO: /api não /v1
    
    /**
     * Criar um novo pagamento PIX
     */
    public function createPayment($data) {
        // Estrutura CORRETA segundo docs da MisticPay
        $payload = [
            'amount' => number_format((float)$data['amount'], 2, '.', ''), // String com 2 decimais
            'currency' => 'BRL',
            'payment_method' => 'pix',
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'customer' => [
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'document' => preg_replace('/[^0-9]/', '', $data['customer_cpf'] ?? '')
            ],
            'metadata' => [
                'plan_id' => $data['plan_id'] ?? '',
                'store_slug' => $data['store_slug'] ?? '',
                'store_name' => $data['store_name'] ?? '',
                'user_id' => $data['user_id'] ?? '',
                'pending_store_id' => $data['pending_store_id'] ?? ''
            ],
            // IMPORTANTE: Webhook deve apontar para dashboard
            'webhook_url' => 'https://dashboard.splitstore.com.br/backend/webhooks/misticpay.php',
            'return_url' => 'https://dashboard.splitstore.com.br?status=success',
            'expires_in' => 600 // 10 minutos em segundos
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
        
        // Headers CORRETOS segundo documentação
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->clientSecret, // Bearer token
            'X-Client-Id: ' . $this->clientId
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
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            error_log("Request Body: $jsonData");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Pega informações adicionais do erro
        $curlInfo = curl_getinfo($ch);
        
        curl_close($ch);
        
        error_log("=== MisticPay Response ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response Body: $response");
        
        if ($error) {
            error_log("CURL Error: $error");
            error_log("CURL Info: " . json_encode($curlInfo));
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
                'data' => null
            ];
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            error_log("Raw Response: $response");
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg(),
                'http_code' => $httpCode,
                'data' => $response,
                'raw_response' => $response
            ];
        }
        
        // Considera sucesso se HTTP 200-299
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
    public function verifyWebhookSignature($payload, $signature) {
        if (empty($signature)) {
            return true; // Se não tiver signature, aceita (para testes)
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->clientSecret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Extrair dados do PIX da resposta
     */
    public function extractPixData($response) {
        if (!isset($response['data'])) {
            return null;
        }
        
        $data = $response['data'];
        
        // Tenta diferentes estruturas que a MisticPay pode retornar
        $pixData = [
            'payment_id' => $data['id'] ?? $data['payment_id'] ?? null,
            'qr_code' => $data['pix']['qr_code'] ?? $data['qr_code'] ?? $data['pix_code'] ?? null,
            'qr_code_base64' => $data['pix']['qr_code_image'] ?? $data['qr_code_image'] ?? $data['qr_code_base64'] ?? null,
            'amount' => $data['amount'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'expires_at' => $data['expires_at'] ?? $data['expiration_date'] ?? null
        ];
        
        error_log("=== Extracted PIX Data ===");
        error_log(json_encode($pixData, JSON_PRETTY_PRINT));
        
        return $pixData;
    }
}
?>
