-- Add request_batch to surat_penerima for tracking parallel disposition rounds
ALTER TABLE `surat_penerima`
  ADD COLUMN `request_batch` VARCHAR(36) NULL DEFAULT NULL AFTER `tipe_penerima`;

-- Optional index to speed up queries
CREATE INDEX `idx_surat_batch` ON `surat_penerima` (`surat_id`, `request_batch`);
