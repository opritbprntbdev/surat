<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../function/log_helper.php';

if (!isset($_SESSION['user_id'])) {
    errorResponse('Akses ditolak.', 401);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        errorResponse('Invalid JSON payload.', 400);
    }
    $suratId = (int)($payload['surat_id'] ?? 0);
    $starred = !empty($payload['starred']);
    if ($suratId <= 0) {
        errorResponse('surat_id wajib', 422);
    }
    $userId = (int)$_SESSION['user_id'];
    $db = Database::getInstance();
    if ($starred) {
        // insert ignore
        $stmt = $db->prepare("INSERT IGNORE INTO surat_star (surat_id, user_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('ii', $suratId, $userId);
        $stmt->execute();
        LogHelper::add($userId, 'STAR_ADD', 'surat_id=' . $suratId);
    } else {
        $stmt = $db->prepare("DELETE FROM surat_star WHERE surat_id=? AND user_id=?");
        $stmt->bind_param('ii', $suratId, $userId);
        $stmt->execute();
        LogHelper::add($userId, 'STAR_REMOVE', 'surat_id=' . $suratId);
    }
    successResponse(['surat_id' => $suratId, 'starred' => $starred]);
} catch (Throwable $e) {
    error_log('Error star.php: ' . $e->getMessage());
    errorResponse('Terjadi kesalahan pada server.', 500);
}
