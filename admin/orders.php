<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Ver detalhes do pedido
$view_order = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    
    // Buscar dados do pedido
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
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

// Buscar pedidos
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(o.created_at) = CURRENT_DATE()";
            break;
        case 'week':
            $where_conditions[] = "o.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "MONTH(o.created_at) = MONTH(CURRENT_DATE()) AND YEAR(o.created_at) = YEAR(CURRENT_DATE())";
            break;
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$stmt = $conn->prepare("
    SELECT o.*, u.name as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$page_title = 'Gestão de Pedidos';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$home_path = '../index.php';
$pages_path = '../pages/';
$auth_path = '../auth/';
$admin_path = '';
include 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php if ($view_order): ?>
        <!-- Detalhes do Pedido -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-eye"></i> Detalhes do Pedido #<?php echo $view_order['id']; ?></h1>
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Itens do Pedido</h5>
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
                                <td><strong>ID:</strong></td>
                                <td>#<?php echo $view_order['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cliente:</strong></td>
                                <td><?php echo htmlspecialchars($view_order['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($view_order['customer_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $view_order['status'] === 'completed' ? 'success' : ($view_order['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($view_order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Data:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($view_order['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td><strong class="text-primary"><?php echo formatMoney($view_order['total_amount']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Lista de Pedidos -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-shopping-cart"></i> Gestão de Pedidos</h1>
        </div>
        
        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Cliente, email ou ID do pedido">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Concluído</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label">Período</label>
                        <select class="form-select" id="date" name="date">
                            <option value="">Todos</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Hoje</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Última semana</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Este mês</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de Pedidos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pedidos Realizados (<?php echo count($orders); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h4>Nenhum pedido encontrado</h4>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Email</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                    <td><?php echo formatMoney($order['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>