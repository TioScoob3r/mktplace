<?php
/**
 * Classe para integração com API PixUp
 * Gerencia autenticação OAuth2 e operações Pix
 */

class PixUpAPI {
    private $base_url;
    private $client_id;
    private $client_secret;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadConfig();
        $this->base_url = 'https://api.pixupbr.com/v2';
    }
    
    /**
     * Carrega configurações do banco de dados
     */
    private function loadConfig() {
        $stmt = $this->conn->prepare("SELECT * FROM configuracoes_pixup WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            throw new Exception('Configurações PixUp não encontradas. Configure no painel administrativo.');
        }
        
        $this->client_id = $config['client_id'];
        $this->client_secret = $config['client_secret'];
        
        // Ajustar URL base conforme ambiente
        if ($config['ambiente'] === 'sandbox') {
            $this->base_url = 'https://api-sandbox.pixupbr.com/v2';
        }
    }
    
    /**
     * Gera cabeçalho de autorização Basic
     */
    private function getBasicAuthHeader() {
        $credentials = base64_encode($this->client_id . ':' . $this->client_secret);
        return 'Basic ' . $credentials;
    }
    
    /**
     * Obtém token OAuth2 válido
     */
    private function getValidToken() {
        // Verificar se existe token válido no cache
        $stmt = $this->conn->prepare("
            SELECT * FROM pixup_tokens 
            WHERE expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $cached_token = $stmt->fetch();
        
        if ($cached_token) {
            return $cached_token['access_token'];
        }
        
        // Gerar novo token
        return $this->generateNewToken();
    }
    
    /**
     * Gera novo token OAuth2
     */
    private function generateNewToken() {
        $url = $this->base_url . '/oauth/token';
        
        $headers = [
            'Accept: application/json',
            'Authorization: ' . $this->getBasicAuthHeader(),
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
            throw new Exception('Erro cURL na autenticação: ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('Erro na autenticação PixUp. HTTP Code: ' . $http_code . ' Response: ' . $response);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception('Token de acesso não recebido da PixUp');
        }
        
        // Salvar token no cache
        $expires_at = date('Y-m-d H:i:s', time() + $data['expires_in'] - 60); // 60s de margem
        
        $stmt = $this->conn->prepare("
            INSERT INTO pixup_tokens (access_token, token_type, expires_in, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['access_token'],
            $data['token_type'] ?? 'Bearer',
            $data['expires_in'],
            $expires_at
        ]);
        
        // Limpar tokens expirados
        $this->conn->exec("DELETE FROM pixup_tokens WHERE expires_at <= NOW()");
        
        return $data['access_token'];
    }
    
    /**
     * Gera QR Code Pix para pagamento
     */
    public function gerarCobrancaPix($dados_cobranca) {
        $token = $this->getValidToken();
        $url = $this->base_url . '/pix/qrcode';
        
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ];
        
        $payload = [
            'amount' => $dados_cobranca['amount'],
            'postbackUrl' => $dados_cobranca['postback_url'],
            'external_id' => $dados_cobranca['external_id'],
            'payerQuestion' => $dados_cobranca['payer_question'] ?? 'MARKETPLACE DIGITAL',
            'payer' => [
                'name' => $dados_cobranca['payer_name'],
                'document' => $dados_cobranca['payer_document'],
                'email' => $dados_cobranca['payer_email']
            ]
        ];
        
        // Adicionar data de expiração se fornecida
        if (isset($dados_cobranca['due_date'])) {
            $payload['dueDate'] = $dados_cobranca['due_date'];
        }
        
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
            throw new Exception('Erro cURL ao gerar cobrança: ' . $error);
        }
        
        if ($http_code !== 200 && $http_code !== 201) {
            throw new Exception('Erro ao gerar cobrança Pix. HTTP Code: ' . $http_code . ' Response: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Consulta status de uma transação
     */
    public function consultarStatusTransacao($transaction_id) {
        $token = $this->getValidToken();
        $url = $this->base_url . '/pix/transaction/' . $transaction_id;
        
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
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
            throw new Exception('Erro cURL ao consultar transação: ' . $error);
        }
        
        if ($http_code !== 200) {
            throw new Exception('Erro ao consultar transação. HTTP Code: ' . $http_code);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Testa conectividade com a API
     */
    public function testarConexao() {
        try {
            $token = $this->getValidToken();
            return [
                'sucesso' => true,
                'mensagem' => 'Conexão com PixUp estabelecida com sucesso!',
                'token_obtido' => !empty($token)
            ];
        } catch (Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro na conexão: ' . $e->getMessage()
            ];
        }
    }
}
?>