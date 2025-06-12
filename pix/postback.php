<?php
/**
 * Webhook da PixUp para receber notificações de pagamento
 */

require_once '../config/database.php';
require_once '../includes/pix_helper.php';

// Log para debug (remover em produção)
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'get' => $_GET,
    'post' => $_POST
];

file_put_contents(__DIR__ . '/postback.log', json_encode($log_data) . "\n", FILE_APPEND);

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit();
}

// Ler dados do webhook
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo 'Invalid JSON';
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Validar dados obrigatórios
    if (!isset($data['external_id']) || !isset($data['status'])) {
        http_response_code(400);
        echo 'Missing required fields';
        exit();
    }
    
    $external_id = $data['external_id'];
    $status = $data['status'];
    $transaction_id = $data['transactionId'] ?? null;
    
    // Buscar transação no banco
    $transacao = buscarTransacaoPorExternalId($conn, $external_id);
    
    if (!$transacao) {
        http_response_code(404);
        echo 'Transaction not found';
        exit();
    }
    
    // Verificar se status mudou
    if ($transacao['status'] === $status) {
        http_response_code(200);
        echo 'Status already updated';
        exit();
    }
    
    $conn->beginTransaction();
    
    // Preparar dados para atualização
    $dados_atualizacao = ['status' => $status];
    
    if ($transaction_id) {
        $dados_atualizacao['transaction_id'] = $transaction_id;
    }
    
    if ($status === 'PAID') {
        $dados_atualizacao['paid_at'] = date('Y-m-d H:i:s');
        
        // Processar pagamento confirmado
        processarPagamentoConfirmado($conn, $transacao);
    }
    
    // Atualizar status da transação
    atualizarStatusTransacao($conn, $external_id, $dados_atualizacao);
    
    $conn->commit();
    
    // Resposta de sucesso
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Log do erro
    error_log('Erro no postback PixUp: ' . $e->getMessage());
    file_put_contents(__DIR__ . '/error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo 'Internal Server Error';
}
?>