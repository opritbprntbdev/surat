<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

if (!isset($_SESSION['user_id'])) {
    errorResponse('Unauthorized', 401);
}

$role = strtoupper($_SESSION['role'] ?? 'CABANG');
if ($role !== 'UMUM' && $role !== 'ADMIN') {
    errorResponse('Forbidden', 403);
}

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $suratId = isset($payload['surat_id']) ? (int) $payload['surat_id'] : 0;
    $userIdTarget = isset($payload['user_id']) ? (int) $payload['user_id'] : 0;
    $note = trim($payload['note'] ?? '');
    if ($suratId <= 0 || $userIdTarget <= 0) {
        errorResponse('surat_id dan user_id wajib diisi', 422);
    }

    $byUserId = (int) $_SESSION['user_id'];
    $sf = new SuratFunctions();
    $ok = $sf->disposisiKeUser($suratId, $userIdTarget, $note, $byUserId);
    if (!$ok) {
        errorResponse('Gagal melakukan disposisi', 400);
    }
    successResponse(['id' => $suratId], 'Disposisi berhasil');
} catch (Exception $e) {
    error_log('disposisi.php error: ' . $e->getMessage());
    errorResponse('Server error', 500);
}
?>