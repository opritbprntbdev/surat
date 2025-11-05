-- ============================================================
-- Migration 005: Cabang master + Konfigurasi Penomoran Cabang
-- Target: MySQL 5.7
-- Safe guards: CREATE IF NOT EXISTS, column existence checks via INFORMATION_SCHEMA
-- ============================================================

-- 0) Pastikan berada di database aktif yang dipakai aplikasi
--    Jika perlu: USE `surat_app`;

-- 1) Tabel master cabang
CREATE TABLE IF NOT EXISTS `cabang` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(150) NOT NULL,
  `kode_kantor` CHAR(3) NOT NULL,
  `kode` VARCHAR(16) NULL,
  `aktif` ENUM('YA','TIDAK') NOT NULL DEFAULT 'YA',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kode_kantor` (`kode_kantor`),
  KEY `idx_cabang_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Tambahkan kolom cabang_id ke tabel user (nullable)
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user' AND COLUMN_NAME = 'cabang_id'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `user` ADD COLUMN `cabang_id` INT NULL AFTER `divisi_id`, ADD KEY `idx_user_cabang`(`cabang_id`);',
  'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Tambahkan kolom cabang_id ke tabel surat (nullable)
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'surat' AND COLUMN_NAME = 'cabang_id'
);
SET @ddl := IF(@col_exists = 0,
  'ALTER TABLE `surat` ADD COLUMN `cabang_id` INT NULL AFTER `pengirim_id`, ADD KEY `idx_surat_cabang`(`cabang_id`);',
  'SELECT 1');
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Tabel konfigurasi penomoran per cabang dan tahun
CREATE TABLE IF NOT EXISTS `nomor_surat_cabang_config` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cabang_id` INT NOT NULL,
  `tahun` INT NOT NULL,
  `format` VARCHAR(120) NOT NULL DEFAULT '{URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}',
  `reset_policy` ENUM('YEAR','MONTH','NEVER') NOT NULL DEFAULT 'YEAR',
  `last_urut` INT NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cabang_tahun` (`cabang_id`,`tahun`),
  KEY `idx_tahun` (`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Seed awal 44 cabang (kode_kantor 001..044) jika belum ada
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 001','001','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='001');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 002','002','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='002');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 003','003','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='003');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 004','004','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='004');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 005','005','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='005');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 006','006','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='006');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 007','007','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='007');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 008','008','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='008');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 009','009','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='009');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 010','010','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='010');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 011','011','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='011');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 012','012','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='012');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 013','013','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='013');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 014','014','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='014');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 015','015','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='015');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 016','016','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='016');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 017','017','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='017');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 018','018','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='018');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 019','019','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='019');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 020','020','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='020');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 021','021','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='021');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 022','022','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='022');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 023','023','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='023');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 024','024','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='024');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 025','025','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='025');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 026','026','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='026');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 027','027','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='027');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 028','028','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='028');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 029','029','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='029');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 030','030','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='030');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 031','031','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='031');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 032','032','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='032');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 033','033','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='033');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 034','034','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='034');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 035','035','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='035');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 036','036','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='036');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 037','037','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='037');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 038','038','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='038');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 039','039','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='039');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 040','040','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='040');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 041','041','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='041');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 042','042','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='042');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 043','043','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='043');
INSERT INTO `cabang` (`nama`,`kode_kantor`,`kode`) SELECT 'KC 044','044','KC' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM cabang WHERE kode_kantor='044');

-- 6) Seed konfigurasi penomoran default untuk tahun berjalan jika belum ada
SET @tahun := YEAR(CURDATE());
INSERT INTO `nomor_surat_cabang_config` (`cabang_id`,`tahun`,`format`,`reset_policy`,`last_urut`)
SELECT c.id, @tahun, '{URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}', 'YEAR', 0
FROM cabang c
LEFT JOIN nomor_surat_cabang_config n ON n.cabang_id = c.id AND n.tahun = @tahun
WHERE n.id IS NULL;
