<?php
/**
 * Configurações da API PixUp
 * Classe para gerenciar integração com PixUp
 */

class PixUpAPI {
    private $base_url = 'https://api.pixupbr.com/v2';
    private $auth_key; // Chave de autorização já codificada em base64
    private $access_token;
    private $token_expires_at;
    
    public function __construct() {
        // Substitua pela sua chave de autorização da PixUp (já em base64)
        $this->auth_key = 'Basic SXNtYWZv...'; // Exemplo - substitua pela sua chave real
        $this->loadTokenFromSession();
    }
    
    /**
     * Carrega token da sessão se ainda válido
     */
    private function loadTokenFromSession() {
        if (isset($_SESSION['pixup_token']) && isset($_SESSION['pixup_token_expires'])) {
            if (time() < $_SESSION['pixup_token_expires']) {
                $this->access_token = $_SESSION['pixup_token'];
                $this->token_expires_at = $_SESSION['pixup_token_expires'];
            }
        }
    }
    
    /**
     * Salva token na sessão
     */
    private function saveTokenToSession($token, $expires_in) {
        $this->access_token = $token;
        $this->token_expires_at = time() + $expires_in - 60; // 60s de margem
        
        $_SESSION['pixup_token'] = $this->access_token;
        $_SESSION['pixup_token_expires'] = $this->token_expires_at;
    }
    
    /**
     * Gera token de autenticação OAuth2
     */
    public function authenticate() {
        $url = $this->base_url . '/oauth/token';
        
        $headers = [
            'Accept: application/json',
            'Authorization: ' . $this->auth_key,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $data = 'grant_type=client_credentials';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('Erro na autenticação PixUp. HTTP Code: ' . $http_code);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Token de acesso não recebido da PixUp');
        }
        
        $this->saveTokenToSession($data['access_token'], $data['expires_in']);
        
        return $data;
    }
    
    /**
     * Verifica se precisa renovar o token
     */
    private function ensureValidToken() {
        if (!$this->access_token || time() >= $this->token_expires_at) {
            $this->authenticate();
        }
    }
    
    /**
     * Gera QR Code Pix para pagamento
     */
    public function generatePixQRCode($order_data) {
        $this->ensureValidToken();
        
        $url = $this->base_url . '/pix/qrcode';
        
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];
        
        $payload = [
            'amount' => $order_data['amount'],
            'postbackUrl' => $order_data['postback_url'],
            'external_id' => $order_data['external_id'],
            'payerQuestion' => 'MARKETPLACE DIGITAL',
            'payer' => [
                'name' => $order_data['payer_name'],
                'document' => $order_data['payer_document'],
                'email' => $order_data['payer_email']
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($http_code !== 200 && $http_code !== 201) {
            throw new Exception('Erro ao gerar QR Code Pix. HTTP Code: ' . $http_code . ' Response: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Consulta status de uma transação
     */
    public function getTransactionStatus($transaction_id) {
        $this->ensureValidToken();
        
        $url = $this->base_url . '/pix/transaction/' . $transaction_id;
        
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $this->access_token
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro cURL: ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('Erro ao consultar transação. HTTP Code: ' . $http_code);
        }
        
        return json_decode($response, true);
    }
}
?>