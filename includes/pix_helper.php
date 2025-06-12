<?php
/**
 * Funções auxiliares para operações Pix
 */

/**
 * Cria uma nova transação Pix no banco
 */
function criarTransacaoPix($conn, $dados) {
    $external_id = 'ORDER_' . $dados['pedido_id'] . '_' . time() . '_' . rand(1000, 9999);
    
    $stmt = $conn->prepare("
        INSERT INTO transacoes_pix 
        (pedido_id, user_id, external_id, amount, payer_name, payer_document, payer_email, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");
    
    $stmt->execute([
        $dados['pedido_id'],
        $dados['user_id'],
        $external_id,
        $dados['amount'],
        $dados['payer_name'],
        $dados['payer_document'],
        $dados['payer_email']
    ]);
    
    return [
        'id' => $conn->lastInsertId(),
        'external_id' => $external_id
    ];
}

/**
 * Atualiza status da transação Pix
 */
function atualizarStatusTransacao($conn, $external_id, $dados_atualizacao) {
    $sql = "UPDATE transacoes_pix SET ";
    $params = [];
    $updates = [];
    
    if (isset($dados_atualizacao['status'])) {
        $updates[] = "status = ?";
        $params[] = $dados_atualizacao['status'];
    }
    
    if (isset($dados_atualizacao['transaction_id'])) {
        $updates[] = "transaction_id = ?";
        $params[] = $dados_atualizacao['transaction_id'];
    }
    
    if (isset($dados_atualizacao['qr_code_image'])) {
        $updates[] = "qr_code_image = ?";
        $params[] = $dados_atualizacao['qr_code_image'];
    }
    
    if (isset($dados_atualizacao['qr_code_text'])) {
        $updates[] = "qr_code_text = ?";
        $params[] = $dados_atualizacao['qr_code_text'];
    }
    
    if (isset($dados_atualizacao['paid_at'])) {
        $updates[] = "paid_at = ?";
        $params[] = $dados_atualizacao['paid_at'];
    }
    
    if (isset($dados_atualizacao['expires_at'])) {
        $updates[] = "expires_at = ?";
        $params[] = $dados_atualizacao['expires_at'];
    }
    
    $updates[] = "updated_at = NOW()";
    
    $sql .= implode(', ', $updates) . " WHERE external_id = ?";
    $params[] = $external_id;
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Busca transação por external_id
 */
function buscarTransacaoPorExternalId($conn, $external_id) {
    $stmt = $conn->prepare("SELECT * FROM transacoes_pix WHERE external_id = ?");
    $stmt->execute([$external_id]);
    return $stmt->fetch();
}

/**
 * Busca transação por pedido_id
 */
function buscarTransacaoPorPedido($conn, $pedido_id) {
    $stmt = $conn->prepare("SELECT * FROM transacoes_pix WHERE pedido_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$pedido_id]);
    return $stmt->fetch();
}

/**
 * Valida CPF
 */
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

/**
 * Formata CPF
 */
function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Remove formatação do CPF
 */
function limparCPF($cpf) {
    return preg_replace('/[^0-9]/', '', $cpf);
}

/**
 * Gera URL do postback
 */
function obterUrlPostback() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/pix/postback.php';
}

/**
 * Processa pagamento confirmado
 */
function processarPagamentoConfirmado($conn, $transacao) {
    try {
        $conn->beginTransaction();
        
        // Atualizar status do pedido
        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->execute([$transacao['pedido_id']]);
        
        // Buscar itens do pedido
        $stmt = $conn->prepare("
            SELECT product_id, quantity 
            FROM order_items 
            WHERE order_id = ?
        ");
        $stmt->execute([$transacao['pedido_id']]);
        $items = $stmt->fetchAll();
        
        // Adicionar aos downloads
        foreach ($items as $item) {
            // Verificar se já não existe
            $stmt = $conn->prepare("
                SELECT id FROM downloads 
                WHERE user_id = ? AND product_id = ? AND order_id = ?
            ");
            $stmt->execute([
                $transacao['user_id'], 
                $item['product_id'], 
                $transacao['pedido_id']
            ]);
            
            if (!$stmt->fetch()) {
                // Inserir download
                $stmt = $conn->prepare("
                    INSERT INTO downloads (user_id, product_id, order_id) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $transacao['user_id'], 
                    $item['product_id'], 
                    $transacao['pedido_id']
                ]);
                
                // Atualizar contador de downloads do produto
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET downloads_count = downloads_count + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>