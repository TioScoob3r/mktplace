<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Buscar produtos em destaque
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Marketplace Digital</h1>
                    <p class="lead">Encontre os melhores produtos digitais para seu negócio</p>
                    <a href="pages/products.php" class="btn btn-light btn-lg">Ver Produtos</a>
                </div>
                <div class="col-lg-6">
                    <img src="https://via.placeholder.com/600x400/0066cc/ffffff?text=Produtos+Digitais" 
                         class="img-fluid rounded" alt="Produtos Digitais">
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Produtos em Destaque</h2>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/300x200/f8f9fa/6c757d?text=Produto'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                                    <form method="POST" action="pages/cart.php">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                                            <i class="fas fa-shopping-cart"></i> Comprar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="pages/products.php" class="btn btn-outline-primary">Ver Todos os Produtos</a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>