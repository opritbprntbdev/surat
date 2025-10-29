<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    errorResponse('Akses ditolak.', 401);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        errorResponse('Invalid JSON payload.', 400);
    }
    $suratId = (int)($payload['surat_id'] ?? 0);
    if ($suratId <= 0) {
        errorResponse('surat_id wajib', 422);
    }
    $userId = (int)$_SESSION['user_id'];
    $db = Database::getInstance();
    // Insert ignore to mark read
    $stmt = $db->prepare("INSERT IGNORE INTO surat_read (surat_id, user_id, read_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('ii', $suratId, $userId);
    $stmt->execute();
    successResponse(['surat_id' => $suratId, 'read' => true]);
} catch (Throwable $e) {
    error_log('Error read.php: ' . $e->getMessage());
    errorResponse('Terjadi kesalahan pada server.', 500);
}
