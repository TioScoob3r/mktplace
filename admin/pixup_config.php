<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/pixup_api.php';

requireLogin();
requireAdmin();

$db = new Database();
$conn = $db->getConnection();

$message = '';

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['salvar_config'])) {
        $client_id = sanitizeInput($_POST['client_id']);
        $client_secret = sanitizeInput($_POST['client_secret']);
        $ambiente = sanitizeInput($_POST['ambiente']);
        
        if (empty($client_id) || empty($client_secret)) {
            $message = showAlert('Por favor, preencha todos os campos obrigatórios.', 'danger');
        } else {
            try {
                // Desativar configurações anteriores
                $stmt = $conn->prepare("UPDATE configuracoes_pixup SET ativo = 0");
                $stmt->execute();
                
                // Inserir nova configuração
                $stmt = $conn->prepare("
                    INSERT INTO configuracoes_pixup (client_id, client_secret, ambiente, ativo) 
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$client_id, $client_secret, $ambiente]);
                
                // Limpar cache de tokens
                $conn->exec("DELETE FROM pixup_tokens");
                
                $message = showAlert('Configurações salvas com sucesso!', 'success');
            } catch (Exception $e) {
                $message = showAlert('Erro ao salvar configurações: ' . $e->getMessage(), 'danger');
            }
        }
    }
    
    if (isset($_POST['testar_conexao'])) {
        try {
            $pixup = new PixUpAPI($conn);
            $teste = $pixup->testarConexao();
            
            if ($teste['sucesso']) {
                $message = showAlert($teste['mensagem'], 'success');
            } else {
                $message = showAlert($teste['mensagem'], 'danger');
            }
        } catch (Exception $e) {
            $message = showAlert('Erro no teste: ' . $e->getMessage(), 'danger');
        }
    }
}

// Buscar configuração atual
$stmt = $conn->prepare("SELECT * FROM configuracoes_pixup WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$config_atual = $stmt->fetch();

// Buscar estatísticas de transações
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_transacoes,
        SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as pagas,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'PAID' THEN amount ELSE 0 END) as valor_total
    FROM transacoes_pix
");
$stmt->execute();
$stats = $stmt->fetch();

$page_title = 'Configurações PixUp';
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
        <h1><i class="fas fa-qrcode"></i> Configurações PixUp</h1>
    </div>
    
    <?php if ($message): ?>
        <?php echo $message; ?>
    <?php endif; ?>
    
    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Transações
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_transacoes'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-qrcode fa-2x text-gray-300"></i>
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
                                Pagamentos Confirmados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['pagas'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['pendentes'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Valor Total Recebido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatMoney($stats['valor_total'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Formulário de Configuração -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cog"></i> Configurações da API PixUp
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="client_id" class="form-label">Client ID *</label>
                                <input type="text" class="form-control" id="client_id" name="client_id" 
                                       value="<?php echo htmlspecialchars($config_atual['client_id'] ?? ''); ?>" required>
                                <div class="form-text">Obtido no painel da PixUp</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="client_secret" class="form-label">Client Secret *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="client_secret" name="client_secret" 
                                           value="<?php echo htmlspecialchars($config_atual['client_secret'] ?? ''); ?>" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mantenha em segurança</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="ambiente" class="form-label">Ambiente</label>
                                <select class="form-select" id="ambiente" name="ambiente">
                                    <option value="sandbox" <?php echo ($config_atual['ambiente'] ?? '') === 'sandbox' ? 'selected' : ''; ?>>
                                        Sandbox (Testes)
                                    </option>
                                    <option value="producao" <?php echo ($config_atual['ambiente'] ?? '') === 'producao' ? 'selected' : ''; ?>>
                                        Produção
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Como obter as credenciais:</h6>
                            <ol class="mb-0">
                                <li>Acesse o painel da PixUp</li>
                                <li>Vá em "Configurações" → "API"</li>
                                <li>Copie o Client ID e Client Secret</li>
                                <li>Configure o webhook: <code><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/pix/postback.php</code></li>
                            </ol>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="salvar_config" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                            
                            <?php if ($config_atual): ?>
                            <button type="submit" name="testar_conexao" class="btn btn-outline-success">
                                <i class="fas fa-plug"></i> Testar Conexão
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Status da Configuração -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Status da Integração
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($config_atual): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <strong>Configurado</strong><br>
                            Ambiente: <?php echo ucfirst($config_atual['ambiente']); ?><br>
                            Configurado em: <?php echo date('d/m/Y H:i', strtotime($config_atual['created_at'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Não Configurado</strong><br>
                            Configure as credenciais para ativar os pagamentos Pix.
                        </div>
                    <?php endif; ?>
                    
                    <h6>Recursos Disponíveis:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Geração de QR Code Pix</li>
                        <li><i class="fas fa-check text-success"></i> Código "Copia e Cola"</li>
                        <li><i class="fas fa-check text-success"></i> Webhook automático</li>
                        <li><i class="fas fa-check text-success"></i> Verificação de status</li>
                        <li><i class="fas fa-check text-success"></i> Liberação automática</li>
                    </ul>
                </div>
            </div>
            
            <!-- Últimas Transações -->
            <?php
            $stmt = $conn->prepare("
                SELECT t.*, u.name as customer_name 
                FROM transacoes_pix t
                JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC 
                LIMIT 5
            ");
            $stmt->execute();
            $ultimas_transacoes = $stmt->fetchAll();
            ?>
            
            <?php if (!empty($ultimas_transacoes)): ?>
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Últimas Transações
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($ultimas_transacoes as $transacao): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <div>
                            <small class="text-muted">#<?php echo $transacao['pedido_id']; ?></small><br>
                            <strong><?php echo htmlspecialchars($transacao['customer_name']); ?></strong><br>
                            <small><?php echo formatMoney($transacao['amount']); ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-<?php 
                                echo $transacao['status'] === 'PAID' ? 'success' : 
                                    ($transacao['status'] === 'PENDING' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo $transacao['status']; ?>
                            </span><br>
                            <small class="text-muted">
                                <?php echo date('d/m H:i', strtotime($transacao['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="text-center mt-3">
                        <a href="pix_transactions.php" class="btn btn-sm btn-outline-primary">
                            Ver Todas
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('client_secret');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>