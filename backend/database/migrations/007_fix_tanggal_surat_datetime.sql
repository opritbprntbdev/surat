-- Migration: Change tanggal_surat from DATE to DATETIME
-- Reason: Need to store time information for accurate last_activity_at calculation

USE surat_app;

-- Step 1: Alter column type from DATE to DATETIME
ALTER TABLE surat MODIFY COLUMN tanggal_surat DATETIME NOT NULL;

-- Step 2: Update existing records to use created_at timestamp
-- This will fix records that have 00:00:00 time
UPDATE surat 
SET tanggal_surat = created_at 
WHERE TIME(tanggal_surat) = '00:00:00' 
  AND DATE(tanggal_surat) = DATE(created_at);

-- Verify
SELECT id, nomor_surat, tanggal_surat, created_at 
FROM surat 
WHERE id IN (11, 12);
