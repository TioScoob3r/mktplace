<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->getConnection();

// Buscar produtos disponíveis para download
$stmt = $conn->prepare("
    SELECT d.*, p.name, p.description, p.image, p.file_path, o.created_at as purchase_date
    FROM downloads d
    JOIN products p ON d.product_id = p.id
    JOIN orders o ON d.order_id = o.id
    WHERE d.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$downloads = $stmt->fetchAll();

// Processar download
if (isset($_GET['download']) && isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    
    // Verificar se o usuário tem acesso ao produto
    $stmt = $conn->prepare("SELECT * FROM downloads WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product_id]);
    $download_access = $stmt->fetch();
    
    if ($download_access) {
        // Incrementar contador de downloads
        $stmt = $conn->prepare("UPDATE downloads SET download_count = download_count + 1 WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        
        // Em um sistema real, aqui seria feito o download do arquivo
        $message = showAlert('Download iniciado! Em um sistema real, o arquivo seria baixado automaticamente.', 'success');
    } else {
        $message = showAlert('Você não tem acesso a este produto.', 'danger');
    }
}

$page_title = 'Meus Downloads';
$css_path = '../assets/css/style.css';
$home_path = '../index.php';
$pages_path = '';
$auth_path = '../auth/';
include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1><i class="fas fa-download"></i> Meus Downloads</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item active">Downloads</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="row">
            <div class="col-12">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cloud-download-alt"></i> Produtos Disponíveis</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($downloads)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-download fa-3x text-muted mb-3"></i>
                            <h4>Nenhum produto disponível</h4>
                            <p class="text-muted">Você ainda não comprou nenhum produto digital.</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag"></i> Ver Produtos
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($downloads as $download): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?php echo !empty($download['image']) ? $download['image'] : 'https://via.placeholder.com/300x200/f8f9fa/6c757d?text=Produto'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($download['name']); ?>" style="height: 200px; object-fit: cover;">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?php echo htmlspecialchars($download['name']); ?></h5>
                                        <p class="card-text flex-grow-1"><?php echo htmlspecialchars($download['description']); ?></p>
                                        
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    Comprado em <?php echo date('d/m/Y', strtotime($download['purchase_date'])); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-download"></i> 
                                                    <?php echo $download['download_count']; ?> downloads
                                                </small>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <a href="?download=1&product_id=<?php echo $download['product_id']; ?>" 
                                                   class="btn btn-success">
                                                    <i class="fas fa-download"></i> Baixar Produto
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($downloads)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informações Importantes</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-shield-alt"></i> Seus Direitos</h6>
                        <ul class="mb-0">
                            <li>Você pode baixar seus produtos quantas vezes quiser</li>
                            <li>Os produtos ficam disponíveis permanentemente em sua conta</li>
                            <li>Em caso de problemas, entre em contato com o suporte</li>
                            <li>Mantenha seus produtos em local seguro como backup</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>