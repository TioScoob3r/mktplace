-- =====================================================
-- MARKETPLACE DIGITAL - ESTRUTURA COMPLETA DO BANCO
-- =====================================================
-- Versão: 2.0
-- Data: 2024
-- Charset: utf8mb4
-- Engine: InnoDB
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Configurações de charset
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- CRIAÇÃO DO BANCO DE DADOS
-- =====================================================

CREATE DATABASE IF NOT EXISTS `marketplace_digital` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `marketplace_digital`;

-- =====================================================
-- TABELA: usuarios
-- =====================================================

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `tipo` enum('cliente','admin','super_admin') DEFAULT 'cliente',
  `status` enum('ativo','inativo','suspenso') DEFAULT 'ativo',
  `avatar` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `email_verificado` tinyint(1) DEFAULT 0,
  `token_verificacao` varchar(100) DEFAULT NULL,
  `token_reset_senha` varchar(100) DEFAULT NULL,
  `reset_senha_expira` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_status` (`status`),
  KEY `idx_email_verificado` (`email_verificado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: categorias
-- =====================================================

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `cor` varchar(7) DEFAULT '#007bff',
  `categoria_pai_id` int(11) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categoria_pai_id` (`categoria_pai_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ordem` (`ordem`),
  CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`categoria_pai_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: produtos
-- =====================================================

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `descricao_curta` varchar(500) DEFAULT NULL,
  `descricao_completa` longtext DEFAULT NULL,
  `preco` decimal(10,2) NOT NULL,
  `preco_promocional` decimal(10,2) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `imagem_principal` varchar(255) DEFAULT NULL,
  `galeria_imagens` json DEFAULT NULL,
  `arquivo_produto` varchar(255) DEFAULT NULL,
  `arquivo_preview` varchar(255) DEFAULT NULL,
  `tamanho_arquivo` bigint(20) DEFAULT NULL,
  `formato_arquivo` varchar(50) DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `requisitos_sistema` json DEFAULT NULL,
  `status` enum('ativo','inativo','rascunho') DEFAULT 'ativo',
  `destaque` tinyint(1) DEFAULT 0,
  `downloads_count` int(11) DEFAULT 0,
  `vendas_count` int(11) DEFAULT 0,
  `avaliacao_media` decimal(3,2) DEFAULT 0.00,
  `total_avaliacoes` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `seo_title` varchar(200) DEFAULT NULL,
  `seo_description` varchar(300) DEFAULT NULL,
  `seo_keywords` varchar(500) DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categoria_id` (`categoria_id`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_status` (`status`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_preco` (`preco`),
  KEY `idx_vendas` (`vendas_count`),
  KEY `idx_avaliacao` (`avaliacao_media`),
  FULLTEXT KEY `idx_busca` (`nome`,`descricao_curta`,`descricao_completa`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  CONSTRAINT `produtos_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: pedidos
-- =====================================================

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_pedido` varchar(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `status` enum('pendente','processando','pago','cancelado','reembolsado','expirado') DEFAULT 'pendente',
  `metodo_pagamento` enum('pix','cartao','boleto','transferencia') DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `taxa_processamento` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `moeda` varchar(3) DEFAULT 'BRL',
  `cupom_desconto` varchar(50) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `dados_pagamento` json DEFAULT NULL,
  `ip_cliente` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `pago_em` timestamp NULL DEFAULT NULL,
  `cancelado_em` timestamp NULL DEFAULT NULL,
  `motivo_cancelamento` text DEFAULT NULL,
  `expira_em` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pedido` (`numero_pedido`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_metodo_pagamento` (`metodo_pagamento`),
  KEY `idx_total` (`total`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: itens_pedido
-- =====================================================

DROP TABLE IF EXISTS `itens_pedido`;
CREATE TABLE `itens_pedido` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `nome_produto` varchar(200) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `quantidade` int(11) DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `dados_produto` json DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `itens_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `itens_pedido_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: transacoes_pix
-- =====================================================

DROP TABLE IF EXISTS `transacoes_pix`;
CREATE TABLE `transacoes_pix` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('PENDING','PAID','CANCELLED','EXPIRED','REFUNDED') DEFAULT 'PENDING',
  `pagador_nome` varchar(100) NOT NULL,
  `pagador_documento` varchar(14) NOT NULL,
  `pagador_email` varchar(100) NOT NULL,
  `qr_code_image` text DEFAULT NULL,
  `qr_code_text` text DEFAULT NULL,
  `pix_copia_cola` text DEFAULT NULL,
  `webhook_data` json DEFAULT NULL,
  `tentativas_webhook` int(11) DEFAULT 0,
  `ultimo_webhook` timestamp NULL DEFAULT NULL,
  `pago_em` timestamp NULL DEFAULT NULL,
  `expira_em` timestamp NULL DEFAULT NULL,
  `cancelado_em` timestamp NULL DEFAULT NULL,
  `motivo_cancelamento` text DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `taxa_processamento` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `external_id` (`external_id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_pagador_documento` (`pagador_documento`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `transacoes_pix_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transacoes_pix_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: downloads
-- =====================================================

DROP TABLE IF EXISTS `downloads`;
CREATE TABLE `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `token_download` varchar(100) NOT NULL,
  `downloads_realizados` int(11) DEFAULT 0,
  `limite_downloads` int(11) DEFAULT -1,
  `ip_ultimo_download` varchar(45) DEFAULT NULL,
  `user_agent_ultimo` text DEFAULT NULL,
  `ultimo_download` timestamp NULL DEFAULT NULL,
  `expira_em` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_download` (`token_download`),
  KEY `usuario_id` (`usuario_id`),
  KEY `produto_id` (`produto_id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_expira_em` (`expira_em`),
  CONSTRAINT `downloads_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `downloads_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `downloads_ibfk_3` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: configuracoes_pixup
-- =====================================================

DROP TABLE IF EXISTS `configuracoes_pixup`;
CREATE TABLE `configuracoes_pixup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(255) NOT NULL,
  `client_secret` varchar(255) NOT NULL,
  `ambiente` enum('sandbox','producao') DEFAULT 'sandbox',
  `webhook_url` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(100) DEFAULT NULL,
  `timeout_transacao` int(11) DEFAULT 3600,
  `auto_aprovacao` tinyint(1) DEFAULT 1,
  `taxa_processamento` decimal(5,4) DEFAULT 0.0000,
  `valor_minimo` decimal(10,2) DEFAULT 1.00,
  `valor_maximo` decimal(10,2) DEFAULT 50000.00,
  `ativo` tinyint(1) DEFAULT 1,
  `configurado_por` int(11) DEFAULT NULL,
  `testado_em` timestamp NULL DEFAULT NULL,
  `ultimo_teste_sucesso` tinyint(1) DEFAULT NULL,
  `logs_habilitados` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `configurado_por` (`configurado_por`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_ambiente` (`ambiente`),
  CONSTRAINT `configuracoes_pixup_ibfk_1` FOREIGN KEY (`configurado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: tokens_pixup
-- =====================================================

DROP TABLE IF EXISTS `tokens_pixup`;
CREATE TABLE `tokens_pixup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `access_token` text NOT NULL,
  `token_type` varchar(50) DEFAULT 'Bearer',
  `expires_in` int(11) NOT NULL,
  `scope` varchar(200) DEFAULT NULL,
  `ambiente` enum('sandbox','producao') DEFAULT 'sandbox',
  `expira_em` timestamp NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expira_em` (`expira_em`),
  KEY `idx_ambiente` (`ambiente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: admin_config
-- =====================================================

DROP TABLE IF EXISTS `admin_config`;
CREATE TABLE `admin_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` longtext DEFAULT NULL,
  `tipo` enum('string','integer','boolean','json','encrypted') DEFAULT 'string',
  `categoria` varchar(50) DEFAULT 'geral',
  `descricao` text DEFAULT NULL,
  `editavel` tinyint(1) DEFAULT 1,
  `publico` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_publico` (`publico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: avaliacoes
-- =====================================================

DROP TABLE IF EXISTS `avaliacoes`;
CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `nota` tinyint(1) NOT NULL CHECK (`nota` >= 1 and `nota` <= 5),
  `titulo` varchar(200) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `aprovado` tinyint(1) DEFAULT 0,
  `aprovado_por` int(11) DEFAULT NULL,
  `aprovado_em` timestamp NULL DEFAULT NULL,
  `util_sim` int(11) DEFAULT 0,
  `util_nao` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_avaliacao` (`produto_id`,`usuario_id`,`pedido_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `aprovado_por` (`aprovado_por`),
  KEY `idx_aprovado` (`aprovado`),
  KEY `idx_nota` (`nota`),
  CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacoes_ibfk_3` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `avaliacoes_ibfk_4` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: cupons_desconto
-- =====================================================

DROP TABLE IF EXISTS `cupons_desconto`;
CREATE TABLE `cupons_desconto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `tipo` enum('percentual','valor_fixo') DEFAULT 'percentual',
  `valor` decimal(10,2) NOT NULL,
  `valor_minimo_pedido` decimal(10,2) DEFAULT NULL,
  `limite_uso` int(11) DEFAULT NULL,
  `usado_count` int(11) DEFAULT 0,
  `limite_por_usuario` int(11) DEFAULT 1,
  `produtos_aplicaveis` json DEFAULT NULL,
  `categorias_aplicaveis` json DEFAULT NULL,
  `usuarios_aplicaveis` json DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `valido_de` timestamp NULL DEFAULT NULL,
  `valido_ate` timestamp NULL DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `criado_por` (`criado_por`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_validade` (`valido_de`,`valido_ate`),
  CONSTRAINT `cupons_desconto_ibfk_1` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: logs_sistema
-- =====================================================

DROP TABLE IF EXISTS `logs_sistema`;
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nivel` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') DEFAULT 'INFO',
  `categoria` varchar(50) NOT NULL,
  `mensagem` text NOT NULL,
  `contexto` json DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `metodo_http` varchar(10) DEFAULT NULL,
  `arquivo` varchar(255) DEFAULT NULL,
  `linha` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_nivel` (`nivel`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERÇÃO DE DADOS INICIAIS
-- =====================================================

-- Usuário Super Admin
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `tipo`, `status`, `email_verificado`) VALUES
('Super Administrador', 'admin@marketplace.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'ativo', 1);

-- Categorias iniciais
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `icone`, `cor`, `ordem`) VALUES
('E-books', 'ebooks', 'Livros digitais e guias', 'fas fa-book', '#007bff', 1),
('Cursos Online', 'cursos', 'Cursos e treinamentos digitais', 'fas fa-graduation-cap', '#28a745', 2),
('Templates', 'templates', 'Templates e temas para websites', 'fas fa-palette', '#dc3545', 3),
('Plugins', 'plugins', 'Plugins e extensões', 'fas fa-plug', '#ffc107', 4),
('Software', 'software', 'Aplicativos e ferramentas', 'fas fa-desktop', '#6f42c1', 5);

-- Configurações administrativas iniciais
INSERT INTO `admin_config` (`chave`, `valor`, `tipo`, `categoria`, `descricao`, `publico`) VALUES
('site_nome', 'Marketplace Digital', 'string', 'geral', 'Nome do site', 1),
('site_descricao', 'A melhor plataforma para produtos digitais', 'string', 'geral', 'Descrição do site', 1),
('site_email', 'contato@marketplace.com', 'string', 'geral', 'Email de contato', 1),
('site_telefone', '(11) 99999-9999', 'string', 'geral', 'Telefone de contato', 1),
('moeda_padrao', 'BRL', 'string', 'financeiro', 'Moeda padrão do sistema', 0),
('taxa_processamento', '0.0399', 'string', 'financeiro', 'Taxa de processamento padrão', 0),
('limite_downloads', '10', 'integer', 'produtos', 'Limite padrão de downloads por produto', 0),
('manutencao_ativa', '0', 'boolean', 'sistema', 'Modo manutenção ativo', 0),
('registro_usuarios', '1', 'boolean', 'usuarios', 'Permitir registro de novos usuários', 0),
('email_verificacao', '1', 'boolean', 'usuarios', 'Exigir verificação de email', 0);

-- =====================================================
-- TRIGGERS E PROCEDURES
-- =====================================================

-- Trigger para atualizar contador de vendas do produto
DELIMITER $$
CREATE TRIGGER `atualizar_vendas_produto` AFTER UPDATE ON `pedidos`
FOR EACH ROW BEGIN
    IF NEW.status = 'pago' AND OLD.status != 'pago' THEN
        UPDATE produtos p 
        SET vendas_count = vendas_count + (
            SELECT SUM(quantidade) 
            FROM itens_pedido 
            WHERE pedido_id = NEW.id AND produto_id = p.id
        )
        WHERE p.id IN (
            SELECT produto_id 
            FROM itens_pedido 
            WHERE pedido_id = NEW.id
        );
    END IF;
END$$

-- Trigger para gerar número do pedido
DELIMITER $$
CREATE TRIGGER `gerar_numero_pedido` BEFORE INSERT ON `pedidos`
FOR EACH ROW BEGIN
    IF NEW.numero_pedido IS NULL OR NEW.numero_pedido = '' THEN
        SET NEW.numero_pedido = CONCAT('PED', YEAR(NOW()), LPAD(NEW.id, 6, '0'));
    END IF;
END$$

-- Trigger para gerar token de download
DELIMITER $$
CREATE TRIGGER `gerar_token_download` BEFORE INSERT ON `downloads`
FOR EACH ROW BEGIN
    IF NEW.token_download IS NULL OR NEW.token_download = '' THEN
        SET NEW.token_download = SHA2(CONCAT(NEW.usuario_id, NEW.produto_id, NOW(), RAND()), 256);
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

-- Índices compostos para consultas frequentes
ALTER TABLE `pedidos` ADD INDEX `idx_usuario_status` (`usuario_id`, `status`);
ALTER TABLE `produtos` ADD INDEX `idx_categoria_status` (`categoria_id`, `status`);
ALTER TABLE `transacoes_pix` ADD INDEX `idx_usuario_status` (`usuario_id`, `status`);
ALTER TABLE `downloads` ADD INDEX `idx_usuario_ativo` (`usuario_id`, `ativo`);

-- =====================================================
-- VIEWS ÚTEIS
-- =====================================================

-- View para relatório de vendas
CREATE OR REPLACE VIEW `view_relatorio_vendas` AS
SELECT 
    p.id as pedido_id,
    p.numero_pedido,
    u.nome as cliente_nome,
    u.email as cliente_email,
    p.total,
    p.status,
    p.metodo_pagamento,
    p.criado_em,
    COUNT(ip.id) as total_itens
FROM pedidos p
JOIN usuarios u ON p.usuario_id = u.id
LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
GROUP BY p.id;

-- View para produtos mais vendidos
CREATE OR REPLACE VIEW `view_produtos_mais_vendidos` AS
SELECT 
    p.id,
    p.nome,
    p.preco,
    p.vendas_count,
    p.downloads_count,
    p.avaliacao_media,
    c.nome as categoria_nome
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.status = 'ativo'
ORDER BY p.vendas_count DESC;

-- =====================================================
-- FINALIZAÇÃO
-- =====================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================