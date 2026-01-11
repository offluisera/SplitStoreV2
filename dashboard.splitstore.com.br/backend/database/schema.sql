-- dashboard.splitstore.com.br/backend/database/schema.sql

-- Tabela de cupons
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_percent` DECIMAL(5,2) NOT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `max_uses` INT UNSIGNED NULL,
  `used_count` INT UNSIGNED DEFAULT 0,
  `valid_until` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lojas pendentes (aguardando pagamento)
CREATE TABLE IF NOT EXISTS `pending_stores` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `store_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `plan_id` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `discount` DECIMAL(10,2) DEFAULT 0,
  `coupon_code` VARCHAR(50) NULL,
  `payment_id` VARCHAR(255) NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lojas ativas
CREATE TABLE IF NOT EXISTS `stores` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `plano` VARCHAR(50) NOT NULL,
  `status` ENUM('active', 'suspended', 'cancelled') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de transações
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `store_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `produto_nome` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_store_id` (`store_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir cupons de exemplo
INSERT INTO `coupons` (`code`, `discount_percent`, `active`, `max_uses`, `valid_until`) VALUES
('PRIMEIRACOMPRA', 10.00, 1, 100, NULL),
('LANCAMENTO', 20.00, 1, 50, '2026-02-28 23:59:59'),
('VIP50', 50.00, 1, 10, NULL)
ON DUPLICATE KEY UPDATE `code` = `code`;