-- OBJSIS V2 Database Backup
-- Created: 2026-01-01 09:52:10

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` VALUES ('1', 'Drinks', '1');
INSERT INTO `categories` VALUES ('2', 'Main Course', '2');
INSERT INTO `categories` VALUES ('3', 'Desserts', '3');

DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `type` enum('fixed','percent') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiration_date` datetime DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `current_uses` int(11) DEFAULT 0,
  `one_time_use` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` VALUES ('1', '1', 'Cola', 'Refreshing drink', '2.50', NULL, '1', NULL, '2025-12-31 15:47:32');
INSERT INTO `menu_items` VALUES ('2', '2', 'Burger', 'Tasty beef burger', '8.90', NULL, '1', NULL, '2025-12-31 15:47:32');
INSERT INTO `menu_items` VALUES ('3', '3', 'Tiramisu', 'Vinikajuci kolac', '10.00', NULL, '1', NULL, '2025-12-31 15:59:04');

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` VALUES ('1', '1', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('2', '1', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('3', '2', '2', '3', '8.90', NULL);
INSERT INTO `order_items` VALUES ('4', '3', '3', '1', '10.00', NULL);
INSERT INTO `order_items` VALUES ('5', '3', '2', '2', '8.90', NULL);
INSERT INTO `order_items` VALUES ('6', '3', '1', '2', '2.50', NULL);
INSERT INTO `order_items` VALUES ('7', '4', '1', '5', '2.50', NULL);
INSERT INTO `order_items` VALUES ('8', '5', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('9', '6', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('10', '6', '3', '1', '10.00', NULL);
INSERT INTO `order_items` VALUES ('11', '6', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('12', '7', '2', '5', '8.90', NULL);
INSERT INTO `order_items` VALUES ('13', '8', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('14', '8', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('15', '8', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('16', '9', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('17', '9', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('18', '9', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('19', '9', '3', '3', '10.00', NULL);
INSERT INTO `order_items` VALUES ('20', '10', '1', '5', '2.50', NULL);
INSERT INTO `order_items` VALUES ('21', '10', '3', '4', '10.00', NULL);
INSERT INTO `order_items` VALUES ('22', '11', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('23', '12', '1', '3', '2.50', NULL);
INSERT INTO `order_items` VALUES ('24', '12', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('25', '13', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('26', '14', '2', '1', '8.90', NULL);
INSERT INTO `order_items` VALUES ('27', '14', '1', '1', '2.50', NULL);
INSERT INTO `order_items` VALUES ('28', '15', '1', '10', '2.50', NULL);
INSERT INTO `order_items` VALUES ('29', '16', '1', '1', '2.50', NULL);

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` int(11) NOT NULL,
  `status` enum('received','preparing','ready','delivered','paid','cancelled') DEFAULT 'received',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `coupon_code` varchar(20) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `is_training` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_training` (`is_training`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` VALUES ('1', '1', 'paid', '11.40', '0.00', NULL, NULL, '0', '2025-12-31 15:48:26', '2025-12-31 15:50:11');
INSERT INTO `orders` VALUES ('2', '1', 'paid', '26.70', '0.00', NULL, NULL, '0', '2025-12-31 15:54:16', '2025-12-31 16:29:10');
INSERT INTO `orders` VALUES ('3', '1', 'paid', '32.80', '0.00', NULL, NULL, '0', '2025-12-31 16:02:10', '2025-12-31 16:29:08');
INSERT INTO `orders` VALUES ('4', '1', 'paid', '12.50', '0.00', NULL, NULL, '0', '2025-12-31 16:28:31', '2025-12-31 17:07:04');
INSERT INTO `orders` VALUES ('5', '1', 'paid', '2.25', '0.25', 'SAVE10', NULL, '0', '2025-12-31 16:28:56', '2025-12-31 17:07:00');
INSERT INTO `orders` VALUES ('6', '1', 'paid', '17.12', '4.28', 'SAVE20', NULL, '0', '2025-12-31 16:30:57', '2025-12-31 16:31:31');
INSERT INTO `orders` VALUES ('7', '1', 'paid', '44.50', '0.00', NULL, NULL, '0', '2025-12-31 16:49:43', '2025-12-31 16:51:15');
INSERT INTO `orders` VALUES ('8', '1', 'paid', '13.90', '0.00', NULL, NULL, '0', '2025-12-31 17:07:15', '2025-12-31 17:10:51');
INSERT INTO `orders` VALUES ('9', '1', 'paid', '43.90', '0.00', NULL, NULL, '0', '2025-12-31 18:08:08', '2025-12-31 18:11:27');
INSERT INTO `orders` VALUES ('10', '1', 'paid', '52.50', '0.00', NULL, NULL, '0', '2025-12-31 18:16:13', '2025-12-31 18:21:00');
INSERT INTO `orders` VALUES ('11', '1', 'paid', '2.50', '0.00', NULL, NULL, '0', '2025-12-31 18:33:25', '2025-12-31 18:40:57');
INSERT INTO `orders` VALUES ('12', '1', 'paid', '16.40', '0.00', NULL, NULL, '0', '2025-12-31 18:36:26', '2025-12-31 18:40:56');
INSERT INTO `orders` VALUES ('13', '1', 'received', '2.25', '0.25', 'SAVE10', NULL, '0', '2025-12-31 18:43:10', '2025-12-31 18:43:10');
INSERT INTO `orders` VALUES ('14', '2', 'paid', '10.26', '1.14', 'SAVE10', NULL, '0', '2025-12-31 18:44:48', '2025-12-31 18:45:56');
INSERT INTO `orders` VALUES ('15', '2', 'received', '20.00', '5.00', 'SAVE20', NULL, '0', '2026-01-01 10:50:14', '2026-01-01 10:50:14');
INSERT INTO `orders` VALUES ('16', '2', 'received', '2.00', '0.50', 'SAVE20', NULL, '0', '2026-01-01 10:50:31', '2026-01-01 10:50:31');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES ('language', 'sk');
INSERT INTO `settings` VALUES ('restaurant_name', 'OBJSIS');
INSERT INTO `settings` VALUES ('training_mode', '0');

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `cash_start` decimal(10,2) DEFAULT 0.00,
  `cash_end` decimal(10,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shifts` VALUES ('1', '1', '2025-12-31 16:53:09', '2025-12-31 16:55:17', '100.00', '0.00', NULL);
INSERT INTO `shifts` VALUES ('2', '1', '2025-12-31 17:00:49', '2025-12-31 18:08:49', '0.00', '0.00', NULL);
INSERT INTO `shifts` VALUES ('3', '1', '2025-12-31 18:46:04', NULL, '0.00', '0.00', NULL);

DROP TABLE IF EXISTS `tables`;
CREATE TABLE `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('free','occupied','reserved') DEFAULT 'free',
  `x_pos` int(11) DEFAULT 0,
  `y_pos` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tables` VALUES ('1', '1', '4', 'occupied', '0', '0', '2025-12-31 16:39:15');
INSERT INTO `tables` VALUES ('2', '2', '4', 'occupied', '0', '0', '2025-12-31 16:39:34');
INSERT INTO `tables` VALUES ('3', '3', '4', 'free', '0', '0', '2025-12-31 18:10:35');
INSERT INTO `tables` VALUES ('4', '4', '4', 'free', '0', '0', '2025-12-31 18:10:40');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `role` enum('admin','cook','waiter') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES ('1', 'Admin User', '$2y$12$5SrdmGh/Cndf9yJAnbXEk.hurgBCWJkGj7XorkGV3LuFQSCGZ6uOi', 'admin', '2025-12-31 15:47:31');
INSERT INTO `users` VALUES ('2', 'Chief Cook', '$2y$12$fSSLfLCrO2DwuUcLyG6d7enXJhBAmuLZfPUsWf9ymzpKF8a3K9bYa', 'cook', '2025-12-31 15:47:31');
INSERT INTO `users` VALUES ('3', 'Main Waiter', '$2y$12$gSjpHnm/0SZETKSOuVPkK.8PUuDRuhALA0CFxxqR4Q1HgRffe6zAy', 'waiter', '2025-12-31 15:47:32');

SET FOREIGN_KEY_CHECKS=1;
