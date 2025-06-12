<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/pix_helper.php';
require_once '../config/pixup_api.php';

header('Content-Type: application/json');

requireLogin();

$db = new Database();
$conn = $db->getConnection();

$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$order_id) {
    echo json_encode(['error' => 'Order ID não fornecido']);
    exit();
}

// Verificar se o pedido pertence ao usuário
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['error' => 'Pedido não encontrado']);
    exit();
}

// Buscar transação Pix
$transacao = buscarTransacaoPorPedido($conn, $order_id);

if (!$transacao) {
    echo json_encode(['error' => 'Transação Pix não encontrada']);
    exit();
}

try {
    // Se já está pago, retornar status
    if ($transacao['status'] === 'PAID') {
        echo json_encode([
            'status' => 'PAID',
            'paid_at' => $transacao['paid_at'],
            'amount' => $transacao['amount']
        ]);
        exit();
    }
    
    // Consultar status na PixUp se tiver transaction_id
    if ($transacao['transaction_id']) {
        $pixup = new PixUpAPI($conn);
        $status_response = $pixup->consultarStatusTransacao($transacao['transaction_id']);
        
        // Atualizar status local se mudou
        if ($status_response['status'] !== $transacao['status']) {
            $dados_atualizacao = ['status' => $status_response['status']];
            
            if ($status_response['status'] === 'PAID') {
                $dados_atualizacao['paid_at'] = date('Y-m-d H:i:s');
                
                // Processar pagamento confirmado
                processarPagamentoConfirmado($conn, $transacao);
            }
            
            atualizarStatusTransacao($conn, $transacao['external_id'], $dados_atualizacao);
        }
        
        echo json_encode([
            'status' => $status_response['status'],
            'amount' => $status_response['amount'] ?? $transacao['amount'],
            'paid_at' => $dados_atualizacao['paid_at'] ?? $transacao['paid_at']
        ]);
    } else {
        // Retornar status atual do banco
        echo json_encode([
            'status' => $transacao['status'],
            'amount' => $transacao['amount']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Erro ao verificar status: ' . $e->getMessage(),
        'status' => $transacao['status']
    ]);
}
?>