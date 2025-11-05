<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

echo "=== CURRENT STATE ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE id IN (11, 12)");
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Nomor: {$row['nomor_surat']}" . PHP_EOL;
    echo "  tanggal_surat: {$row['tanggal_surat']}" . PHP_EOL;
    echo "  created_at:    {$row['created_at']}" . PHP_EOL;
    echo "  Match: " . ($row['tanggal_surat'] === $row['created_at'] ? 'YES' : 'NO') . PHP_EOL . PHP_EOL;
}
