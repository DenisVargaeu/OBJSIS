-- RBAC Migration Script

-- 1. Create permissions table
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create roles table
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create role_permissions mapping table
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Seed Permissions
INSERT INTO `permissions` (`name`, `description`) VALUES
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
('manage_roles', 'Ability to manage roles and their permissions');

-- 5. Seed Roles
INSERT INTO `roles` (`name`, `display_name`) VALUES
('admin', 'Administrator'),
('manager', 'Manager'),
('cook', 'Chef / Cook'),
('waiter', 'Server / Waiter'),
('inventory', 'Inventory Staff');

-- 6. Map Permissions to Roles
-- Administrator: Everything
INSERT INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'admin';

-- Manager: Most things except roles/system settings
INSERT INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'manager' AND p.name NOT IN ('manage_roles', 'manage_settings');

-- Cook: Can view orders and manage status, view menu
INSERT INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'cook' AND p.name IN ('view_orders', 'manage_orders', 'view_menu', 'view_dashboard');

-- Waiter: Can view/manage orders, view menu
INSERT INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'waiter' AND p.name IN ('view_orders', 'manage_orders', 'view_menu', 'view_dashboard');

-- Inventory: Can view menu and manage inventory
INSERT INTO `role_permissions` (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.name = 'inventory' AND p.name IN ('view_menu', 'view_inventory', 'manage_inventory', 'view_dashboard');

-- 7. Add role_id to users table
ALTER TABLE `users` ADD COLUMN `role_id` int(11) DEFAULT NULL,
ADD CONSTRAINT `users_ibfk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

-- 8. Migrate Data (from old enum role)
UPDATE `users` u 
JOIN `roles` r ON u.role = r.name 
SET u.role_id = r.id;

-- 9. Drop old role enum column (Wait, let's keep it until verified? No, let's be bold if we are sure).
-- I will keep it for a moment but the code should start using role_id.
-- Actually, let's drop it to be "better" and clean.
-- ALTER TABLE `users` DROP COLUMN `role`;
