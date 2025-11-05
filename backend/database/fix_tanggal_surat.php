<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

echo "=== BEFORE UPDATE (with time) ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE id IN (11, 12)");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

echo PHP_EOL . "=== UPDATING ===" . PHP_EOL;
$updateResult = $db->query("UPDATE surat SET tanggal_surat = created_at WHERE id IN (11, 12) AND tanggal_surat != created_at");
echo "Affected rows: " . $db->affected_rows . PHP_EOL;

echo PHP_EOL . "=== AFTER UPDATE (with time) ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE id IN (11, 12)");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
