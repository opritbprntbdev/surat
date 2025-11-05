<?php
// Direct connection untuk migration
$conn = new mysqli('localhost', 'root', '', 'surat_app', 3308);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    echo "Running migration 006...\n";
    
    $sql = file_get_contents(__DIR__ . '/migrations/006_add_numbering_log.sql');
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
            if ($conn->more_results()) {
                echo ".";
            }
        } while ($conn->next_result());
        
        echo "\n✓ Migration 006 completed successfully!\n";
    } else {
        echo "✗ Migration failed: " . $conn->error . "\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
