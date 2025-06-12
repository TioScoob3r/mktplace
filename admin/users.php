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
$role_filter = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';

// Processar exclusão de usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Não permitir exclusão do próprio usuário
    if ($user_id === $_SESSION['user_id']) {
        $message = showAlert('Você não pode excluir sua própria conta.', 'warning');
    } else {
        try {
            $conn->beginTransaction();
            
            // Excluir downloads relacionados
            $stmt = $conn->prepare("DELETE FROM downloads WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Excluir itens de pedidos relacionados
            $stmt = $conn->prepare("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
            $stmt->execute([$user_id]);
            
            // Excluir pedidos
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            // Excluir usuário
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            $message = showAlert('Usuário excluído com sucesso!', 'success');
        } catch (Exception $e) {
            $conn->rollback();
            $message = showAlert('Erro ao excluir usuário.', 'danger');
        }
    }
}

// Buscar usuários
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Gestão de Usuários';
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
        <h1><i class="fas fa-users"></i> Gestão de Usuários</h1>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome ou email do usuário">
                </div>
                <div class="col-md-2">
                    <label for="role" class="form-label">Tipo</label>
                    <select class="form-select" id="role" name="role">
                        <option value="">Todos</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Cliente</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
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
    
    <!-- Lista de Usuários -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Usuários Cadastrados (<?php echo count($users); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4>Nenhum usuário encontrado</h4>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Data Cadastro</th>
                                <th>Compras</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <?php
                            // Contar compras do usuário
                            $stmt = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
                            $stmt->execute([$user['id']]);
                            $user_stats = $stmt->fetch();
                            ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                        <span class="badge bg-info ms-1">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Admin' : 'Cliente'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo $user_stats['total_orders']; ?></strong> compras
                                    <br><small class="text-muted"><?php echo formatMoney($user_stats['total_spent']); ?> total</small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewUserHistory(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

<!-- Modal Histórico do Usuário -->
<div class="modal fade" id="userHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history"></i> Histórico de Compras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userHistoryContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewUserHistory(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userHistoryModal'));
    const content = document.getElementById('userHistoryContent');
    
    // Mostrar loading
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Simular carregamento de dados (em um sistema real, seria uma requisição AJAX)
    setTimeout(() => {
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Funcionalidade de histórico detalhado seria implementada via AJAX em um sistema completo.
            </div>
            <p>Aqui seriam exibidas todas as compras do usuário ID: ${userId}</p>
        `;
    }, 1000);
}
</script>

<?php include 'includes/admin_footer.php'; ?>