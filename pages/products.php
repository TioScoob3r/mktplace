<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Buscar todos os produtos ativos
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Produtos';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1><i class="fas fa-shopping-bag"></i> Produtos Digitais</h1>
                    <p class="text-muted">Encontre os melhores produtos para seu negócio</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary fs-6"><?php echo count($products); ?> produtos disponíveis</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Nenhum produto encontrado</h4>
                    <p>Ainda não temos produtos disponíveis. Volte em breve!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm hover-card">
                    <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/400x250/f8f9fa/6c757d?text=Produto'; ?>" 
                         class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 250px; object-fit: cover;">
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h4 text-primary mb-0"><?php echo formatMoney($product['price']); ?></span>
                                <small class="text-muted">
                                    <i class="fas fa-download"></i> <?php echo $product['downloads_count']; ?> downloads
                                </small>
                            </div>
                            
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                                        <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="../auth/login.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Faça login para comprar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>