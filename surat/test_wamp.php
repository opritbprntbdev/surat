<?php
// Test file khusus untuk WAMP
header('Content-Type: application/json');

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo json_encode([
    'status' => 'testing',
    'php_version' => PHP_VERSION,
    'mysqli_available' => extension_loaded('mysqli'),
    'timestamp' => date('Y-m-d H:i:s')
]);

echo "\n\n";

try {
    // Include database configuration
    require_once 'backend/config/database.php';

    // Test database connection
    $db = Database::getInstance();

    // Test query
    $emails = Database::fetchAll("SELECT COUNT(*) as total FROM emails");

    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful!',
        'total_emails' => $emails[0]['total'],
        'database_info' => [
            'host' => 'localhost',
            'database' => 'gmail_clone',
            'connected' => Database::isConnected()
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Test jika tabel exists
echo "\n\n";
try {
    $tablesExist = Database::tableExists('emails');
    echo json_encode([
        'table_check' => [
            'emails_table_exists' => $tablesExist,
            'users_table_exists' => Database::tableExists('users'),
            'attachments_table_exists' => Database::tableExists('email_attachments')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'table_check_error' => $e->getMessage()
    ]);
}
?>