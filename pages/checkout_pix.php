<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/pix_functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Verificar se há itens no carrinho
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: products.php');
    exit();
}

$message = '';
$pix_data = null;
$order_created = false;

// Processar dados do cliente para Pix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_pix'])) {
    $payer_name = sanitizeInput($_POST['payer_name']);
    $payer_document = cleanCPF($_POST['payer_document']);
    $payer_email = sanitizeInput($_POST['payer_email']);
    
    // Validações
    if (empty($payer_name) || empty($payer_document) || empty($payer_email)) {
        $message = showAlert('Por favor, preencha todos os campos.', 'danger');
    } elseif (!validateCPF($payer_document)) {
        $message = showAlert('CPF inválido.', 'danger');
    } elseif (!validateEmail($payer_email)) {
        $message = showAlert('Email inválido.', 'danger');
    } else {
        try {
            $conn->beginTransaction();
            
            // Calcular total
            $total = getCartTotal();
            
            // Criar pedido
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $conn->lastInsertId();
            
            // Adicionar itens do pedido
            foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if ($product) {
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);
                }
            }
            
            // Criar transação Pix
            $payer_data = [
                'name' => $payer_name,
                'document' => $payer_document,
                'email' => $payer_email
            ];
            
            $pix_transaction = createPixTransaction($conn, $order_id, $_SESSION['user_id'], $total, $payer_data);
            
            // Gerar QR Code via PixUp
            $pixup = new PixUpAPI();
            
            $order_data = [
                'amount' => $total,
                'postback_url' => getPostbackUrl(),
                'external_id' => $pix_transaction['external_id'],
                'payer_name' => $payer_name,
                'payer_document' => $payer_document,
                'payer_email' => $payer_email
            ];
            
            $pix_response = $pixup->generatePixQRCode($order_data);
            
            // Atualizar transação com dados da PixUp
            updatePixTransactionStatus(
                $conn, 
                $pix_transaction['external_id'], 
                'PENDING', 
                $pix_response['transactionId']
            );
            
            $conn->commit();
            
            $pix_data = $pix_response;
            $pix_data['order_id'] = $order_id;
            $pix_data['pix_transaction_id'] = $pix_transaction['id'];
            $order_created = true;
            
            // Limpar carrinho
            unset($_SESSION['cart']);
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = showAlert('Erro ao gerar pagamento Pix: ' . $e->getMessage(), 'danger');
        }
    }
}

// Buscar itens do carrinho se ainda não processou
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

$page_title = 'Pagamento Pix';
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
    
    <?php if ($pix_data): ?>
        <!-- Pagamento Pix Gerado -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0"><i class="fas fa-qrcode"></i> Pagamento Pix Gerado</h4>
                    </div>
                    <div class="card-body text-center">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Instruções de Pagamento</h5>
                            <p class="mb-0">Escaneie o QR Code ou copie o código Pix para realizar o pagamento</p>
                        </div>
                        
                        <!-- QR Code -->
                        <div class="mb-4">
                            <h5>QR Code Pix</h5>
                            <?php if (isset($pix_data['qrcode']) && filter_var($pix_data['qrcode'], FILTER_VALIDATE_URL)): ?>
                                <img src="<?php echo $pix_data['qrcode']; ?>" alt="QR Code Pix" class="img-fluid" style="max-width: 300px;">
                            <?php else: ?>
                                <div class="bg-light p-4 rounded">
                                    <i class="fas fa-qrcode fa-5x text-muted"></i>
                                    <p class="mt-2 text-muted">QR Code será exibido aqui</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Código Copia e Cola -->
                        <?php if (isset($pix_data['qrcode']) && !filter_var($pix_data['qrcode'], FILTER_VALIDATE_URL)): ?>
                        <div class="mb-4">
                            <h5>Código Pix (Copia e Cola)</h5>
                            <div class="input-group">
                                <input type="text" class="form-control" id="pixCode" value="<?php echo htmlspecialchars($pix_data['qrcode']); ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyPixCode()">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Informações do Pagamento -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-receipt"></i> Detalhes do Pagamento</h6>
                                        <p><strong>Pedido:</strong> #<?php echo $pix_data['order_id']; ?></p>
                                        <p><strong>Valor:</strong> <?php echo formatMoney($pix_data['amount']); ?></p>
                                        <p><strong>Status:</strong> 
                                            <span class="badge bg-warning">Aguardando Pagamento</span>
                                        </p>
                                        <?php if (isset($pix_data['dueDate'])): ?>
                                        <p><strong>Vencimento:</strong> <?php echo date('d/m/Y H:i', strtotime($pix_data['dueDate'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-clock"></i> Status do Pagamento</h6>
                                        <div id="paymentStatus">
                                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                            Verificando pagamento...
                                        </div>
                                        <small class="text-muted">Atualizando automaticamente a cada 10 segundos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="pix_status.php?order=<?php echo $pix_data['order_id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Acompanhar Status
                            </a>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-bag"></i> Continuar Comprando
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Formulário de Dados para Pix -->
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-credit-card"></i> Pagamento via Pix</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="products.php">Produtos</a></li>
                        <li class="breadcrumb-item"><a href="cart.php">Carrinho</a></li>
                        <li class="breadcrumb-item active">Pagamento Pix</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user"></i> Dados para Pagamento Pix</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="payer_name" class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" id="payer_name" name="payer_name" 
                                           value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required>
                                    <div class="form-text">Nome que aparecerá na cobrança Pix</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="payer_document" class="form-label">CPF *</label>
                                    <input type="text" class="form-control" id="payer_document" name="payer_document" 
                                           placeholder="000.000.000-00" maxlength="14" required>
                                    <div class="form-text">Necessário para identificação do pagamento</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="payer_email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="payer_email" name="payer_email" 
                                           value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>" required>
                                    <div class="form-text">Para receber confirmação do pagamento</div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Sobre o Pagamento Pix</h6>
                                <ul class="mb-0">
                                    <li>Pagamento instantâneo e seguro</li>
                                    <li>Disponível 24h por dia, 7 dias por semana</li>
                                    <li>Confirmação automática em segundos</li>
                                    <li>Seus produtos serão liberados imediatamente após a confirmação</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="generate_pix" class="btn btn-success btn-lg">
                                    <i class="fas fa-qrcode"></i> Gerar Pagamento Pix
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Resumo do Pedido -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Resumo do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_products as $product): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?>...</h6>
                                <small class="text-muted">Qtd: <?php echo $product['quantity']; ?></small>
                            </div>
                            <span><?php echo formatMoney($product['subtotal']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-primary h5"><?php echo formatMoney($total); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Máscara para CPF
document.getElementById('payer_document').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});

// Função para copiar código Pix
function copyPixCode() {
    const pixCode = document.getElementById('pixCode');
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Feedback visual
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-primary');
    }, 2000);
}

<?php if ($pix_data): ?>
// Verificar status do pagamento automaticamente
let checkCount = 0;
const maxChecks = 60; // 10 minutos (60 * 10 segundos)

function checkPaymentStatus() {
    if (checkCount >= maxChecks) {
        document.getElementById('paymentStatus').innerHTML = 
            '<i class="fas fa-clock text-warning"></i> Tempo limite de verificação atingido';
        return;
    }
    
    fetch('pix_check_status.php?order=<?php echo $pix_data['order_id']; ?>')
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('paymentStatus');
            
            if (data.status === 'PAID') {
                statusDiv.innerHTML = '<i class="fas fa-check-circle text-success"></i> Pagamento Confirmado!';
                setTimeout(() => {
                    window.location.href = 'downloads.php';
                }, 2000);
            } else if (data.status === 'CANCELLED' || data.status === 'EXPIRED') {
                statusDiv.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Pagamento ' + data.status;
            } else {
                statusDiv.innerHTML = '<i class="fas fa-clock text-warning"></i> Aguardando pagamento...';
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