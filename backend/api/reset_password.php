<?php
// Minimal reset password API using MySQLi Database class and `user` table
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

try {
    if ($action !== 'change_password') {
        throw new Exception('Action tidak valid');
    }

    $userId = isset($input['user_id']) ? (int) $input['user_id'] : 0;
    $newPassword = trim($input['new_password'] ?? '');

    if ($userId <= 0 || $newPassword === '') {
        throw new Exception('User ID dan password baru wajib diisi');
    }
    if (strlen($newPassword) < 6) {
        throw new Exception('Password minimal 6 karakter');
    }

    $mysqli = Database::getInstance();

    // Verify user exists
    $stmt = $mysqli->prepare("SELECT id FROM user WHERE id = ? AND status = 'AKTIF'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    if (!$user) {
        throw new Exception('User tidak ditemukan');
    }

    // Update password (MD5 to match existing schema)
    $hashed = md5($newPassword);
    $stmt = $mysqli->prepare("UPDATE user SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $hashed, $userId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Password berhasil direset']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>