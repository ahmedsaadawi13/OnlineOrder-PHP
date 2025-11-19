# 🗄️ Database Schema & ERD

## **Restaurant Online Ordering SaaS Platform**

---

## **1. Database Overview**

### **1.1 Strategy**
- **Type:** Relational (MySQL 8.0+)
- **Multi-Tenancy:** Single database with tenant_id isolation
- **Character Set:** utf8mb4 (supports emojis, Arabic, multilingual)
- **Collation:** utf8mb4_unicode_ci
- **Engine:** InnoDB (ACID compliance, foreign keys)

### **1.2 Design Principles**
- ✅ Every tenant-specific table includes `tenant_id` (restaurant_id)
- ✅ Soft deletes using `deleted_at` timestamp
- ✅ Audit trails (`created_at`, `updated_at`)
- ✅ Indexed foreign keys for performance
- ✅ ENUM types for status fields
- ✅ JSON columns for flexible metadata

---

## **2. Entity Relationship Diagram (ERD)**

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          CORE ENTITIES                                    │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────────┐           ┌─────────────────┐           ┌─────────────┐
│  restaurants    │◄──────────┤     users       │──────────►│    roles    │
│  (tenants)      │ 1      N  │                 │ N      M  │             │
├─────────────────┤           ├─────────────────┤           ├─────────────┤
│ id (PK)         │           │ id (PK)         │           │ id (PK)     │
│ name            │           │ tenant_id (FK)  │           │ name        │
│ slug            │           │ email           │           │ description │
│ status          │           │ password        │           └─────────────┘
│ subscription_id │           │ role_id (FK)    │                 │
│ created_at      │           │ created_at      │                 │
└────────┬────────┘           └────────┬────────┘                 │
         │ 1                           │                          │
         │                             │                          │
         │ N                           │ 1                        │
┌────────▼────────┐           ┌────────▼────────┐       ┌────────▼────────┐
│    branches     │           │ refresh_tokens  │       │  permissions    │
├─────────────────┤           ├─────────────────┤       ├─────────────────┤
│ id (PK)         │           │ id (PK)         │       │ id (PK)         │
│ tenant_id (FK)  │           │ user_id (FK)    │       │ name            │
│ name            │           │ token           │       │ description     │
│ address         │           │ expires_at      │       └─────────────────┘
│ lat, lng        │           └─────────────────┘                │
│ is_active       │                                               │
└────────┬────────┘                                               │
         │ 1                                                      │
         │                                                        │
         │ N                                                      │
┌────────▼────────────┐                                 ┌─────────▼─────────┐
│   opening_hours     │                                 │  role_permission  │
├─────────────────────┤                                 ├───────────────────┤
│ id (PK)             │                                 │ role_id (FK)      │
│ branch_id (FK)      │                                 │ permission_id(FK) │
│ day_of_week         │                                 └───────────────────┘
│ open_time           │
│ close_time          │
└─────────────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│                          MENU SYSTEM                                      │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│  restaurants    │
│  (tenant)       │
└────────┬────────┘
         │ 1
         │
         │ N
┌────────▼────────┐           ┌──────────────────┐
│   categories    │           │   menu_items     │◄─────┐
├─────────────────┤ 1      N  ├──────────────────┤      │
│ id (PK)         │◄──────────┤ id (PK)          │      │
│ tenant_id (FK)  │           │ tenant_id (FK)   │      │
│ name            │           │ category_id (FK) │      │
│ slug            │           │ name             │      │
│ sort_order      │           │ description      │      │
│ is_active       │           │ price            │      │
└─────────────────┘           │ image_url        │      │
                              │ is_available     │      │
                              │ is_featured      │      │
                              └────────┬─────────┘      │
                                       │ 1              │
                                       │                │
                  ┌────────────────────┼────────────────┤
                  │ N                  │ N              │ N
         ┌────────▼────────┐  ┌────────▼────────┐  ┌───▼──────────┐
         │ item_modifiers  │  │  item_variants  │  │ item_images  │
         ├─────────────────┤  ├─────────────────┤  ├──────────────┤
         │ id (PK)         │  │ id (PK)         │  │ id (PK)      │
         │ item_id (FK)    │  │ item_id (FK)    │  │ item_id (FK) │
         │ name            │  │ name (size/etc) │  │ url          │
         │ type            │  │ price_modifier  │  │ sort_order   │
         │ is_required     │  │ is_default      │  └──────────────┘
         │ max_selections  │  └─────────────────┘
         └────────┬────────┘
                  │ 1
                  │
                  │ N
         ┌────────▼─────────────┐
         │  modifier_options    │
         ├──────────────────────┤
         │ id (PK)              │
         │ modifier_id (FK)     │
         │ name                 │
         │ price_modifier       │
         │ is_default           │
         └──────────────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│                          ORDER SYSTEM                                     │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│   customers     │
├─────────────────┤ 1
│ id (PK)         │───────┐
│ tenant_id (FK)  │       │
│ name            │       │
│ email           │       │
│ phone           │       │
│ password        │       │ N
│ created_at      │  ┌────▼────────────────┐
└─────────────────┘  │ customer_addresses  │
                     ├─────────────────────┤
                     │ id (PK)             │
                     │ customer_id (FK)    │
                     │ label (home/work)   │
                     │ address_line        │
                     │ lat, lng            │
                     │ is_default          │
                     └─────────────────────┘

┌─────────────────┐
│   customers     │
├─────────────────┤ 1
│ id (PK)         │───────┐
└─────────────────┘       │
                          │ N
                     ┌────▼────────┐           ┌──────────────────┐
                     │   orders    │ 1      N  │   order_items    │
                     ├─────────────┤◄──────────┤                  │
                     │ id (PK)     │           │ id (PK)          │
                     │ tenant_id   │           │ order_id (FK)    │
                     │ customer_id │           │ item_id (FK)     │
                     │ branch_id   │           │ item_name        │
                     │ order_number│           │ quantity         │
                     │ status      │           │ unit_price       │
                     │ payment_stat│           │ subtotal         │
                     │ subtotal    │           │ modifiers (JSON) │
                     │ tax         │           │ special_notes    │
                     │ delivery_fee│           └──────────────────┘
                     │ discount    │
                     │ total       │           ┌──────────────────┐
                     │ order_type  │ 1      N  │ order_status_    │
                     │ coupon_code │◄──────────┤    history       │
                     │ created_at  │           ├──────────────────┤
                     └─────────────┘           │ id (PK)          │
                            │ 1                │ order_id (FK)    │
                            │                  │ status           │
                            │ 1                │ notes            │
                     ┌──────▼──────┐           │ created_by       │
                     │  payments   │           │ created_at       │
                     ├─────────────┤           └──────────────────┘
                     │ id (PK)     │
                     │ order_id(FK)│
                     │ amount      │
                     │ method      │
                     │ status      │
                     │ gateway_id  │
                     │ created_at  │
                     └─────────────┘


┌──────────────────────────────────────────────────────────────────────────┐
│                       COUPONS & SUBSCRIPTIONS                             │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│    coupons      │ 1         N  ┌──────────────────┐
├─────────────────┤◄─────────────┤  coupon_usage    │
│ id (PK)         │              ├──────────────────┤
│ tenant_id (FK)  │              │ id (PK)          │
│ code            │              │ coupon_id (FK)   │
│ type            │              │ order_id (FK)    │
│ value           │              │ customer_id (FK) │
│ min_order_value │              │ used_at          │
│ max_uses        │              └──────────────────┘
│ max_uses_user   │
│ starts_at       │
│ expires_at      │
│ is_active       │
└─────────────────┘

┌───────────────────┐
│ subscription_plans│ 1         N  ┌──────────────────┐
├───────────────────┤◄─────────────┤  subscriptions   │
│ id (PK)           │              ├──────────────────┤
│ name              │              │ id (PK)          │
│ price_monthly     │              │ restaurant_id(FK)│
│ price_yearly      │              │ plan_id (FK)     │
│ max_branches      │              │ status           │
│ max_menu_items    │              │ starts_at        │
│ features (JSON)   │              │ ends_at          │
└───────────────────┘              │ created_at       │
                                   └──────────────────┘
```

---

## **3. Complete SQL Schema**

### **3.1 Authentication & Users**

```sql
-- ============================================
-- USERS & AUTHENTICATION
-- ============================================

CREATE TABLE `restaurants` (
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

CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) UNIQUE NOT NULL,
  `slug` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `is_system` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `module` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permission` (
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(500),
  `is_active` BOOLEAN DEFAULT TRUE,
  `email_verified_at` TIMESTAMP NULL,
  `last_login_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_email_tenant` (`email`, `tenant_id`),
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_email` (`email`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `refresh_tokens` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(500) UNIQUE NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `is_revoked` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RESTAURANT & BRANCHES
-- ============================================

CREATE TABLE `branches` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address_line1` VARCHAR(255),
  `address_line2` VARCHAR(255),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(100),
  `latitude` DECIMAL(10, 8),
  `longitude` DECIMAL(11, 8),
  `phone` VARCHAR(20),
  `email` VARCHAR(255),
  `is_active` BOOLEAN DEFAULT TRUE,
  `accepts_online_orders` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_location` (`latitude`, `longitude`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `opening_hours` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
  `open_time` TIME NOT NULL,
  `close_time` TIME NOT NULL,
  `is_closed` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_branch_day` (`branch_id`, `day_of_week`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_zones` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255),
  `polygon_coordinates` JSON COMMENT 'Array of lat/lng points defining the zone',
  `radius_km` DECIMAL(5, 2) COMMENT 'Alternative to polygon: radius from branch',
  `delivery_fee` DECIMAL(10, 2) NOT NULL,
  `min_order_value` DECIMAL(10, 2),
  `estimated_delivery_time` INT COMMENT 'In minutes',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_branch_id` (`branch_id`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `restaurant_settings` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL UNIQUE,
  `theme_color` VARCHAR(7) DEFAULT '#FF6B6B',
  `language` VARCHAR(5) DEFAULT 'en',
  `tax_rate` DECIMAL(5, 2) DEFAULT 0.00,
  `accepts_cash` BOOLEAN DEFAULT TRUE,
  `accepts_card` BOOLEAN DEFAULT TRUE,
  `stripe_enabled` BOOLEAN DEFAULT FALSE,
  `stripe_public_key` VARCHAR(255),
  `stripe_secret_key` VARCHAR(255),
  `paypal_enabled` BOOLEAN DEFAULT FALSE,
  `paypal_client_id` VARCHAR(255),
  `paypal_secret` VARCHAR(255),
  `email_notifications` BOOLEAN DEFAULT TRUE,
  `sms_notifications` BOOLEAN DEFAULT FALSE,
  `auto_accept_orders` BOOLEAN DEFAULT FALSE,
  `metadata` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.2 Menu System**

```sql
-- ============================================
-- MENU SYSTEM
-- ============================================

CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) COMMENT 'Arabic translation',
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `description_ar` TEXT,
  `image_url` VARCHAR(500),
  `sort_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_slug_tenant` (`slug`, `tenant_id`),
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_sort_order` (`sort_order`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `menu_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255),
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `description_ar` TEXT,
  `price` DECIMAL(10, 2) NOT NULL,
  `image_url` VARCHAR(500),
  `calories` INT,
  `preparation_time` INT COMMENT 'In minutes',
  `is_available` BOOLEAN DEFAULT TRUE,
  `is_featured` BOOLEAN DEFAULT FALSE,
  `is_vegetarian` BOOLEAN DEFAULT FALSE,
  `is_vegan` BOOLEAN DEFAULT FALSE,
  `is_gluten_free` BOOLEAN DEFAULT FALSE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_slug_tenant` (`slug`, `tenant_id`),
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_is_available` (`is_available`),
  INDEX `idx_is_featured` (`is_featured`),
  FULLTEXT INDEX `ft_search` (`name`, `description`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_images` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(255),
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_item_id` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_modifiers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'e.g., "Size", "Toppings"',
  `name_ar` VARCHAR(255),
  `type` ENUM('single', 'multiple') DEFAULT 'single',
  `is_required` BOOLEAN DEFAULT FALSE,
  `min_selections` INT DEFAULT 0,
  `max_selections` INT DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_item_id` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modifier_options` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `modifier_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'e.g., "Large", "Extra Cheese"',
  `name_ar` VARCHAR(255),
  `price_modifier` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Additional cost',
  `is_default` BOOLEAN DEFAULT FALSE,
  `is_available` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_modifier_id` (`modifier_id`),
  FOREIGN KEY (`modifier_id`) REFERENCES `item_modifiers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_variants` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'e.g., "Small", "Medium", "Large"',
  `name_ar` VARCHAR(255),
  `price_modifier` DECIMAL(10, 2) DEFAULT 0.00,
  `sku` VARCHAR(100),
  `is_default` BOOLEAN DEFAULT FALSE,
  `is_available` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_item_id` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `item_availability` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `is_available` BOOLEAN DEFAULT TRUE,
  `stock_quantity` INT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_item_branch` (`item_id`, `branch_id`),
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.3 Customers**

```sql
-- ============================================
-- CUSTOMERS
-- ============================================

CREATE TABLE `customers` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(500),
  `date_of_birth` DATE,
  `email_verified_at` TIMESTAMP NULL,
  `phone_verified_at` TIMESTAMP NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `preferred_language` VARCHAR(5) DEFAULT 'en',
  `last_login_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_email_tenant` (`email`, `tenant_id`),
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_phone` (`phone`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer_addresses` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `label` VARCHAR(50) COMMENT 'e.g., "Home", "Work", "Other"',
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255),
  `city` VARCHAR(100),
  `state` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(100),
  `latitude` DECIMAL(10, 8),
  `longitude` DECIMAL(11, 8),
  `delivery_instructions` TEXT,
  `is_default` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer_favorites` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_customer_item` (`customer_id`, `item_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.4 Orders**

```sql
-- ============================================
-- ORDERS
-- ============================================

CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `branch_id` BIGINT UNSIGNED NOT NULL,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
  `status` ENUM('pending', 'confirmed', 'preparing', 'ready_for_pickup', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
  `payment_status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `order_type` ENUM('delivery', 'pickup', 'dine_in') NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `delivery_fee` DECIMAL(10, 2) DEFAULT 0.00,
  `discount_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `coupon_code` VARCHAR(50),
  `delivery_address_id` BIGINT UNSIGNED,
  `scheduled_at` TIMESTAMP NULL COMMENT 'For scheduled orders',
  `estimated_delivery_time` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `special_instructions` TEXT,
  `cancellation_reason` TEXT,
  `cancelled_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_branch_id` (`branch_id`),
  INDEX `idx_order_number` (`order_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`delivery_address_id`) REFERENCES `customer_addresses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `item_name` VARCHAR(255) NOT NULL COMMENT 'Snapshot at order time',
  `item_name_ar` VARCHAR(255),
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  `selected_variant` JSON COMMENT 'Variant chosen (if any)',
  `selected_modifiers` JSON COMMENT 'Modifiers & options chosen',
  `special_instructions` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_item_id` (`item_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_status_history` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT,
  `created_by` BIGINT UNSIGNED COMMENT 'User ID who made the change',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_notes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `note` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.5 Payments**

```sql
-- ============================================
-- PAYMENTS
-- ============================================

CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `payment_method` ENUM('cash', 'stripe', 'paypal') NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `gateway_transaction_id` VARCHAR(255) COMMENT 'Stripe/PayPal transaction ID',
  `gateway_response` JSON COMMENT 'Full response from payment gateway',
  `payment_intent_id` VARCHAR(255) COMMENT 'Stripe Payment Intent ID',
  `refund_amount` DECIMAL(10, 2),
  `refund_reason` TEXT,
  `refunded_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_gateway_transaction_id` (`gateway_transaction_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_webhooks` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_id` BIGINT UNSIGNED,
  `gateway` ENUM('stripe', 'paypal') NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` JSON NOT NULL,
  `processed` BOOLEAN DEFAULT FALSE,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_processed` (`processed`),
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.6 Coupons & Discounts**

```sql
-- ============================================
-- COUPONS & DISCOUNTS
-- ============================================

CREATE TABLE `coupons` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `description_ar` TEXT,
  `type` ENUM('fixed', 'percentage') NOT NULL,
  `value` DECIMAL(10, 2) NOT NULL,
  `min_order_value` DECIMAL(10, 2),
  `max_discount_amount` DECIMAL(10, 2) COMMENT 'For percentage coupons',
  `max_uses` INT COMMENT 'Total usage limit',
  `max_uses_per_user` INT DEFAULT 1,
  `applicable_to` ENUM('all', 'categories', 'items') DEFAULT 'all',
  `applicable_ids` JSON COMMENT 'Category or item IDs',
  `starts_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_code_tenant` (`code`, `tenant_id`),
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_code` (`code`),
  INDEX `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupon_usage` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `coupon_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `discount_amount` DECIMAL(10, 2) NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_coupon_id` (`coupon_id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.7 Notifications**

```sql
-- ============================================
-- NOTIFICATIONS
-- ============================================

CREATE TABLE `notification_templates` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('email', 'sms', 'push') NOT NULL,
  `event` VARCHAR(100) NOT NULL COMMENT 'e.g., order.created, order.confirmed',
  `subject` VARCHAR(255),
  `body` TEXT NOT NULL,
  `body_ar` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED COMMENT 'If for user',
  `customer_id` BIGINT UNSIGNED COMMENT 'If for customer',
  `type` ENUM('email', 'sms', 'push', 'system') NOT NULL,
  `title` VARCHAR(255),
  `message` TEXT NOT NULL,
  `data` JSON COMMENT 'Additional metadata',
  `is_read` BOOLEAN DEFAULT FALSE,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_customer_id` (`customer_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('email', 'sms', 'push') NOT NULL,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255),
  `body` TEXT,
  `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `error_message` TEXT,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.8 Super Admin & Billing**

```sql
-- ============================================
-- SUPER ADMIN & SAAS MANAGEMENT
-- ============================================

CREATE TABLE `subscription_plans` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `price_monthly` DECIMAL(10, 2),
  `price_yearly` DECIMAL(10, 2),
  `trial_days` INT DEFAULT 14,
  `max_branches` INT,
  `max_menu_items` INT,
  `max_orders_per_month` INT,
  `features` JSON COMMENT 'List of features',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscriptions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` BIGINT UNSIGNED NOT NULL,
  `plan_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('trial', 'active', 'cancelled', 'expired', 'past_due') DEFAULT 'trial',
  `billing_cycle` ENUM('monthly', 'yearly') DEFAULT 'monthly',
  `trial_ends_at` TIMESTAMP NULL,
  `current_period_start` TIMESTAMP,
  `current_period_end` TIMESTAMP,
  `cancelled_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_restaurant_id` (`restaurant_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` BIGINT UNSIGNED NOT NULL,
  `subscription_id` BIGINT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(50) UNIQUE NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `tax_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('draft', 'open', 'paid', 'void', 'uncollectible') DEFAULT 'open',
  `due_date` DATE,
  `paid_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_restaurant_id` (`restaurant_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `support_tickets` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ticket_number` VARCHAR(50) UNIQUE NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `assigned_to` BIGINT UNSIGNED COMMENT 'Support staff user ID',
  `resolved_at` TIMESTAMP NULL,
  `closed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_restaurant_id` (`restaurant_id`),
  INDEX `idx_status` (`status`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### **3.9 Audit & Logging**

```sql
-- ============================================
-- AUDIT & LOGGING
-- ============================================

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED,
  `action` VARCHAR(100) NOT NULL COMMENT 'create, update, delete',
  `entity_type` VARCHAR(100) NOT NULL COMMENT 'order, menu_item, etc.',
  `entity_id` BIGINT UNSIGNED NOT NULL,
  `old_values` JSON,
  `new_values` JSON,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(500),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_entity` (`entity_type`, `entity_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED,
  `log_name` VARCHAR(100),
  `description` TEXT NOT NULL,
  `subject_type` VARCHAR(100),
  `subject_id` BIGINT UNSIGNED,
  `properties` JSON,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tenant_id` (`tenant_id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`tenant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## **4. Sample Data (Seeds)**

```sql
-- ============================================
-- SEED DATA
-- ============================================

-- Insert Roles
INSERT INTO `roles` (`name`, `slug`, `description`, `is_system`) VALUES
('Super Admin', 'super_admin', 'Platform administrator with full access', TRUE),
('Restaurant Owner', 'restaurant_owner', 'Restaurant owner with full tenant access', TRUE),
('Branch Manager', 'branch_manager', 'Manager of specific branch', TRUE),
('Staff Admin', 'staff_admin', 'Staff member with admin privileges', TRUE),
('Cashier', 'cashier', 'Cashier with limited order access', TRUE);

-- Insert Permissions
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

-- Insert Subscription Plans
INSERT INTO `subscription_plans` (`name`, `slug`, `price_monthly`, `price_yearly`, `max_branches`, `max_menu_items`, `features`) VALUES
('Starter', 'starter', 29.00, 290.00, 1, 50, '["Basic features", "Email support", "1 branch"]'),
('Professional', 'professional', 79.00, 790.00, 5, 200, '["All Starter features", "Priority support", "5 branches", "Custom domain"]'),
('Enterprise', 'enterprise', 199.00, 1990.00, NULL, NULL, '["All Professional features", "Unlimited branches", "Dedicated support", "White-label"]');

-- Sample Restaurant
INSERT INTO `restaurants` (`name`, `slug`, `email`, `phone`, `currency`, `status`) VALUES
('Demo Restaurant', 'demo-restaurant', 'demo@restaurant.com', '+1234567890', 'USD', 'active');

-- Sample Branch
INSERT INTO `branches` (`tenant_id`, `name`, `address_line1`, `city`, `state`, `country`, `latitude`, `longitude`, `phone`, `is_active`) VALUES
(1, 'Main Branch', '123 Main Street', 'New York', 'NY', 'USA', 40.7128, -74.0060, '+1234567890', TRUE);

-- Sample Opening Hours (Monday-Sunday)
INSERT INTO `opening_hours` (`branch_id`, `day_of_week`, `open_time`, `close_time`) VALUES
(1, 0, '10:00:00', '22:00:00'),
(1, 1, '10:00:00', '22:00:00'),
(1, 2, '10:00:00', '22:00:00'),
(1, 3, '10:00:00', '22:00:00'),
(1, 4, '10:00:00', '23:00:00'),
(1, 5, '10:00:00', '23:00:00'),
(1, 6, '10:00:00', '22:00:00');

-- Sample Restaurant Settings
INSERT INTO `restaurant_settings` (`tenant_id`, `theme_color`, `language`, `tax_rate`) VALUES
(1, '#FF6B6B', 'en', 8.50);

-- Sample Categories
INSERT INTO `categories` (`tenant_id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Burgers', 'burgers', 1),
(1, 'Pizza', 'pizza', 2),
(1, 'Drinks', 'drinks', 3),
(1, 'Desserts', 'desserts', 4);

-- Sample Menu Item
INSERT INTO `menu_items` (`tenant_id`, `category_id`, `name`, `slug`, `description`, `price`, `is_available`) VALUES
(1, 1, 'Classic Burger', 'classic-burger', 'Juicy beef patty with lettuce, tomato, and cheese', 12.99, TRUE),
(1, 2, 'Margherita Pizza', 'margherita-pizza', 'Classic tomato sauce, mozzarella, and basil', 14.99, TRUE);

-- Sample Modifier
INSERT INTO `item_modifiers` (`item_id`, `name`, `type`, `is_required`) VALUES
(1, 'Size', 'single', TRUE);

-- Sample Modifier Options
INSERT INTO `modifier_options` (`modifier_id`, `name`, `price_modifier`, `is_default`) VALUES
(1, 'Regular', 0.00, TRUE),
(1, 'Large', 3.00, FALSE);
```

---

## **5. Database Indexes Strategy**

### **5.1 Critical Indexes**

```sql
-- Performance optimization indexes
CREATE INDEX idx_orders_tenant_status ON orders(tenant_id, status);
CREATE INDEX idx_orders_created_at_desc ON orders(created_at DESC);
CREATE INDEX idx_menu_items_tenant_category ON menu_items(tenant_id, category_id);
CREATE INDEX idx_customers_phone_tenant ON customers(phone, tenant_id);
```

---

## **6. Database Migrations**

Create migration files in `/database/migrations/` directory. Example naming:
- `001_create_restaurants_table.sql`
- `002_create_users_table.sql`
- `003_create_branches_table.sql`
- etc.

---

**Document Version:** 1.0
**Last Updated:** 2025-11-19
**Total Tables:** 37 tables

