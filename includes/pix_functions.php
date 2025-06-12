<?php
/**
 * Funções auxiliares para integração Pix
 */

require_once __DIR__ . '/../config/pixup.php';

/**
 * Cria uma nova transação Pix no banco de dados
 */
function createPixTransaction($conn, $order_id, $user_id, $amount, $payer_data) {
    $external_id = 'ORDER_' . $order_id . '_' . time();
    
    $stmt = $conn->prepare("
        INSERT INTO pix_transactions 
        (order_id, user_id, external_id, amount, payer_name, payer_document, payer_email, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");
    
    $stmt->execute([
        $order_id,
        $user_id,
        $external_id,
        $amount,
        $payer_data['name'],
        $payer_data['document'],
        $payer_data['email']
    ]);
    
    return [
        'id' => $conn->lastInsertId(),
        'external_id' => $external_id
    ];
}

/**
 * Atualiza status da transação Pix
 */
function updatePixTransactionStatus($conn, $external_id, $status, $transaction_id = null, $paid_at = null) {
    $sql = "UPDATE pix_transactions SET status = ?";
    $params = [$status];
    
    if ($transaction_id) {
        $sql .= ", transaction_id = ?";
        $params[] = $transaction_id;
    }
    
    if ($paid_at) {
        $sql .= ", paid_at = ?";
        $params[] = $paid_at;
    }
    
    $sql .= " WHERE external_id = ?";
    $params[] = $external_id;
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Busca transação Pix por external_id
 */
function getPixTransactionByExternalId($conn, $external_id) {
    $stmt = $conn->prepare("SELECT * FROM pix_transactions WHERE external_id = ?");
    $stmt->execute([$external_id]);
    return $stmt->fetch();
}

/**
 * Busca transação Pix por order_id
 */
function getPixTransactionByOrderId($conn, $order_id) {
    $stmt = $conn->prepare("SELECT * FROM pix_transactions WHERE order_id = ?");
    $stmt->execute([$order_id]);
    return $stmt->fetch();
}

/**
 * Valida CPF
 */
function validateCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se não é uma sequência de números iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validação do algoritmo do CPF
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
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Remove formatação do CPF
 */
function cleanCPF($cpf) {
    return preg_replace('/[^0-9]/', '', $cpf);
}

/**
 * Gera URL do postback
 */
function getPostbackUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/pix/postback.php';
}
?>