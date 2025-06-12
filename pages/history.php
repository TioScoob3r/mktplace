<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Buscar histórico de compras do usuário
$stmt = $conn->prepare("
    SELECT o.*, 
           COUNT(oi.id) as total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Ver detalhes do pedido
$view_order = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    
    // Verificar se o pedido pertence ao usuário logado
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $view_order = $stmt->fetch();
    
    if ($view_order) {
        // Buscar itens do pedido
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $view_order['items'] = $stmt->fetchAll();
    }
}

$page_title = 'Histórico de Compras';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <?php if ($view_order): ?>
        <!-- Detalhes do Pedido -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1><i class="fas fa-receipt"></i> Detalhes do Pedido #<?php echo $view_order['id']; ?></h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="history.php">Histórico</a></li>
                                <li class="breadcrumb-item active">Pedido #<?php echo $view_order['id']; ?></li>
                            </ol>
                        </nav>
                    </div>
                    <a href="history.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Itens Comprados</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($view_order['items'] as $item): ?>
                        <div class="row align-items-center py-3 border-bottom">
                            <div class="col-md-2">
                                <img src="<?php echo !empty($item['product_image']) ? $item['product_image'] : 'https://via.placeholder.com/80x80/f8f9fa/6c757d?text=P'; ?>" 
                                     class="img-fluid rounded" alt="Produto" style="max-height: 60px;">
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                <small class="text-muted">Quantidade: <?php echo $item['quantity']; ?></small>
                            </div>
                            <div class="col-md-2">
                                <strong><?php echo formatMoney($item['price']); ?></strong>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong><?php echo formatMoney($item['price'] * $item['quantity']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="row pt-3">
                            <div class="col-md-8">
                                <strong>Total do Pedido:</strong>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong class="text-primary h5"><?php echo formatMoney($view_order['total_amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Número:</strong></td>
                                <td>#<?php echo $view_order['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $view_order['status'] === 'completed' ? 'success' : ($view_order['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php 
                                        switch($view_order['status']) {
                                            case 'completed': echo 'Concluído'; break;
                                            case 'pending': echo 'Pendente'; break;
                                            case 'cancelled': echo 'Cancelado'; break;
                                            default: echo ucfirst($view_order['status']);
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Data da Compra:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($view_order['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Pago:</strong></td>
                                <td><strong class="text-primary"><?php echo formatMoney($view_order['total_amount']); ?></strong></td>
                            </tr>
                        </table>
                        
                        <?php if ($view_order['status'] === 'completed'): ?>
                        <div class="d-grid gap-2 mt-3">
                            <a href="downloads.php" class="btn btn-success">
                                <i class="fas fa-download"></i> Acessar Downloads
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Lista de Pedidos -->
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-history"></i> Histórico de Compras</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item active">Histórico</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Suas Compras</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h4>Nenhuma compra realizada</h4>
                                <p class="text-muted">Você ainda não fez nenhuma compra em nossa loja.</p>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag"></i> Ver Produtos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Itens</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo $order['total_items']; ?> item(s)</td>
                                            <td><?php echo formatMoney($order['total_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                    <?php 
                                                    switch($order['status']) {
                                                        case 'completed': echo 'Concluído'; break;
                                                        case 'pending': echo 'Pendente'; break;
                                                        case 'cancelled': echo 'Cancelado'; break;
                                                        default: echo ucfirst($order['status']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="history.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>
                                                    <?php if ($order['status'] === 'completed'): ?>
                                                        <a href="downloads.php" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-download"></i> Downloads
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($orders)): ?>
        <!-- Resumo das Compras -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Total de Pedidos</h5>
                        <h2 class="text-primary"><?php echo count($orders); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-success">Total Gasto</h5>
                        <h2 class="text-success">
                            <?php 
                            $total_spent = array_sum(array_column($orders, 'total_amount'));
                            echo formatMoney($total_spent);
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title text-info">Ticket Médio</h5>
                        <h2 class="text-info">
                            <?php 
                            $avg_order = count($orders) > 0 ? $total_spent / count($orders) : 0;
                            echo formatMoney($avg_order);
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>