<?php
session_start();

// Simulate logged in cabang user
$_SESSION['user_id'] = 7; // kckayangan@bprntb.co.id
$_SESSION['role'] = 'CABANG';
$_SESSION['cabang_id'] = 7; // KC KAYANGAN (kode 007)

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/cabang_numbering.php';

echo "Testing CabangNumbering::reserve()\n";
echo "User ID: 7\n";
echo "Cabang ID: 7 (KC KAYANGAN - kode 007)\n";
echo "Jenis: KELUAR\n\n";

try {
    $result = CabangNumbering::reserve(7, 'KELUAR', 7);
    echo "✓ SUCCESS!\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "✗ FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
