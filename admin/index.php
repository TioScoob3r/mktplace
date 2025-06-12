<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

// Estatísticas do dashboard
$stats = [];

// Total de vendas
$stmt = $conn->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM orders WHERE status = 'completed'");
$stmt->execute();
$sales_data = $stmt->fetch();
$stats['total_orders'] = $sales_data['total_orders'] ?? 0;
$stats['total_revenue'] = $sales_data['total_revenue'] ?? 0;

// Faturamento do mês atual
$stmt = $conn->prepare("SELECT COUNT(*) as monthly_orders, SUM(total_amount) as monthly_revenue FROM orders WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stmt->execute();
$monthly_data = $stmt->fetch();
$stats['monthly_orders'] = $monthly_data['monthly_orders'] ?? 0;
$stats['monthly_revenue'] = $monthly_data['monthly_revenue'] ?? 0;

// Total de usuários
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'");
$stmt->execute();
$users_data = $stmt->fetch();
$stats['total_users'] = $users_data['total_users'] ?? 0;

// Total de produtos
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
$stmt->execute();
$products_data = $stmt->fetch();
$stats['total_products'] = $products_data['total_products'] ?? 0;

// Produtos mais vendidos
$stmt = $conn->prepare("
    SELECT p.name, p.price, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM products p 
    JOIN order_items oi ON p.id = oi.product_id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE o.status = 'completed' 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$stmt->execute();
$top_products = $stmt->fetchAll();

// Vendas dos últimos 7 dias para gráfico
$stmt = $conn->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
    FROM orders 
    WHERE status = 'completed' AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute();
$daily_sales = $stmt->fetchAll();

// Últimas compras
$stmt = $conn->prepare("
    SELECT o.id, o.total_amount, o.created_at, u.name as customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'completed'
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

$page_title = 'Dashboard Admin';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$home_path = '../index.php';
$pages_path = '../pages/';
$auth_path = '../auth/';
$admin_path = '';
include 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <!-- Estatísticas Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Vendas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_orders']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Faturamento Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($stats['total_revenue']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Faturamento do Mês
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($stats['monthly_revenue']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total de Usuários
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_users']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos e Tabelas -->
    <div class="row">
        <!-- Gráfico de Vendas -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas dos Últimos 7 Dias</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produtos Mais Vendidos -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Produtos Mais Vendidos</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-muted text-center">Nenhuma venda registrada ainda.</p>
                    <?php else: ?>
                        <?php foreach ($top_products as $product): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted"><?php echo $product['total_sold']; ?> vendas</small>
                            </div>
                            <div class="text-end">
                                <strong><?php echo formatMoney($product['revenue']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Compras -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Últimas Compras</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <p class="text-muted text-center">Nenhuma compra registrada ainda.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Email</th>
                                        <th>Valor</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                                        <td><?php echo formatMoney($order['total_amount']); ?></td>
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de vendas
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            <?php 
            $dates = [];
            $revenues = [];
            foreach ($daily_sales as $sale) {
                $dates[] = "'" . date('d/m', strtotime($sale['date'])) . "'";
                $revenues[] = $sale['revenue'];
            }
            echo implode(',', $dates);
            ?>
        ],
        datasets: [{
            label: 'Faturamento (R$)',
            data: [<?php echo implode(',', $revenues); ?>],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Vendas dos Últimos 7 Dias'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toFixed(2);
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>