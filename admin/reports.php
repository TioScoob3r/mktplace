<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Relatório de vendas por período
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE status = 'completed' 
    AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$period_stats = $stmt->fetch();

// Vendas por produto no período
$stmt = $conn->prepare("
    SELECT 
        p.name,
        p.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY revenue DESC
");
$stmt->execute([$date_from, $date_to]);
$product_sales = $stmt->fetchAll();

// Vendas por dia no período
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total_amount) as revenue
    FROM orders
    WHERE status = 'completed'
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$date_from, $date_to]);
$daily_sales = $stmt->fetchAll();

// Top clientes no período
$stmt = $conn->prepare("
    SELECT 
        u.name,
        u.email,
        COUNT(o.id) as total_orders,
        SUM(o.total_amount) as total_spent
    FROM users u
    JOIN orders o ON u.id = o.user_id
    WHERE o.status = 'completed'
    AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_customers = $stmt->fetchAll();

$page_title = 'Relatórios de Vendas';
$css_path = '../assets/css/style.css';
$js_path = '../assets/js/script.js';
$home_path = '../index.php';
$pages_path = '../pages/';
$auth_path = '../auth/';
$admin_path = '';
include 'includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-bar"></i> Relatórios de Vendas</h1>
        <button type="button" class="btn btn-success" onclick="exportReport()">
            <i class="fas fa-download"></i> Exportar CSV
        </button>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <!-- Filtros de Data -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resumo do Período -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Pedidos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($period_stats['total_orders'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Faturamento Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($period_stats['total_revenue'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Ticket Médio
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($period_stats['avg_order_value'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Vendas por Dia</h6>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabelas de Relatórios -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Produtos Mais Vendidos</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($product_sales)): ?>
                        <p class="text-muted text-center">Nenhuma venda no período selecionado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Vendas</th>
                                        <th>Receita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($product_sales as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                        <td><?php echo formatMoney($product['revenue']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Top Clientes</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_customers)): ?>
                        <p class="text-muted text-center">Nenhum cliente no período selecionado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Pedidos</th>
                                        <th>Total Gasto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($customer['name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                        </td>
                                        <td><?php echo $customer['total_orders']; ?></td>
                                        <td><?php echo formatMoney($customer['total_spent']); ?></td>
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
// Gráfico de vendas diárias
const ctx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesChart = new Chart(ctx, {
    type: 'bar',
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
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Vendas Diárias no Período'
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

function exportReport() {
    // Simular exportação CSV
    alert('Funcionalidade de exportação seria implementada aqui.\nDados do período: <?php echo $date_from; ?> a <?php echo $date_to; ?>');
}
</script>

<?php include 'includes/admin_footer.php'; ?>