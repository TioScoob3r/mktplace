<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Verificar se há itens no carrinho
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit();
}

$message = '';
$order_success = false;

// Processar checkout tradicional (simulado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_checkout'])) {
    try {
        $conn->beginTransaction();
        
        // Calcular total
        $total = getCartTotal();
        
        // Criar pedido
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $conn->lastInsertId();
        
        // Adicionar itens do pedido
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Inserir item do pedido
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);
                
                // Adicionar aos downloads disponíveis
                $stmt = $conn->prepare("INSERT INTO downloads (user_id, product_id, order_id) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $order_id]);
                
                // Atualizar contador de downloads do produto
                $stmt = $conn->prepare("UPDATE products SET downloads_count = downloads_count + ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
            }
        }
        
        $conn->commit();
        
        // Limpar carrinho
        unset($_SESSION['cart']);
        
        $order_success = true;
        $message = showAlert('Compra realizada com sucesso! Você já pode acessar seus produtos.', 'success');
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = showAlert('Erro ao processar compra. Tente novamente.', 'danger');
    }
}

// Buscar itens do carrinho
$cart_products = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        
        $cart_products[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// Verificar se PixUp está configurado
$pixup_configurado = false;
try {
    $stmt = $conn->prepare("SELECT id FROM configuracoes_pixup WHERE ativo = 1 LIMIT 1");
    $stmt->execute();
    $pixup_configurado = $stmt->fetch() !== false;
} catch (Exception $e) {
    // Tabela pode não existir ainda
}

$page_title = 'Finalizar Compra';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <?php if ($message): ?>
        <div class="row">
            <div class="col-12">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($order_success): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                        <h2 class="text-success">Compra Realizada com Sucesso!</h2>
                        <p class="lead">Obrigado pela sua compra. Seus produtos já estão disponíveis para download.</p>
                        
                        <div class="d-grid gap-2 d-md-block mt-4">
                            <a href="downloads.php" class="btn btn-success btn-lg">
                                <i class="fas fa-download"></i> Acessar Meus Produtos
                            </a>
                            <a href="history.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-history"></i> Ver Histórico
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-shopping-bag"></i> Continuar Comprando
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-credit-card"></i> Finalizar Compra</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Produtos</a></li>
                        <li class="breadcrumb-item"><a href="cart.php">Carrinho</a></li>
                        <li class="breadcrumb-item active">Checkout</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Revisão do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_products as $product): ?>
                        <div class="row align-items-center py-3 border-bottom">
                            <div class="col-md-2">
                                <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/80x80/f8f9fa/6c757d?text=P'; ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height: 60px;">
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted">Quantidade: <?php echo $product['quantity']; ?></small>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong><?php echo formatMoney($product['subtotal']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="row pt-3">
                            <div class="col-md-8">
                                <strong>Total do Pedido:</strong>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong class="text-primary h5"><?php echo formatMoney($total); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações Importantes</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success"></i> Produtos digitais disponíveis imediatamente após a compra</li>
                            <li class="mb-2"><i class="fas fa-check text-success"></i> Downloads ilimitados dos seus produtos</li>
                            <li class="mb-2"><i class="fas fa-check text-success"></i> Histórico completo de compras</li>
                            <li class="mb-0"><i class="fas fa-check text-success"></i> Suporte completo ao cliente</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Formas de Pagamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Resumo:</strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span><?php echo formatMoney($total); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Taxa de processamento:</span>
                            <span class="text-success">Grátis</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary h5"><?php echo formatMoney($total); ?></strong>
                        </div>
                        
                        <!-- Opções de Pagamento -->
                        <?php if ($pixup_configurado): ?>
                        <div class="d-grid gap-2 mb-3">
                            <a href="checkout_pix_new.php" class="btn btn-success btn-lg">
                                <i class="fas fa-qrcode"></i> Pagar com Pix
                            </a>
                            <small class="text-center text-muted">Pagamento instantâneo e seguro</small>
                        </div>
                        
                        <hr>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <small><i class="fas fa-exclamation-triangle"></i> Pix não configurado pelo administrador</small>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="d-grid gap-2">
                                <button type="submit" name="process_checkout" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock"></i> Pagamento Simulado
                                </button>
                                <small class="text-center text-muted">Para demonstração</small>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> Voltar ao Carrinho
                                </a>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Compra 100% segura
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>