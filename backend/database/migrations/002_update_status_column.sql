-- Update status column to flexible VARCHAR and set final lifecycle values
-- Run this on database `surat_app`

ALTER TABLE `surat`
  MODIFY `status` VARCHAR(64) NOT NULL DEFAULT 'MENUNGGU_TINDAKAN_UMUM';

-- Optional: normalize legacy statuses (uncomment if needed)
-- UPDATE surat SET status='MENUNGGU_TINDAKAN_UMUM' WHERE status IN ('MENUNGGU_UMUM');
-- UPDATE surat SET status='SIAP_DISEBARKAN' WHERE status='SIAP_DISEBARKAN';
-- UPDATE surat SET status='SELESAI' WHERE status IN ('TERDISTRIBUSI');
