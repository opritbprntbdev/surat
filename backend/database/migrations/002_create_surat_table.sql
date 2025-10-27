-- Table: surat (compatible with existing functions and group inbox for UMUM)
CREATE TABLE IF NOT EXISTS `surat` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nomor_surat` VARCHAR(100) NULL,
  `perihal` VARCHAR(255) NOT NULL,
  `tanggal_surat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('draft','sent','archived') NOT NULL DEFAULT 'sent',
  `pengirim_id` INT UNSIGNED NOT NULL,
  `penerima_id` INT UNSIGNED NULL,
  `from_branch_id` INT UNSIGNED NOT NULL,
  `html_content` LONGTEXT NULL,
  `current_owner_type` ENUM('UMUM','DIVISI','DIREKSI','USER','CABANG') NOT NULL DEFAULT 'UMUM',
  `current_owner_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_from_branch` (`from_branch_id`),
  KEY `idx_owner_type` (`current_owner_type`),
  KEY `idx_status` (`status`),
  KEY `idx_pengirim` (`pengirim_id`),
  KEY `idx_penerima` (`penerima_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
