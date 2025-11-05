<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

echo "=== Checking surat with nomor 002.11.KC.007 ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE nomor_surat LIKE '%002.11.KC.007%' ORDER BY id DESC LIMIT 5");

while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;
}

echo PHP_EOL . "=== Checking all surat created today with 00:00:00 timestamp ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE DATE(tanggal_surat) = CURDATE() AND TIME(tanggal_surat) = '00:00:00' ORDER BY id DESC LIMIT 10");

echo "Found " . $result->num_rows . " surat with 00:00:00 timestamp today" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;
}

echo PHP_EOL . "=== Fix: Update tanggal_surat to created_at for these records ===" . PHP_EOL;
$updateResult = $db->query("UPDATE surat SET tanggal_surat = created_at WHERE DATE(created_at) = CURDATE() AND TIME(tanggal_surat) = '00:00:00'");

if ($updateResult) {
    echo "Successfully updated " . $db->affected_rows . " records" . PHP_EOL;
} else {
    echo "Failed to update: " . $db->error . PHP_EOL;
}

echo PHP_EOL . "=== Verify updated records ===" . PHP_EOL;
$result = $db->query("SELECT id, nomor_surat, DATE_FORMAT(tanggal_surat, '%Y-%m-%d %H:%i:%s') as tanggal_surat, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at FROM surat WHERE nomor_surat LIKE '%002.11.KC.007%' ORDER BY id DESC LIMIT 5");

while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;
}
