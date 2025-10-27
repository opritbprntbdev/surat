<?php
/**
 * Database Migration Runner
 * Run this file to create/update database tables
 */

require_once '../config/pdo_connection.php';

function runMigration($conn, $migrationFile) {
    echo "Running migration: " . basename($migrationFile) . "\n";
    
    $sql = file_get_contents($migrationFile);
    
    // Split SQL statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
                echo "✓ Executed statement successfully\n";
            } catch (PDOException $e) {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
}

try {
    echo "Starting database migration...\n\n";
    
    // Get all migration files
    $migrationDir = __DIR__ . '/../database/migrations/';
    $migrationFiles = glob($migrationDir . '*.sql');
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "No migration files found in: $migrationDir\n";
        exit;
    }
    
    foreach ($migrationFiles as $file) {
        runMigration($conn, $file);
        echo "\n";
    }
    
    echo "Migration completed successfully!\n";
    
    // Test connection
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table has {$result['count']} records.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>