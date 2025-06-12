<?php
/**
 * Script de Inicialização do Banco de Dados
 * Execute este arquivo uma vez para criar as tabelas necessárias
 */

require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Criar tabela de usuários
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('customer', 'admin') DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    // Criar tabela de produtos
    $sql_products = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        file_path VARCHAR(255),
        image VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        downloads_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    // Criar tabela de pedidos
    $sql_orders = "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    // Criar tabela de itens do pedido
    $sql_order_items = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    
    // Criar tabela de downloads
    $sql_downloads = "CREATE TABLE IF NOT EXISTS downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        order_id INT NOT NULL,
        download_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )";
    
    // Executar as queries
    $conn->exec($sql_users);
    $conn->exec($sql_products);
    $conn->exec($sql_orders);
    $conn->exec($sql_order_items);
    $conn->exec($sql_downloads);
    
    // Inserir usuário admin padrão
    $admin_email = 'admin@marketplace.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Administrador', $admin_email, $admin_password, 'admin']);
    
    // Inserir alguns produtos de exemplo
    $sample_products = [
        [
            'name' => 'E-book: Marketing Digital Completo',
            'description' => 'Guia completo sobre marketing digital com estratégias comprovadas para aumentar suas vendas online.',
            'price' => 29.90,
            'image' => 'https://via.placeholder.com/400x300/007bff/ffffff?text=E-book+Marketing'
        ],
        [
            'name' => 'Curso: PHP do Zero ao Profissional',
            'description' => 'Aprenda PHP desde o básico até conceitos avançados com projetos práticos.',
            'price' => 89.90,
            'image' => 'https://via.placeholder.com/400x300/28a745/ffffff?text=Curso+PHP'
        ],
        [
            'name' => 'Template: Landing Page Responsiva',
            'description' => 'Template profissional para landing pages com alta conversão, totalmente responsivo.',
            'price' => 19.90,
            'image' => 'https://via.placeholder.com/400x300/dc3545/ffffff?text=Template+LP'
        ],
        [
            'name' => 'Plugin: Sistema de Comentários',
            'description' => 'Plugin completo para sistema de comentários em websites com moderação.',
            'price' => 45.50,
            'image' => 'https://via.placeholder.com/400x300/ffc107/000000?text=Plugin+JS'
        ]
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
    foreach ($sample_products as $product) {
        $stmt->execute([$product['name'], $product['description'], $product['price'], $product['image']]);
    }
    
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup Concluído - Marketplace Digital</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
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
                            <h2 class='text-success'>Setup Concluído com Sucesso!</h2>
                            <p class='lead'>O banco de dados foi criado e configurado corretamente.</p>
                            
                            <div class='alert alert-info'>
                                <h5>Credenciais do Administrador:</h5>
                                <p><strong>Email:</strong> admin@marketplace.com</p>
                                <p><strong>Senha:</strong> admin123</p>
                            </div>
                            
                            <div class='d-grid gap-2 d-md-block'>
                                <a href='index.php' class='btn btn-primary'>Ir para Home</a>
                                <a href='auth/login.php' class='btn btn-outline-primary'>Fazer Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src='https://kit.fontawesome.com/your-font-awesome-kit.js'></script>
    </body>
    </html>";
    
} catch(PDOException $e) {
    echo "Erro na configuração do banco de dados: " . $e->getMessage();
}
?>