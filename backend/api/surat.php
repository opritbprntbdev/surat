<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

if (!isset($_SESSION['user_id'])) {
    errorResponse('Akses ditolak.', 401);
}

try {
    $suratFunctions = new SuratFunctions();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST') {
        // Selalu baca input JSON untuk POST request
        $payload = json_decode(file_get_contents('php://input'), true);

        // Validasi payload
        if (!is_array($payload)) {
            errorResponse('Invalid JSON payload.', 400);
        }

        $perihal = trim($payload['perihal'] ?? '');
        $isi = $payload['isi_surat'] ?? null;

        if ($perihal === '') {
            errorResponse('Perihal wajib diisi', 422);
        }

        $userId = (int)($_SESSION['user_id']);

        $newId = $suratFunctions->createSurat($userId, $perihal, $isi);
        // Menggunakan successResponse yang konsisten dengan frontend
        successResponse(['id' => $newId], 'Surat berhasil dibuat dan dikirim ke UMUM');

    } else if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $surat = $suratFunctions->getSuratById($id);
        if ($surat) {
            successResponse($surat);
        } else {
            errorResponse('Surat tidak ditemukan', 404);
        }
    } else {
        // Default: daftar surat, role-aware (UMUM inbox: MENUNGGU_UMUM; CABANG can use box=sent)
        $box = isset($_GET['box']) ? strtolower($_GET['box']) : null;
        $role = strtoupper($_SESSION['role'] ?? 'CABANG');
        $userId = (int)($_SESSION['user_id']);
        $result = $suratFunctions->getSuratList($box, $role, $userId);
        successResponse($result);
    }

} catch (Exception $e) {
    error_log('Error di surat.php: ' . $e->getMessage());
    errorResponse('Terjadi kesalahan pada server.', 500);
}
?>