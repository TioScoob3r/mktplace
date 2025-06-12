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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = (float)$_POST['price'];
        $status = sanitizeInput($_POST['status']);
        
        // Upload de imagem (simulado)
        $image = '';
        if (!empty($_POST['image_url'])) {
            $image = sanitizeInput($_POST['image_url']);
        }
        
        if (!empty($name) && !empty($description) && $price > 0) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $image, $status])) {
                $message = showAlert('Produto adicionado com sucesso!', 'success');
            } else {
                $message = showAlert('Erro ao adicionar produto.', 'danger');
            }
        } else {
            $message = showAlert('Por favor, preencha todos os campos obrigatórios.', 'danger');
        }
    }
    
    if (isset($_POST['edit_product'])) {
        $id = (int)$_POST['product_id'];
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = (float)$_POST['price'];
        $status = sanitizeInput($_POST['status']);
        $image = sanitizeInput($_POST['image_url']);
        
        if (!empty($name) && !empty($description) && $price > 0) {
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $image, $status, $id])) {
                $message = showAlert('Produto atualizado com sucesso!', 'success');
            } else {
                $message = showAlert('Erro ao atualizar produto.', 'danger');
            }
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = showAlert('Produto excluído com sucesso!', 'success');
        } else {
            $message = showAlert('Erro ao excluir produto.', 'danger');
        }
    }
}

// Buscar produtos
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("SELECT * FROM products $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = 'Gestão de Produtos';
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
        <h1><i class="fas fa-box"></i> Gestão de Produtos</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i> Adicionar Produto
        </button>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome ou descrição do produto">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Todos</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inativo</option>
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
    
    <!-- Lista de Produtos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Produtos Cadastrados (<?php echo count($products); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h4>Nenhum produto encontrado</h4>
                    <p class="text-muted">Adicione produtos para começar a vender.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagem</th>
                                <th>Nome</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>#<?php echo $product['id']; ?></td>
                                <td>
                                    <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://via.placeholder.com/50x50/f8f9fa/6c757d?text=P'; ?>" 
                                         class="rounded" alt="Produto" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo formatMoney($product['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo $product['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($product['downloads_count']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?')">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Modal Adicionar Produto -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Adicionar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Descrição *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Preço (R$) *</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="image_url" class="form-label">URL da Imagem</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   placeholder="https://exemplo.com/imagem.jpg">
                            <div class="form-text">Cole a URL de uma imagem para o produto</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="add_product" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Produto -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_product_id" name="product_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_name" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_description" class="form-label">Descrição *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_price" class="form-label">Preço (R$) *</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Ativo</option>
                                <option value="inactive">Inativo</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="edit_image_url" class="form-label">URL da Imagem</label>
                            <input type="url" class="form-control" id="edit_image_url" name="image_url">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">
                        <i class="fas fa-save"></i> Atualizar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_status').value = product.status;
    document.getElementById('edit_image_url').value = product.image || '';
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>

<?php include 'includes/admin_footer.php'; ?>