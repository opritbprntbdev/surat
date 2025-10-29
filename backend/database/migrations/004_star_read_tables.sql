-- Create per-user star (bookmark) table
CREATE TABLE IF NOT EXISTS `surat_star` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_star` (`surat_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create per-user read tracking table
CREATE TABLE IF NOT EXISTS `surat_read` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_read` (`surat_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
