<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
echo "Running migration 003...\n";
try {
    $db->query("ALTER TABLE `surat_penerima` ADD COLUMN `request_batch` VARCHAR(36) NULL DEFAULT NULL AFTER `tipe_penerima`");
} catch (Throwable $e) {
    // ignore if already exists
}
try {
    $db->query("CREATE INDEX `idx_surat_batch` ON `surat_penerima` (`surat_id`, `request_batch`)");
} catch (Throwable $e) {
    // ignore if already exists
}
echo "Migration 003 applied (or already in place).\n";
