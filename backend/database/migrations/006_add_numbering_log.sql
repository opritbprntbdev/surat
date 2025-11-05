-- ============================================================
-- Migration 006: Log Penomoran Surat + Update Config
-- Target: MySQL 5.7
-- Purpose: Tracking semua nomor yang diambil, cegah duplikasi & mubazir
-- ============================================================

-- 1) Tambah kolom jenis_surat di nomor_surat_cabang_config
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'nomor_surat_cabang_config' 
    AND COLUMN_NAME = 'jenis_surat'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `nomor_surat_cabang_config` 
   ADD COLUMN `jenis_surat` ENUM(''MASUK'',''KELUAR'') NOT NULL DEFAULT ''KELUAR'' AFTER `cabang_id`;',
  'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Update unique constraint (cabang + tahun + jenis)
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'nomor_surat_cabang_config'
    AND INDEX_NAME = 'uq_cabang_tahun'
);
SET @ddl := IF(@idx_exists > 0,
  'ALTER TABLE `nomor_surat_cabang_config` DROP INDEX `uq_cabang_tahun`;',
  'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE `nomor_surat_cabang_config` 
ADD UNIQUE KEY `uq_cabang_tahun_jenis` (`cabang_id`, `tahun`, `jenis_surat`);

-- 3) Update format default untuk jenis KELUAR (lebih presisi)
UPDATE `nomor_surat_cabang_config`
SET `format` = '{URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}'
WHERE `jenis_surat` = 'KELUAR';

-- 4) Seed config MASUK untuk semua cabang tahun berjalan
SET @tahun := YEAR(CURDATE());
INSERT INTO `nomor_surat_cabang_config` (`cabang_id`, `jenis_surat`, `tahun`, `format`, `reset_policy`, `last_urut`)
SELECT c.id, 'MASUK', @tahun, '{URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}', 'YEAR', 0
FROM cabang c
LEFT JOIN nomor_surat_cabang_config n ON n.cabang_id = c.id AND n.tahun = @tahun AND n.jenis_surat = 'MASUK'
WHERE n.id IS NULL;

-- 5) Tabel log penomoran surat
CREATE TABLE IF NOT EXISTS `log_nomor_surat` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cabang_id` INT NOT NULL,
  `jenis_surat` ENUM('MASUK','KELUAR') NOT NULL,
  `nomor_surat` VARCHAR(120) NOT NULL,
  `nomor_urut` INT NOT NULL,
  `tanggal_ambil` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` INT NOT NULL,
  `surat_id` INT NULL,
  `status` ENUM('RESERVED','USED','CANCELLED') NOT NULL DEFAULT 'RESERVED',
  `keterangan` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cabang_jenis` (`cabang_id`, `jenis_surat`),
  INDEX `idx_surat` (`surat_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_tanggal` (`tanggal_ambil`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Tambah kolom jenis_surat di tabel surat (opsional, untuk tracking)
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'surat' 
    AND COLUMN_NAME = 'jenis_surat'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `surat` 
   MODIFY COLUMN `jenis_surat` ENUM(''MASUK'',''KELUAR'') NOT NULL DEFAULT ''KELUAR'';',
  'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
