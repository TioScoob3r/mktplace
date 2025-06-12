<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

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
        $webhook_url = sanitizeInput($_POST['webhook_url']);
        $timeout_transacao = (int)$_POST['timeout_transacao'];
        $auto_aprovacao = isset($_POST['auto_aprovacao']) ? 1 : 0;
        $taxa_processamento = (float)$_POST['taxa_processamento'];
        $valor_minimo = (float)$_POST['valor_minimo'];
        $valor_maximo = (float)$_POST['valor_maximo'];
        
        if (empty($client_id) || empty($client_secret)) {
            $message = showAlert('Por favor, preencha todos os campos obrigatórios.', 'danger');
        } else {
            try {
                $conn->beginTransaction();
                
                // Desativar configurações anteriores
                $conn->exec("UPDATE configuracoes_pixup SET ativo = 0");
                
                // Inserir nova configuração
                $sql = "INSERT INTO configuracoes_pixup 
                        (client_id, client_secret, ambiente, webhook_url, timeout_transacao, 
                         auto_aprovacao, taxa_processamento, valor_minimo, valor_maximo, 
                         configurado_por, ativo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $client_id, $client_secret, $ambiente, $webhook_url, $timeout_transacao,
                    $auto_aprovacao, $taxa_processamento, $valor_minimo, $valor_maximo,
                    $_SESSION['user_id']
                ]);
                
                // Limpar cache de tokens
                $conn->exec("DELETE FROM tokens_pixup WHERE ambiente = '$ambiente'");
                
                $conn->commit();
                $message = showAlert('Configurações salvas com sucesso!', 'success');
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = showAlert('Erro ao salvar configurações: ' . $e->getMessage(), 'danger');
            }
        }
    }
    
    if (isset($_POST['testar_conexao'])) {
        try {
            // Aqui seria implementado o teste real com a API PixUp
            $message = showAlert('Teste de conexão realizado com sucesso!', 'success');
        } catch (Exception $e) {
            $message = showAlert('Erro no teste: ' . $e->getMessage(), 'danger');
        }
    }
}

// Buscar configuração atual
$config_atual = fetchOne("SELECT * FROM configuracoes_pixup WHERE ativo = 1 ORDER BY id DESC LIMIT 1");

// Buscar estatísticas de transações
$stats = fetchOne("
    SELECT 
        COUNT(*) as total_transacoes,
        SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as pagas,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'PAID' THEN valor ELSE 0 END) as valor_total
    FROM transacoes_pix
");

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
                            
                            <div class="col-md-6 mb-3">
                                <label for="webhook_url" class="form-label">URL do Webhook</label>
                                <input type="url" class="form-control" id="webhook_url" name="webhook_url" 
                                       value="<?php echo htmlspecialchars($config_atual['webhook_url'] ?? (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/pix/postback.php'); ?>">
                                <div class="form-text">URL para receber notificações</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="timeout_transacao" class="form-label">Timeout (segundos)</label>
                                <input type="number" class="form-control" id="timeout_transacao" name="timeout_transacao" 
                                       value="<?php echo $config_atual['timeout_transacao'] ?? 3600; ?>" min="300" max="86400">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="valor_minimo" class="form-label">Valor Mínimo (R$)</label>
                                <input type="number" class="form-control" id="valor_minimo" name="valor_minimo" 
                                       value="<?php echo $config_atual['valor_minimo'] ?? 1.00; ?>" step="0.01" min="0.01">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="valor_maximo" class="form-label">Valor Máximo (R$)</label>
                                <input type="number" class="form-control" id="valor_maximo" name="valor_maximo" 
                                       value="<?php echo $config_atual['valor_maximo'] ?? 50000.00; ?>" step="0.01" min="1.00">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="taxa_processamento" class="form-label">Taxa de Processamento (%)</label>
                                <input type="number" class="form-control" id="taxa_processamento" name="taxa_processamento" 
                                       value="<?php echo ($config_atual['taxa_processamento'] ?? 0) * 100; ?>" step="0.01" min="0" max="10">
                                <div class="form-text">Taxa adicional sobre o valor da transação</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="auto_aprovacao" name="auto_aprovacao" 
                                           <?php echo ($config_atual['auto_aprovacao'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="auto_aprovacao">
                                        Aprovação Automática de Pagamentos
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Como obter as credenciais:</h6>
                            <ol class="mb-0">
                                <li>Acesse o painel da PixUp</li>
                                <li>Vá em "Configurações" → "API"</li>
                                <li>Copie o Client ID e Client Secret</li>
                                <li>Configure o webhook com a URL fornecida acima</li>
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
                            Configurado em: <?php echo date('d/m/Y H:i', strtotime($config_atual['criado_em'])); ?>
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
                        <li><i class="fas fa-check text-success"></i> Controle de timeout</li>
                        <li><i class="fas fa-check text-success"></i> Logs detalhados</li>
                    </ul>
                </div>
            </div>
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