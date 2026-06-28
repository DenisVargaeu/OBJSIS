CREATE TABLE IF NOT EXISTS `maintenance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `message` text DEFAULT 'We are currently performing scheduled maintenance. Please try again later.',
  `allowed_ips` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `maintenance_settings` (`id`, `is_enabled`, `message`, `allowed_ips`) VALUES (1, 0, 'We are currently performing scheduled maintenance. Please try again later.', NULL) ON DUPLICATE KEY UPDATE id=1;
