<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/pix_functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$order_id) {
    header('Location: history.php');
    exit();
}

// Verificar se o pedido pertence ao usuário
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: history.php');
    exit();
}

// Buscar transação Pix
$pix_transaction = getPixTransactionByOrderId($conn, $order_id);

if (!$pix_transaction) {
    header('Location: history.php');
    exit();
}

// Buscar itens do pedido
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$page_title = 'Status do Pagamento Pix';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1><i class="fas fa-qrcode"></i> Status do Pagamento Pix</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="history.php">Histórico</a></li>
                    <li class="breadcrumb-item active">Status Pix</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Status do Pagamento -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Status Atual</h5>
                </div>
                <div class="card-body text-center">
                    <div id="paymentStatus">
                        <?php
                        $status_class = '';
                        $status_icon = '';
                        $status_text = '';
                        
                        switch ($pix_transaction['status']) {
                            case 'PAID':
                                $status_class = 'success';
                                $status_icon = 'check-circle';
                                $status_text = 'Pagamento Confirmado!';
                                break;
                            case 'PENDING':
                                $status_class = 'warning';
                                $status_icon = 'clock';
                                $status_text = 'Aguardando Pagamento';
                                break;
                            case 'CANCELLED':
                                $status_class = 'danger';
                                $status_icon = 'times-circle';
                                $status_text = 'Pagamento Cancelado';
                                break;
                            case 'EXPIRED':
                                $status_class = 'secondary';
                                $status_icon = 'times-circle';
                                $status_text = 'Pagamento Expirado';
                                break;
                            default:
                                $status_class = 'info';
                                $status_icon = 'question-circle';
                                $status_text = 'Status Desconhecido';
                        }
                        ?>
                        
                        <i class="fas fa-<?php echo $status_icon; ?> fa-4x text-<?php echo $status_class; ?> mb-3"></i>
                        <h3 class="text-<?php echo $status_class; ?>"><?php echo $status_text; ?></h3>
                        
                        <?php if ($pix_transaction['status'] === 'PENDING'): ?>
                            <p class="text-muted">O pagamento será confirmado automaticamente assim que for processado.</p>
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            <small class="text-muted">Verificando automaticamente...</small>
                        <?php elseif ($pix_transaction['status'] === 'PAID'): ?>
                            <p class="text-success">Seus produtos já estão disponíveis para download!</p>
                            <a href="downloads.php" class="btn btn-success">
                                <i class="fas fa-download"></i> Acessar Downloads
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Itens do Pedido -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Itens do Pedido</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
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
                            <strong class="text-primary h5"><?php echo formatMoney($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Informações da Transação -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Detalhes da Transação</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Pedido:</strong></td>
                            <td>#<?php echo $order['id']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Valor:</strong></td>
                            <td><?php echo formatMoney($pix_transaction['amount']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>CPF:</strong></td>
                            <td><?php echo formatCPF($pix_transaction['payer_document']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><?php echo htmlspecialchars($pix_transaction['payer_email']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pix_transaction['created_at'])); ?></td>
                        </tr>
                        <?php if ($pix_transaction['paid_at']): ?>
                        <tr>
                            <td><strong>Pago em:</strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pix_transaction['paid_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($pix_transaction['transaction_id']): ?>
                        <tr>
                            <td><strong>ID Transação:</strong></td>
                            <td><small><?php echo htmlspecialchars($pix_transaction['transaction_id']); ?></small></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Ações -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($pix_transaction['status'] === 'PAID'): ?>
                            <a href="downloads.php" class="btn btn-success">
                                <i class="fas fa-download"></i> Acessar Downloads
                            </a>
                        <?php endif; ?>
                        
                        <a href="history.php" class="btn btn-outline-primary">
                            <i class="fas fa-history"></i> Ver Histórico
                        </a>
                        
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-shopping-bag"></i> Continuar Comprando
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($pix_transaction['status'] === 'PENDING'): ?>
// Verificar status do pagamento automaticamente
let checkCount = 0;
const maxChecks = 120; // 20 minutos

function checkPaymentStatus() {
    if (checkCount >= maxChecks) {
        return;
    }
    
    fetch('pix_check_status.php?order=<?php echo $order_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'PAID') {
                location.reload(); // Recarregar página para mostrar status atualizado
            } else if (data.status === 'CANCELLED' || data.status === 'EXPIRED') {
                location.reload();
            } else {
                checkCount++;
                setTimeout(checkPaymentStatus, 10000); // Verificar novamente em 10 segundos
            }
        })
        .catch(error => {
            console.error('Erro ao verificar status:', error);
            checkCount++;
            if (checkCount < maxChecks) {
                setTimeout(checkPaymentStatus, 10000);
            }
        });
}

// Iniciar verificação após 5 segundos
setTimeout(checkPaymentStatus, 5000);
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>