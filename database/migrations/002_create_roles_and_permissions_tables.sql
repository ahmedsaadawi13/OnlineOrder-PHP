-- ============================================
-- Migration: Create roles and permissions tables
-- Version: 002
-- ============================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) UNIQUE NOT NULL,
  `slug` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `is_system` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `module` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permission` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Platform administrator with full access', TRUE),
('Restaurant Owner', 'restaurant_owner', 'Restaurant owner with full tenant access', TRUE),
('Branch Manager', 'branch_manager', 'Manager of specific branch', TRUE),
('Staff Admin', 'staff_admin', 'Staff member with admin privileges', TRUE),
('Cashier', 'cashier', 'Cashier with limited order access', TRUE);

-- Insert default permissions
INSERT INTO `permissions` (`name`, `slug`, `module`) VALUES
('View Dashboard', 'dashboard.view', 'dashboard'),
('Manage Restaurants', 'restaurants.manage', 'restaurants'),
('Manage Branches', 'branches.manage', 'branches'),
('Manage Menu', 'menu.manage', 'menu'),
('View Orders', 'orders.view', 'orders'),
('Manage Orders', 'orders.manage', 'orders'),
('Manage Customers', 'customers.manage', 'customers'),
('Manage Coupons', 'coupons.manage', 'coupons'),
('View Reports', 'reports.view', 'reports'),
('Manage Settings', 'settings.manage', 'settings'),
('Manage Users', 'users.manage', 'users');

-- Assign all permissions to Super Admin
INSERT INTO `role_permission` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;
