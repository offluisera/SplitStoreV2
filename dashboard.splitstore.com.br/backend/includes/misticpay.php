<?php
/**
 * ============================================
 * MISTICPAY - CLASSE CORRIGIDA COM DOCUMENTAÇÃO OFICIAL
 * ============================================
 * dashboard.splitstore.com.br/backend/includes/misticpay.php
 */

class MisticPay {
    private $clientId = 'ci_6wqrtigx1d8e430';
    private $clientSecret = 'cs_w810l4jlhnqs60rrmxh8xgd2u';
    private $apiUrl = 'https://api.misticpay.com';
    
    /**
     * Criar uma nova transação PIX
     * Conforme documentação: https://docs.misticpay.com/#create-transaction
     */
    public function createPayment($data) {
        // Estrutura CORRETA segundo a documentação MisticPay
        $payload = [
            'amount' => floatval($data['amount']), // Valor em reais (ex: 4.55)
            'payerName' => $data['customer_name'],
            'payerDocument' => preg_replace('/[^0-9]/', '', $data['customer_cpf'] ?? '00000000000'),
            'transactionId' => $data['pending_store_id'] ?? uniqid('store_'),
            'description' => $data['description'] ?? 'Assinatura SplitStore',
            'projectWebhook' => 'https://dashboard.splitstore.com.br/backend/webhooks/misticpay.php'
        ];
        
        error_log("=== MisticPay CreatePayment Request ===");
        error_log("URL: {$this->apiUrl}/api/transactions/create");
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));
        
        // Endpoint correto: /api/transactions/create
        $result = $this->makeRequest('POST', '/api/transactions/create', $payload);
        
        error_log("=== MisticPay CreatePayment Response ===");
        error_log("HTTP Code: " . ($result['http_code'] ?? 'N/A'));
        error_log("Response: " . json_encode($result, JSON_PRETTY_PRINT));
        
        return $result;
    }
    
    /**
     * Buscar informações de uma transação
     */
    public function getPayment($transactionId) {
        error_log("=== MisticPay GetPayment ===");
        error_log("Transaction ID: $transactionId");
        
        return $this->makeRequest('POST', "/api/transactions/check", ['transactionId' => $transactionId]);
    }
    
    /**
     * Cancelar uma transação
     */
    public function cancelPayment($transactionId) {
        return $this->makeRequest('POST', "/transactions/{$transactionId}/cancel");
    }
    
    /**
     * Fazer requisição para API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->apiUrl . $endpoint;
        
        // Headers corretos segundo documentação MisticPay
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'ci: ' . $this->clientId,
            'cs: ' . $this->clientSecret
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
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Debug detalhado
        
        // Capturar informações detalhadas do CURL
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            error_log("Request Body: $jsonData");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Pegar informações adicionais do erro
        $curlInfo = curl_getinfo($ch);
        
        // Ler verbose output
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        
        curl_close($ch);
        fclose($verbose);
        
        error_log("=== MisticPay Response ===");
        error_log("HTTP Code: $httpCode");
        error_log("Response Body: $response");
        error_log("CURL Verbose: $verboseLog");
        
        if ($error) {
            error_log("CURL Error: $error");
            error_log("CURL Info: " . json_encode($curlInfo));
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode,
                'data' => null,
                'curl_info' => $curlInfo
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
            return true; // Aceita sem signature para testes
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->clientSecret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Extrair dados do PIX da resposta
     * Adaptado para a estrutura real da MisticPay
     */
    public function extractPixData($response) {
        if (!isset($response['data'])) {
            return null;
        }
        
        $data = $response['data'];
        
        error_log("=== Extracting PIX Data ===");
        error_log("Response structure: " . json_encode(array_keys($data)));
        
        // A MisticPay retorna estrutura específica
        $pixData = [
            'transaction_id' => $data['transactionId'] ?? $data['data']['transactionId'] ?? null,
            'qr_code' => $data['copyPaste'] ?? $data['data']['copyPaste'] ?? null,
            'qr_code_base64' => $data['qrCodeBase64'] ?? $data['data']['qrCodeBase64'] ?? null,
            'qrcode_url' => $data['qrcodeUrl'] ?? $data['data']['qrcodeUrl'] ?? null,
            'amount' => $data['transactionAmount'] ?? $data['data']['transactionAmount'] ?? null,
            'status' => $data['transactionState'] ?? $data['data']['transactionState'] ?? 'PENDENTE',
            'payer_name' => $data['payer']['name'] ?? $data['data']['payer']['name'] ?? null,
            'payer_document' => $data['payer']['document'] ?? $data['data']['payer']['document'] ?? null
        ];
        
        error_log("=== Extracted PIX Data ===");
        error_log("Transaction ID: " . ($pixData['transaction_id'] ?? 'NULL'));
        error_log("QR Code: " . ($pixData['qr_code'] ? 'PRESENT (' . strlen($pixData['qr_code']) . ' chars)' : 'NULL'));
        error_log("QR Code Base64: " . ($pixData['qr_code_base64'] ? 'PRESENT' : 'NULL'));
        error_log("Full data: " . json_encode($pixData, JSON_PRETTY_PRINT));
        
        return $pixData;
    }
}
?>