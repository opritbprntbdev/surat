<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
echo "Running migration 004...\n";
try {
    $db->query("CREATE TABLE IF NOT EXISTS `surat_star` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_star` (`surat_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Throwable $e) { /* ignore */ }

try {
    $db->query("CREATE TABLE IF NOT EXISTS `surat_read` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `surat_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_read` (`surat_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Throwable $e) { /* ignore */ }

echo "Migration 004 applied (or already in place).\n";
