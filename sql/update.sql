-- OBJSIS V2 Database Update Script
-- Run this file via Admin Panel → Updates (or manually in phpMyAdmin)
-- Target: upgrade any pre-4.0.0 install to v4.0.0
-- Safe to re-run (all statements use IF NOT EXISTS / INSERT IGNORE)

-- --------------------------------------------------------
-- 1. SECTIONS TABLE (restaurant rooms / areas)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-chair',
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `sections` (`name`, `icon`, `sort_order`) VALUES
  ('Main Hall',    'fa-utensils',   1),
  ('Terrace',      'fa-sun',        2),
  ('Bar',          'fa-wine-glass', 3),
  ('Private Room', 'fa-door-closed',4);

-- --------------------------------------------------------
-- 2. ALTER TABLES — add section_id + sort_order
-- --------------------------------------------------------
-- Add section_id column if it doesn't exist
SET @dbname = DATABASE();
SET @tbl    = 'tables';
SET @col1   = 'section_id';
SET @col2   = 'sort_order';

SET @sql1 = (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col1) = 0,
    'ALTER TABLE `tables` ADD COLUMN `section_id` int(11) DEFAULT NULL AFTER `status`',
    'SELECT 1'
  )
);
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @sql2 = (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col2) = 0,
    'ALTER TABLE `tables` ADD COLUMN `sort_order` int(11) DEFAULT 0 AFTER `section_id`',
    'SELECT 1'
  )
);
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Add foreign key if it doesn't exist
SET @fk_sql = (
  SELECT IF(
    (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND CONSTRAINT_NAME = 'tables_ibfk_1') = 0,
    'ALTER TABLE `tables` ADD CONSTRAINT `tables_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL',
    'SELECT 1'
  )
);
PREPARE stmt_fk FROM @fk_sql; EXECUTE stmt_fk; DEALLOCATE PREPARE stmt_fk;

-- --------------------------------------------------------
-- 3. BACKFILL — assign existing tables to default sections
-- --------------------------------------------------------
UPDATE `tables` SET section_id = 1, sort_order = id WHERE section_id IS NULL;
UPDATE `tables` SET sort_order = id WHERE sort_order = 0;

-- --------------------------------------------------------
-- Done
-- --------------------------------------------------------
SELECT 'Update to 4.0.0 complete — sections table added, tables now support rooms and sort order.' AS status;
