<?php
/**
 * Script para criar tabela de transações Pix
 * Execute este arquivo uma vez para criar a tabela necessária
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Criar tabela de transações Pix
    $sql_pix_transactions = "CREATE TABLE IF NOT EXISTS pix_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        external_id VARCHAR(100) UNIQUE NOT NULL,
        transaction_id VARCHAR(100),
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('PENDING', 'PAID', 'CANCELLED', 'EXPIRED') DEFAULT 'PENDING',
        payer_name VARCHAR(100) NOT NULL,
        payer_document VARCHAR(14) NOT NULL,
        payer_email VARCHAR(100) NOT NULL,
        qr_code TEXT,
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_external_id (external_id),
        INDEX idx_order_id (order_id),
        INDEX idx_transaction_id (transaction_id),
        INDEX idx_status (status)
    )";
    
    $conn->exec($sql_pix_transactions);
    
    // Criar diretório para logs do webhook se não existir
    if (!file_exists('pix')) {
        mkdir('pix', 0755, true);
    }
    
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup Pix Concluído - Marketplace Digital</title>
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
                            <h2 class='text-success'>Setup Pix Concluído!</h2>
                            <p class='lead'>A integração com PixUp foi configurada com sucesso.</p>
                            
                            <div class='alert alert-warning'>
                                <h5><i class='fas fa-exclamation-triangle'></i> Configuração Necessária</h5>
                                <p><strong>Edite o arquivo config/pixup.php e substitua a chave de autorização:</strong></p>
                                <code>private \$auth_key = 'Basic SUA_CHAVE_AQUI';</code>
                                <p class='mt-2'><small>Obtenha sua chave na dashboard da PixUp</small></p>
                            </div>
                            
                            <div class='alert alert-info'>
                                <h5><i class='fas fa-info-circle'></i> Recursos Implementados</h5>
                                <ul class='text-start'>
                                    <li>✅ Autenticação OAuth2 com PixUp</li>
                                    <li>✅ Geração de QR Code Pix</li>
                                    <li>✅ Webhook para confirmação automática</li>
                                    <li>✅ Verificação de status em tempo real</li>
                                    <li>✅ Liberação automática de downloads</li>
                                    <li>✅ Interface completa para o usuário</li>
                                </ul>
                            </div>
                            
                            <div class='alert alert-secondary'>
                                <h6><i class='fas fa-link'></i> URL do Webhook</h6>
                                <p>Configure na PixUp: <code>https://seudominio.com/pix/postback.php</code></p>
                            </div>
                            
                            <div class='d-grid gap-2 d-md-block'>
                                <a href='index.php' class='btn btn-primary'>Ir para Home</a>
                                <a href='pages/products.php' class='btn btn-outline-primary'>Ver Produtos</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
} catch(PDOException $e) {
    echo "Erro na configuração do banco de dados: " . $e->getMessage();
}
?>