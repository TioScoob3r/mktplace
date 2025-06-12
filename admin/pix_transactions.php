<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/pix_helper.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';

// Ver detalhes da transação
$view_transaction = null;
if (isset($_GET['view'])) {
    $transaction_id = (int)$_GET['view'];
    
    // Buscar dados da transação
    $stmt = $conn->prepare("
        SELECT t.*, o.id as order_id, u.name as customer_name, u.email as customer_email
        FROM transacoes_pix t
        JOIN orders o ON t.pedido_id = o.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transaction_id]);
    $view_transaction = $stmt->fetch();
    
    if ($view_transaction) {
        // Buscar itens do pedido
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image as product_image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$view_transaction['order_id']]);
        $view_transaction['items'] = $stmt->fetchAll();
    }
}

// Buscar transações Pix
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR t.external_id LIKE ? OR t.transaction_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(t.created_at) = CURRENT_DATE()";
            break;
        case 'week':
            $where_conditions[] = "t.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "MONTH(t.created_at) = MONTH(CURRENT_DATE()) AND YEAR(t.created_at) = YEAR(CURRENT_DATE())";
            break;
    }
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

$stmt = $conn->prepare("
    SELECT t.*, u.name as customer_name, u.email as customer_email, o.id as order_id
    FROM transacoes_pix t
    JOIN users u ON t.user_id = u.id
    JOIN orders o ON t.pedido_id = o.id
    $where_clause
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$page_title = 'Transações Pix';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$home_path = '../index.php';
$pages_path = '../pages/';
$auth_path = '../auth/';
$admin_path = '';
include 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php if ($view_transaction): ?>
        <!-- Detalhes da Transação -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-qrcode"></i> Transação Pix #<?php echo $view_transaction['id']; ?></h1>
            <a href="pix_transactions.php" class="btn btn-outline-secondary">
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
                        <?php foreach ($view_transaction['items'] as $item): ?>
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
                                <strong>Total da Transação:</strong>
                            </div>
                            <div class="col-md-4 text-end">
                                <strong class="text-primary h5"><?php echo formatMoney($view_transaction['amount']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações da Transação</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td>#<?php echo $view_transaction['id']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Pedido:</strong></td>
                                <td><a href="orders.php?view=<?php echo $view_transaction['order_id']; ?>">#<?php echo $view_transaction['order_id']; ?></a></td>
                            </tr>
                            <tr>
                                <td><strong>Cliente:</strong></td>
                                <td><?php echo htmlspecialchars($view_transaction['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($view_transaction['customer_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>CPF:</strong></td>
                                <td><?php echo formatarCPF($view_transaction['payer_document']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $view_transaction['status'] === 'PAID' ? 'success' : 
                                            ($view_transaction['status'] === 'PENDING' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo $view_transaction['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Valor:</strong></td>
                                <td><strong class="text-primary"><?php echo formatMoney($view_transaction['amount']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>Criado em:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($view_transaction['created_at'])); ?></td>
                            </tr>
                            <?php if ($view_transaction['paid_at']): ?>
                            <tr>
                                <td><strong>Pago em:</strong></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($view_transaction['paid_at'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($view_transaction['external_id']): ?>
                            <tr>
                                <td><strong>ID Externo:</strong></td>
                                <td><small><?php echo htmlspecialchars($view_transaction['external_id']); ?></small></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($view_transaction['transaction_id']): ?>
                            <tr>
                                <td><strong>ID PixUp:</strong></td>
                                <td><small><?php echo htmlspecialchars($view_transaction['transaction_id']); ?></small></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Lista de Transações -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-qrcode"></i> Transações Pix</h1>
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
                               value="<?php echo htmlspecialchars($search); ?>" placeholder="Cliente, email ou ID da transação">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="PENDING" <?php echo $status_filter === 'PENDING' ? 'selected' : ''; ?>>Pendente</option>
                            <option value="PAID" <?php echo $status_filter === 'PAID' ? 'selected' : ''; ?>>Pago</option>
                            <option value="CANCELLED" <?php echo $status_filter === 'CANCELLED' ? 'selected' : ''; ?>>Cancelado</option>
                            <option value="EXPIRED" <?php echo $status_filter === 'EXPIRED' ? 'selected' : ''; ?>>Expirado</option>
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
        
        <!-- Lista de Transações -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transações Pix (<?php echo count($transactions); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                        <h4>Nenhuma transação encontrada</h4>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>CPF</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>#<?php echo $transaction['id']; ?></td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $transaction['order_id']; ?>">
                                            #<?php echo $transaction['order_id']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['customer_name']); ?></td>
                                    <td><?php echo formatarCPF($transaction['payer_document']); ?></td>
                                    <td><?php echo formatMoney($transaction['amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $transaction['status'] === 'PAID' ? 'success' : 
                                                ($transaction['status'] === 'PENDING' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo $transaction['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                    <td>
                                        <a href="pix_transactions.php?view=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-outline-primary">
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