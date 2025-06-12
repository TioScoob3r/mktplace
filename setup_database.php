<?php
/**
 * Script de Configuração Completa do Banco de Dados
 * Execute este arquivo uma vez para criar toda a estrutura
 */

require_once 'config/db.php';

try {
    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Setup do Banco de Dados - Marketplace Digital</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
        <style>
            .setup-container { max-width: 800px; margin: 0 auto; padding: 2rem; }
            .step { margin-bottom: 1rem; padding: 1rem; border-left: 4px solid #007bff; background: #f8f9fa; }
            .step.success { border-color: #28a745; }
            .step.error { border-color: #dc3545; }
            .log { background: #000; color: #00ff00; padding: 1rem; border-radius: 0.375rem; font-family: monospace; max-height: 400px; overflow-y: auto; }
        </style>
    </head>
    <body class='bg-light'>
        <div class='setup-container'>
            <div class='text-center mb-4'>
                <h1><i class='fas fa-database'></i> Setup do Banco de Dados</h1>
                <p class='lead'>Configuração completa do Marketplace Digital</p>
            </div>
            
            <div class='log mb-4'>";
    
    // Função para log
    function logMessage($message, $type = 'info') {
        $timestamp = date('H:i:s');
        $icon = $type === 'success' ? '✓' : ($type === 'error' ? '✗' : 'ℹ');
        echo "[$timestamp] $icon $message<br>";
        flush();
    }
    
    logMessage("Iniciando configuração do banco de dados...");
    
    // Conectar ao banco
    $db = DatabaseConnection::getInstance();
    $conn = $db->getConnection();
    
    logMessage("Conexão com banco estabelecida com sucesso", 'success');
    
    // Ler e executar o arquivo SQL
    $sqlFile = __DIR__ . '/sql/setup_marketplace.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    logMessage("Lendo arquivo SQL: setup_marketplace.sql");
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir em comandos individuais
    $commands = array_filter(
        array_map('trim', explode(';', $sql)),
        function($cmd) {
            return !empty($cmd) && 
                   !preg_match('/^(\/\*|--|#)/', $cmd) && 
                   !preg_match('/^(START TRANSACTION|COMMIT|SET|DELIMITER)/', $cmd);
        }
    );
    
    logMessage("Encontrados " . count($commands) . " comandos SQL para executar");
    
    $executed = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        try {
            if (trim($command)) {
                $conn->exec($command);
                $executed++;
                
                // Log específico para algumas operações
                if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $command, $matches)) {
                    logMessage("Tabela '{$matches[1]}' criada com sucesso", 'success');
                } elseif (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $command, $matches)) {
                    logMessage("Dados inseridos na tabela '{$matches[1]}'", 'success');
                }
            }
        } catch (PDOException $e) {
            $errors++;
            logMessage("Erro ao executar comando: " . $e->getMessage(), 'error');
        }
    }
    
    logMessage("Comandos executados: $executed");
    logMessage("Erros encontrados: $errors");
    
    // Verificar se as tabelas foram criadas
    $tables = [
        'usuarios', 'categorias', 'produtos', 'pedidos', 'itens_pedido',
        'transacoes_pix', 'downloads', 'configuracoes_pixup', 'tokens_pixup',
        'admin_config', 'avaliacoes', 'cupons_desconto', 'logs_sistema'
    ];
    
    logMessage("Verificando tabelas criadas...");
    
    $tablesCreated = 0;
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $tablesCreated++;
                logMessage("Tabela '$table' verificada", 'success');
            } else {
                logMessage("Tabela '$table' não encontrada", 'error');
            }
        } catch (PDOException $e) {
            logMessage("Erro ao verificar tabela '$table': " . $e->getMessage(), 'error');
        }
    }
    
    logMessage("Tabelas criadas: $tablesCreated/" . count($tables));
    
    // Verificar usuário admin
    $adminUser = fetchOne("SELECT * FROM usuarios WHERE email = 'admin@marketplace.com'");
    if ($adminUser) {
        logMessage("Usuário administrador criado com sucesso", 'success');
    } else {
        logMessage("Erro: Usuário administrador não foi criado", 'error');
    }
    
    // Verificar configurações
    $configCount = countRecords('admin_config');
    logMessage("Configurações administrativas: $configCount registros");
    
    // Verificar categorias
    $categoryCount = countRecords('categorias');
    logMessage("Categorias iniciais: $categoryCount registros");
    
    logMessage("Setup concluído!");
    
    echo "</div>";
    
    if ($errors === 0 && $tablesCreated === count($tables)) {
        echo "<div class='alert alert-success'>
                <h4><i class='fas fa-check-circle'></i> Setup Concluído com Sucesso!</h4>
                <p>O banco de dados foi configurado corretamente.</p>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>
                <h4><i class='fas fa-exclamation-triangle'></i> Setup Concluído com Avisos</h4>
                <p>Alguns erros foram encontrados. Verifique os logs acima.</p>
              </div>";
    }
    
    echo "<div class='card'>
            <div class='card-header'>
                <h5><i class='fas fa-user-shield'></i> Credenciais do Administrador</h5>
            </div>
            <div class='card-body'>
                <p><strong>Email:</strong> admin@marketplace.com</p>
                <p><strong>Senha:</strong> admin123</p>
                <div class='alert alert-warning'>
                    <small><i class='fas fa-exclamation-triangle'></i> 
                    Altere essas credenciais após o primeiro login!</small>
                </div>
            </div>
          </div>
          
          <div class='text-center mt-4'>
            <a href='index.php' class='btn btn-primary btn-lg me-2'>
                <i class='fas fa-home'></i> Ir para Home
            </a>
            <a href='auth/login.php' class='btn btn-outline-primary btn-lg'>
                <i class='fas fa-sign-in-alt'></i> Fazer Login
            </a>
          </div>
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='fas fa-times-circle'></i> Erro na Configuração</h4>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
    
    logMessage("Erro fatal: " . $e->getMessage(), 'error');
}
?>