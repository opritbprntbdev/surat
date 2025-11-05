<?php
// Test apakah surat.php bisa di-load tanpa error
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'UMUM';

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../function/surat_function.php';
    require_once __DIR__ . '/../function/cabang_numbering.php';
    
    echo "✓ All files loaded successfully!\n";
    echo "✓ Database class exists: " . (class_exists('Database') ? 'YES' : 'NO') . "\n";
    echo "✓ SuratFunctions class exists: " . (class_exists('SuratFunctions') ? 'YES' : 'NO') . "\n";
    echo "✓ CabangNumbering class exists: " . (class_exists('CabangNumbering') ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
