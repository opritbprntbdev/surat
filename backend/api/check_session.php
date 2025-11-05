<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

// Refresh cabang_id jika belum ada di session (untuk backward compatibility)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'CABANG' && empty($_SESSION['cabang_id'])) {
    try {
        require_once __DIR__ . '/../config/database_wamp.php';
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT cabang_id FROM user WHERE id=? LIMIT 1");
        $userId = (int)$_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['cabang_id'] = $row['cabang_id'];
        }
    } catch (Exception $e) {
        error_log('Error refreshing cabang_id: ' . $e->getMessage());
    }
}

echo json_encode(['logged_in' => true]);
?>