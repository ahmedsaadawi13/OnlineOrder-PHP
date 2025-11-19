-- ============================================
-- Migration: Create restaurants table
-- Version: 001
-- ============================================

CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `phone` VARCHAR(20),
  `logo_url` VARCHAR(500),
  `currency` VARCHAR(3) DEFAULT 'USD',
  `timezone` VARCHAR(50) DEFAULT 'UTC',
  `status` ENUM('pending', 'active', 'suspended', 'cancelled') DEFAULT 'pending',
  `subscription_id` BIGINT UNSIGNED,
  `metadata` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
