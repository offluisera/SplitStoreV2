-- ============================================
-- SCHEMA SQL - BANCO DE DADOS POR LOJA
-- ============================================
-- Exemplo: Para loja "redsky" será criado banco "redsky"
-- Para loja "loja-teste" será criado banco "loja_teste"

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- TABELA: categories
-- ============================================
CREATE TABLE `categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `icone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordem` int DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: products
-- ============================================
CREATE TABLE `products` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int UNSIGNED NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco` decimal(10,2) NOT NULL,
  `preco_promocional` decimal(10,2) DEFAULT NULL,
  `imagem_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comando` text COLLATE utf8mb4_unicode_ci,
  `estoque` int DEFAULT -1 COMMENT '-1 = ilimitado',
  `vendas_total` int DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `destaque` tinyint(1) DEFAULT 0,
  `ordem` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_vendas` (`vendas_total`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: customers
-- ============================================
CREATE TABLE `customers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minecraft_username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minecraft_uuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_gasto` decimal(15,2) DEFAULT 0.00,
  `total_pedidos` int DEFAULT 0,
  `primeiro_pedido` timestamp NULL DEFAULT NULL,
  `ultimo_pedido` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_cpf` (`cpf`),
  KEY `idx_minecraft_username` (`minecraft_username`),
  KEY `idx_total_gasto` (`total_gasto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: orders
-- ============================================
CREATE TABLE `orders` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` int UNSIGNED NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','cancelled','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `final_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('pix','credit_card','debit_card','boleto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway` enum('misticpay','mercadopago') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minecraft_username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: order_items
-- ============================================
CREATE TABLE `order_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `comando` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: transactions
-- ============================================
CREATE TABLE `transactions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED NOT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('pix','credit_card','debit_card','boleto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway` enum('misticpay','mercadopago') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','processing','cancelled','refunded','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transactions_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: orders_pending
-- ============================================
CREATE TABLE `orders_pending` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `minecraft_username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('pix','credit_card','debit_card','boleto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cart_data` json NOT NULL COMMENT 'Dados do carrinho em JSON',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: orders_success
-- ============================================
CREATE TABLE `orders_success` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int UNSIGNED NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comandos_executados` json DEFAULT NULL COMMENT 'Log dos comandos executados',
  `entrega_status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `entrega_tentativas` int DEFAULT 0,
  `entrega_erro` text COLLATE utf8mb4_unicode_ci,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_entrega_status` (`entrega_status`),
  CONSTRAINT `fk_orders_success_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: news
-- ============================================
CREATE TABLE `news` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conteudo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagem_destaque` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `autor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Admin',
  `visualizacoes` int DEFAULT 0,
  `destaque` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: comments_news
-- ============================================
CREATE TABLE `comments_news` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `news_id` int UNSIGNED NOT NULL,
  `customer_id` int UNSIGNED DEFAULT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprovado` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_news` (`news_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_aprovado` (`aprovado`),
  CONSTRAINT `fk_comments_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABELA: store_settings
-- ============================================
CREATE TABLE `store_settings` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` enum('text','number','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DADOS INICIAIS
-- ============================================

-- Categoria padrão
INSERT INTO `categories` (`nome`, `slug`, `descricao`, `icone`, `ordem`, `ativo`) VALUES
('VIP', 'vip', 'Ranks VIP do servidor', '👑', 1, 1),
('Kits', 'kits', 'Kits de itens especiais', '📦', 2, 1),
('Cash', 'cash', 'Moeda premium do servidor', '💎', 3, 1);

-- Configurações iniciais da loja
INSERT INTO `store_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('store_name', 'Minha Loja', 'text'),
('store_description', 'Loja oficial do servidor', 'text'),
('primary_color', '#ef4444', 'text'),
('maintenance_mode', '0', 'boolean'),
('discord_webhook', NULL, 'text'),
('minecraft_server_ip', NULL, 'text'),
('minecraft_rcon_enabled', '0', 'boolean');

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Atualizar total_gasto do cliente
DELIMITER $$
CREATE TRIGGER `update_customer_stats_after_order` 
AFTER UPDATE ON `orders` 
FOR EACH ROW 
BEGIN
  IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
    UPDATE customers 
    SET total_gasto = total_gasto + NEW.final_amount,
        total_pedidos = total_pedidos + 1,
        ultimo_pedido = NEW.updated_at
    WHERE id = NEW.customer_id;
    
    -- Atualizar vendas do produto
    UPDATE products p
    INNER JOIN order_items oi ON p.id = oi.product_id
    SET p.vendas_total = p.vendas_total + oi.quantity
    WHERE oi.order_id = NEW.id;
  END IF;
END$$
DELIMITER ;

-- Trigger: Limpar pedidos pendentes expirados
DELIMITER $$
CREATE EVENT `cleanup_expired_pending_orders`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
  DELETE FROM orders_pending WHERE expires_at < NOW();
END$$
DELIMITER ;

-- ============================================
-- VIEWS ÚTEIS
-- ============================================

-- View: Produtos mais vendidos
CREATE VIEW `view_top_products` AS
SELECT 
  p.id,
  p.nome,
  p.preco,
  p.vendas_total,
  c.nome as categoria,
  SUM(oi.total_price) as revenue_total
FROM products p
INNER JOIN categories c ON p.category_id = c.id
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
GROUP BY p.id
ORDER BY p.vendas_total DESC;

-- View: Clientes VIP (top gastadores)
CREATE VIEW `view_top_customers` AS
SELECT 
  id,
  nome,
  email,
  minecraft_username,
  total_gasto,
  total_pedidos,
  primeiro_pedido,
  ultimo_pedido
FROM customers
WHERE total_gasto > 0
ORDER BY total_gasto DESC
LIMIT 50;

-- View: Estatísticas de vendas por método de pagamento
CREATE VIEW `view_payment_stats` AS
SELECT 
  payment_method,
  COUNT(*) as total_transactions,
  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
  SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as revenue
FROM orders
GROUP BY payment_method;

COMMIT;

-- ============================================
-- FIM DO SCHEMA
-- ============================================