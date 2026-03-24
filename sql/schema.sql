-- OBJSIS V2 - Complete Database Schema
-- Version: 2.5.4
-- Last Updated: 2026-03-23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. SETTINGS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('restaurant_name', 'My Restaurant');

-- --------------------------------------------------------
-- 2. PERMISSIONS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `permissions` (`name`, `description`) VALUES
('view_dashboard', 'Ability to view the main dashboard'),
('manage_users', 'Ability to add, edit, and delete users'),
('view_menu', 'Ability to view the menu management page'),
('manage_menu', 'Ability to edit menu items and categories'),
('view_orders', 'Ability to view orders (e.g., KDS or Order list)'),
('manage_orders', 'Ability to create, update, or cancel orders'),
('view_inventory', 'Ability to view stock levels'),
('manage_inventory', 'Ability to update stock and logs'),
('view_reports', 'Ability to view sales and performance reports'),
('manage_settings', 'Ability to change system settings'),
('manage_roles', 'Ability to manage roles and their permissions'),
('manage_system', 'Global system management permission');

-- --------------------------------------------------------
-- 3. ROLES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `roles` (`name`, `display_name`) VALUES
('admin', 'Administrator'),
('manager', 'Manager'),
('cook', 'Chef / Cook'),
('waiter', 'Server / Waiter'),
('inventory', 'Inventory Staff');

-- --------------------------------------------------------
-- 4. ROLE_PERMISSIONS (mapping)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin: Everything
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'admin';

-- Manager: Most things except roles/system settings
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'manager' AND p.name NOT IN ('manage_roles', 'manage_settings');

-- Cook: View orders, manage order status, view menu, dashboard
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'cook' AND p.name IN ('view_orders', 'manage_orders', 'view_menu', 'view_dashboard');

-- Waiter: View/manage orders, view menu, dashboard
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'waiter' AND p.name IN ('view_orders', 'manage_orders', 'view_menu', 'view_dashboard');

-- Inventory: View menu, manage inventory, dashboard
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'inventory' AND p.name IN ('view_menu', 'view_inventory', 'manage_inventory', 'view_dashboard');

-- --------------------------------------------------------
-- 5. USERS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `role` enum('admin','cook','waiter','inventory') NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin (PIN: 1234)
INSERT INTO `users` (`name`, `pin_hash`, `role`) VALUES
('Admin', '$2y$10$R9h/lIPzLpC.qGvFh9U9u.u.u.u.u.u.u.u.u.u.u.u.u.u.u.u.', 'admin');

-- --------------------------------------------------------
-- 6. CATEGORIES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES
(1, 'Appetizers', 1),
(2, 'Main Courses', 2),
(3, 'Desserts', 3),
(4, 'Beverages', 4);

-- --------------------------------------------------------
-- 7. MENU_ITEMS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `allergens` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 8. TABLES
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('free','occupied','reserved') DEFAULT 'free',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tables` (`id`, `name`, `capacity`, `status`) VALUES
(1, 'Table 1', 4, 'free'),
(2, 'Table 2', 4, 'free'),
(3, 'Table 3', 2, 'free'),
(4, 'Table 4', 6, 'free'),
(5, 'Table 5', 4, 'free');

-- --------------------------------------------------------
-- 9. ORDERS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` int(11) NOT NULL,
  `status` enum('received','preparing','ready','delivered','paid','cancelled') DEFAULT 'received',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 10. ORDER_ITEMS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price_at_time` decimal(10,2) NOT NULL,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 11. COUPONS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `type` enum('fixed','percent') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expiration_date` datetime DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `one_time_use` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 12. SHIFTS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `start_time` datetime DEFAULT current_timestamp(),
  `end_time` datetime DEFAULT NULL,
  `cash_start` decimal(10,2) DEFAULT 0.00,
  `cash_end` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 13. INVENTORY
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `current_quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'pcs',
  `critical_threshold` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 14. MENU_ITEM_INGREDIENTS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `menu_item_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `menu_item_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_item_ingredients_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 15. INVENTORY_LOGS
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `change_type` enum('purchase','sale','waste','correction') NOT NULL,
  `quantity_change` decimal(10,2) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
