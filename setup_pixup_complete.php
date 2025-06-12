<?php
/**
 * Script para criar tabelas necessárias para integração PixUp
 * Execute este arquivo uma vez para criar as tabelas
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Criar tabela de configurações PixUp
    $sql_config = "CREATE TABLE IF NOT EXISTS configuracoes_pixup (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id VARCHAR(255) NOT NULL,
        client_secret VARCHAR(255) NOT NULL,
        ambiente ENUM('sandbox', 'producao') DEFAULT 'sandbox',
        ativo BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    // Criar tabela de transações Pix
    $sql_transacoes = "CREATE TABLE IF NOT EXISTS transacoes_pix (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        user_id INT NOT NULL,
        transaction_id VARCHAR(100),
        external_id VARCHAR(100) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('PENDING', 'PAID', 'CANCELLED', 'EXPIRED') DEFAULT 'PENDING',
        payer_name VARCHAR(100) NOT NULL,
        payer_document VARCHAR(14) NOT NULL,
        payer_email VARCHAR(100) NOT NULL,
        qr_code_image TEXT,
        qr_code_text TEXT,
        paid_at TIMESTAMP NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (pedido_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_external_id (external_id),
        INDEX idx_transaction_id (transaction_id),
        INDEX idx_status (status),
        INDEX idx_pedido_id (pedido_id)
    )";
    
    // Criar tabela de tokens OAuth (cache)
    $sql_tokens = "CREATE TABLE IF NOT EXISTS pixup_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        access_token TEXT NOT NULL,
        token_type VARCHAR(50) DEFAULT 'Bearer',
        expires_in INT NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    // Executar as queries
    $conn->exec($sql_config);
    $conn->exec($sql_transacoes);
    $conn->exec($sql_tokens);
    
    // Criar diretório para logs se não existir
    if (!file_exists('pix')) {
        mkdir('pix', 0755, true);
    }
    
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup PixUp Concluído - Marketplace Digital</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    </head>
    <body class='bg-light'>
        <div class='container py-5'>
            <div class='row justify-content-center'>
                <div class='col-md-8'>
                    <div class='card shadow'>
                        <div class='card-body text-center'>
                            <div class='text-success mb-3'>
                                <i class='fas fa-check-circle' style='font-size: 3rem;'></i>
                            </div>
                            <h2 class='text-success'>Setup PixUp Concluído!</h2>
                            <p class='lead'>A integração completa com PixUp foi configurada com sucesso.</p>
                            
                            <div class='alert alert-info'>
                                <h5><i class='fas fa-cog'></i> Próximos Passos</h5>
                                <ol class='text-start'>
                                    <li>Acesse o painel administrativo</li>
                                    <li>Vá em 'Configurações PixUp'</li>
                                    <li>Configure seu Client ID e Client Secret</li>
                                    <li>Teste a integração</li>
                                </ol>
                            </div>
                            
                            <div class='alert alert-warning'>
                                <h6><i class='fas fa-exclamation-triangle'></i> URL do Webhook</h6>
                                <p>Configure na PixUp: <code>https://seudominio.com/pix/postback.php</code></p>
                            </div>
                            
                            <div class='d-grid gap-2 d-md-block'>
                                <a href='admin/index.php' class='btn btn-primary'>Painel Admin</a>
                                <a href='index.php' class='btn btn-outline-primary'>Ir para Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
} catch(PDOException $e) {
    echo "Erro na configuração: " . $e->getMessage();
}
?>