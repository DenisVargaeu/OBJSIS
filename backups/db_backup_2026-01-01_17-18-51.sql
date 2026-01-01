

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES ('1', 'Drinks', '1');
INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES ('2', 'Main Course', '2');
INSERT INTO `categories` (`id`, `name`, `sort_order`) VALUES ('3', 'Desserts', '3');


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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `current_quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'pcs',
  `critical_threshold` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('1', 'Cola', '88.50', 'l', '2.00', '2026-01-01 11:17:45');
INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('2', 'Beef Patty', '87.00', 'pcs', '5.00', '2026-01-01 11:21:29');
INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('3', 'Cheese', '94.00', 'pcs', '5.00', '2026-01-01 11:22:25');
INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('4', 'Burger Bun ', '87.00', 'pcs', '5.00', '2026-01-01 11:23:18');
INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('5', 'Tomato', '48.50', 'pcs', '5.00', '2026-01-01 11:23:36');
INSERT INTO `inventory` (`id`, `name`, `current_quantity`, `unit`, `critical_threshold`, `created_at`) VALUES ('6', 'Cream', '0.00', 'l', '5.00', '2026-01-01 12:01:50');


CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `change_type` enum('purchase','sale','waste','correction') NOT NULL,
  `quantity_change` decimal(10,2) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('1', '1', '1', 'purchase', '10.00', '2026-01-01 11:17:45');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('2', '1', '', 'sale', '-0.50', '2026-01-01 11:18:18');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('3', '1', '', 'sale', '-9.50', '2026-01-01 11:20:06');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('4', '1', '1', 'purchase', '90.00', '2026-01-01 11:20:35');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('5', '2', '1', 'purchase', '90.00', '2026-01-01 11:21:29');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('6', '3', '1', 'purchase', '100.00', '2026-01-01 11:22:25');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('7', '4', '1', 'purchase', '90.00', '2026-01-01 11:23:18');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('8', '5', '1', 'purchase', '50.00', '2026-01-01 11:23:36');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('9', '1', '', 'sale', '-0.50', '2026-01-01 11:24:32');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('10', '2', '', 'sale', '-1.00', '2026-01-01 11:24:32');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('11', '4', '', 'sale', '-1.00', '2026-01-01 11:24:32');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('12', '3', '', 'sale', '-2.00', '2026-01-01 11:24:32');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('13', '5', '', 'sale', '-0.50', '2026-01-01 11:24:32');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('14', '6', '1', 'purchase', '1.00', '2026-01-01 12:01:50');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('15', '6', '', 'sale', '-1.00', '2026-01-01 12:02:31');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('16', '2', '', 'sale', '-2.00', '2026-01-01 13:37:37');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('17', '4', '', 'sale', '-2.00', '2026-01-01 13:37:37');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('18', '3', '', 'sale', '-4.00', '2026-01-01 13:37:37');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('19', '5', '', 'sale', '-1.00', '2026-01-01 13:37:37');
INSERT INTO `inventory_logs` (`id`, `inventory_id`, `user_id`, `change_type`, `quantity_change`, `timestamp`) VALUES ('20', '1', '', 'sale', '-1.00', '2026-01-01 13:37:37');


CREATE TABLE `menu_item_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `inventory_id` (`inventory_id`),
  CONSTRAINT `menu_item_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_item_ingredients_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('1', '1', '1', '0.50');
INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('2', '2', '2', '1.00');
INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('3', '2', '4', '1.00');
INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('4', '2', '3', '2.00');
INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('5', '2', '5', '0.50');
INSERT INTO `menu_item_ingredients` (`id`, `menu_item_id`, `inventory_id`, `quantity_required`) VALUES ('6', '3', '6', '0.25');


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

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `allergens`, `created_at`) VALUES ('1', '1', 'Cola', 'Refreshing drink', '2.50', '', '1', '', '2025-12-31 15:47:32');
INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `allergens`, `created_at`) VALUES ('2', '2', 'Burger', 'Tasty beef burger', '8.90', '', '1', '', '2025-12-31 15:47:32');
INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `allergens`, `created_at`) VALUES ('3', '3', 'Tiramisu', 'Vinikajuci kolac', '10.00', '', '0', '', '2025-12-31 15:59:04');


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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('1', '1', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('2', '1', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('3', '2', '2', '3', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('4', '3', '3', '1', '10.00', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('5', '3', '2', '2', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('6', '3', '1', '2', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('7', '4', '1', '5', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('8', '5', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('9', '6', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('10', '6', '3', '1', '10.00', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('11', '6', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('12', '7', '2', '5', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('13', '8', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('14', '8', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('15', '8', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('16', '9', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('17', '9', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('18', '9', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('19', '9', '3', '3', '10.00', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('20', '10', '1', '5', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('21', '10', '3', '4', '10.00', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('22', '11', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('23', '12', '1', '3', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('24', '12', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('25', '13', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('26', '14', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('27', '14', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('28', '15', '1', '10', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('29', '16', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('30', '17', '1', '4', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('31', '17', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('32', '18', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('33', '19', '1', '19', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('34', '20', '1', '1', '2.50', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('35', '20', '2', '1', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('36', '21', '3', '4', '10.00', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('37', '22', '2', '2', '8.90', '');
INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `quantity`, `price_at_time`, `note`) VALUES ('38', '22', '1', '2', '2.50', '');


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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('1', '1', 'paid', '11.40', '0.00', '', '', '0', '2025-12-31 15:48:26', '2025-12-31 15:50:11');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('2', '1', 'paid', '26.70', '0.00', '', '', '0', '2025-12-31 15:54:16', '2025-12-31 16:29:10');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('3', '1', 'paid', '32.80', '0.00', '', '', '0', '2025-12-31 16:02:10', '2025-12-31 16:29:08');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('4', '1', 'paid', '12.50', '0.00', '', '', '0', '2025-12-31 16:28:31', '2025-12-31 17:07:04');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('5', '1', 'paid', '2.25', '0.25', 'SAVE10', '', '0', '2025-12-31 16:28:56', '2025-12-31 17:07:00');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('6', '1', 'paid', '17.12', '4.28', 'SAVE20', '', '0', '2025-12-31 16:30:57', '2025-12-31 16:31:31');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('7', '1', 'paid', '44.50', '0.00', '', '', '0', '2025-12-31 16:49:43', '2025-12-31 16:51:15');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('8', '1', 'paid', '13.90', '0.00', '', '', '0', '2025-12-31 17:07:15', '2025-12-31 17:10:51');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('9', '1', 'paid', '43.90', '0.00', '', '', '0', '2025-12-31 18:08:08', '2025-12-31 18:11:27');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('10', '1', 'paid', '52.50', '0.00', '', '', '0', '2025-12-31 18:16:13', '2025-12-31 18:21:00');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('11', '1', 'paid', '2.50', '0.00', '', '', '0', '2025-12-31 18:33:25', '2025-12-31 18:40:57');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('12', '1', 'paid', '16.40', '0.00', '', '', '0', '2025-12-31 18:36:26', '2025-12-31 18:40:56');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('13', '1', 'paid', '2.25', '0.25', 'SAVE10', '', '0', '2025-12-31 18:43:10', '2026-01-01 11:08:08');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('14', '2', 'paid', '10.26', '1.14', 'SAVE10', '', '0', '2025-12-31 18:44:48', '2025-12-31 18:45:56');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('15', '2', 'paid', '20.00', '5.00', 'SAVE20', '', '0', '2026-01-01 10:50:14', '2026-01-01 11:08:05');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('16', '2', 'paid', '2.00', '0.50', 'SAVE20', '', '0', '2026-01-01 10:50:31', '2026-01-01 10:54:31');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('17', '1', 'paid', '18.90', '0.00', '', '', '0', '2026-01-01 11:10:53', '2026-01-01 11:59:59');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('18', '1', 'paid', '2.50', '0.00', '', '', '0', '2026-01-01 11:18:18', '2026-01-01 11:59:57');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('19', '1', 'paid', '47.50', '0.00', '', '', '0', '2026-01-01 11:20:06', '2026-01-01 11:59:55');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('20', '1', 'paid', '11.40', '0.00', '', '', '0', '2026-01-01 11:24:32', '2026-01-01 11:59:53');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('21', '4', 'received', '40.00', '0.00', '', '', '0', '2026-01-01 12:02:31', '2026-01-01 12:02:31');
INSERT INTO `orders` (`id`, `table_number`, `status`, `total_price`, `discount_amount`, `coupon_code`, `note`, `is_training`, `created_at`, `updated_at`) VALUES ('22', '1', 'received', '18.24', '4.56', 'SAVE20', '', '0', '2026-01-01 13:37:37', '2026-01-01 13:37:37');


CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('language', 'sk');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('restaurant_name', 'OBJSIS');
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES ('training_mode', '0');


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

INSERT INTO `shifts` (`id`, `user_id`, `start_time`, `end_time`, `cash_start`, `cash_end`, `note`) VALUES ('1', '1', '2025-12-31 16:53:09', '2025-12-31 16:55:17', '100.00', '0.00', '');
INSERT INTO `shifts` (`id`, `user_id`, `start_time`, `end_time`, `cash_start`, `cash_end`, `note`) VALUES ('2', '1', '2025-12-31 17:00:49', '2025-12-31 18:08:49', '0.00', '0.00', '');
INSERT INTO `shifts` (`id`, `user_id`, `start_time`, `end_time`, `cash_start`, `cash_end`, `note`) VALUES ('3', '1', '2025-12-31 18:46:04', '2026-01-01 11:04:32', '0.00', '0.00', '');


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

INSERT INTO `tables` (`id`, `name`, `capacity`, `status`, `x_pos`, `y_pos`, `created_at`) VALUES ('1', '1', '4', 'occupied', '0', '0', '2025-12-31 16:39:15');
INSERT INTO `tables` (`id`, `name`, `capacity`, `status`, `x_pos`, `y_pos`, `created_at`) VALUES ('2', '2', '4', 'free', '0', '0', '2025-12-31 16:39:34');
INSERT INTO `tables` (`id`, `name`, `capacity`, `status`, `x_pos`, `y_pos`, `created_at`) VALUES ('3', '3', '4', 'free', '0', '0', '2025-12-31 18:10:35');
INSERT INTO `tables` (`id`, `name`, `capacity`, `status`, `x_pos`, `y_pos`, `created_at`) VALUES ('4', '4', '4', 'occupied', '0', '0', '2025-12-31 18:10:40');


CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `role` enum('admin','cook','waiter','inventory') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `name`, `pin_hash`, `role`, `created_at`) VALUES ('1', 'Admin User', '$2y$12$5SrdmGh/Cndf9yJAnbXEk.hurgBCWJkGj7XorkGV3LuFQSCGZ6uOi', 'admin', '2025-12-31 15:47:31');
INSERT INTO `users` (`id`, `name`, `pin_hash`, `role`, `created_at`) VALUES ('2', 'Chief Cook', '$2y$12$fSSLfLCrO2DwuUcLyG6d7enXJhBAmuLZfPUsWf9ymzpKF8a3K9bYa', 'cook', '2025-12-31 15:47:31');
INSERT INTO `users` (`id`, `name`, `pin_hash`, `role`, `created_at`) VALUES ('3', 'Main Waiter', '$2y$12$gSjpHnm/0SZETKSOuVPkK.8PUuDRuhALA0CFxxqR4Q1HgRffe6zAy', 'waiter', '2025-12-31 15:47:32');
