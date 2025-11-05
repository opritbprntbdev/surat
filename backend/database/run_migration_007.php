<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

echo "=== MIGRATION 007: Fix tanggal_surat to DATETIME ===" . PHP_EOL . PHP_EOL;

// Step 1: Alter column type
echo "Step 1: Altering tanggal_surat from DATE to DATETIME..." . PHP_EOL;
try {
    $db->query("ALTER TABLE surat MODIFY COLUMN tanggal_surat DATETIME NOT NULL");
    echo "✓ Successfully altered column type" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Step 2: Update existing records
echo "Step 2: Updating existing records with 00:00:00 time..." . PHP_EOL;
try {
    $result = $db->query("
        UPDATE surat 
        SET tanggal_surat = created_at 
        WHERE TIME(tanggal_surat) = '00:00:00' 
          AND DATE(tanggal_surat) = DATE(created_at)
    ");
    echo "✓ Successfully updated " . $db->affected_rows . " records" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Verify
echo "Step 3: Verifying updated records..." . PHP_EOL;
$result = $db->query("
    SELECT id, nomor_surat, 
           DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, 
           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
    FROM surat 
    WHERE id IN (11, 12)
");

while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Nomor: {$row['nomor_surat']}" . PHP_EOL;
    echo "  tanggal_surat: {$row['tanggal_surat']}" . PHP_EOL;
    echo "  created_at:    {$row['created_at']}" . PHP_EOL;
    echo "  Match: " . ($row['tanggal_surat'] === $row['created_at'] ? 'YES ✓' : 'NO ✗') . PHP_EOL . PHP_EOL;
}

echo "Migration 007 completed successfully!" . PHP_EOL;
