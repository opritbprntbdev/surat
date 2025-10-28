<?php
// Run migration 002: change surat.status to VARCHAR(64)
require_once __DIR__ . '/../config/database.php';

echo "Running migration 002...\n";
$db = Database::getInstance();
$sql = "ALTER TABLE `surat` MODIFY `status` VARCHAR(64) NOT NULL DEFAULT 'MENUNGGU_TINDAKAN_UMUM'";

try {
    $db->query($sql);
    echo "Migration 002 applied successfully.\n";
    exit(0);
} catch (Throwable $e) {
    // If column is already VARCHAR, this will likely throw a 'Invalid use of NULL value' or no-op depending on MySQL; just report error
    fwrite(STDERR, "Failed to apply migration: " . $e->getMessage() . "\n");
    exit(1);
}
