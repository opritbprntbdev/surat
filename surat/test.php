<?php
// Test file to verify database connection and API functionality
header('Content-Type: application/json');

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
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>