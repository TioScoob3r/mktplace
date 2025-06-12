<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

$message = '';

// Adicionar produto ao carrinho
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    
    // Verificar se o produto existe
    $stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Adicionar ou incrementar quantidade
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]++;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
        
        $message = showAlert("Produto '{$product['name']}' adicionado ao carrinho!", 'success');
    } else {
        $message = showAlert('Produto não encontrado.', 'danger');
    }
}

// Remover produto do carrinho
if (isset($_POST['remove_from_cart'])) {
    $product_id = (int)$_POST['product_id'];
    unset($_SESSION['cart'][$product_id]);
    $message = showAlert('Produto removido do carrinho.', 'info');
}

// Atualizar quantidade
if (isset($_POST['update_quantity'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
        $message = showAlert('Quantidade atualizada.', 'success');
    } else {
        unset($_SESSION['cart'][$product_id]);
        $message = showAlert('Produto removido do carrinho.', 'info');
    }
}

// Buscar detalhes dos produtos no carrinho
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

$page_title = 'Carrinho de Compras';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1><i class="fas fa-shopping-cart"></i> Carrinho de Compras</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Produtos</a></li>
                    <li class="breadcrumb-item active">Carrinho</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="row">
            <div class="col-12">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Itens no Carrinho</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($cart_products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h4>Seu carrinho está vazio</h4>
                            <p class="text-muted">Que tal adicionar alguns produtos?</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Ver Produtos
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart_products as $product): ?>
                        <div class="row align-items-center py-3 border-bottom">
                            <div class="col-md-2">
                                <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/100x100/f8f9fa/6c757d?text=Produto'; ?>" 
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height: 80px;">
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted"><?php echo formatMoney($product['price']); ?> cada</small>
                            </div>
                            <div class="col-md-3">
                                <form method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" 
                                           min="0" max="10" class="form-control form-control-sm me-2" style="width: 80px;">
                                    <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-2">
                                <strong><?php echo formatMoney($product['subtotal']); ?></strong>
                            </div>
                            <div class="col-md-1">
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Subtotal:</span>
                        <strong><?php echo formatMoney($total); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Taxa de processamento:</span>
                        <span class="text-success">Grátis</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-primary h5"><?php echo formatMoney($total); ?></strong>
                    </div>
                    
                    <?php if (!empty($cart_products)): ?>
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Finalizar Compra
                            </a>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Continuar Comprando
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Produtos Recomendados -->
            <?php
            $stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY downloads_count DESC LIMIT 3");
            $stmt->execute();
            $recommended = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (!empty($recommended)): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-star"></i> Produtos Populares</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($recommended as $product): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/60x60/f8f9fa/6c757d?text=P'; ?>" 
                             class="rounded me-3" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?>...</h6>
                            <small class="text-primary"><?php echo formatMoney($product['price']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>