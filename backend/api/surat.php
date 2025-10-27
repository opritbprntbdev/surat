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
        // Create minimal surat (Cabang → UMUM)
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST; // support form-encoded fallback
        }

        $perihal = trim($payload['perihal'] ?? $payload['judul'] ?? '');
        $isi = $payload['isi_surat'] ?? $payload['html_content'] ?? null;

        if ($perihal === '') {
            errorResponse('Perihal/Judul wajib diisi', 422);
        }

        $userId = (int)($_SESSION['user_id']);
        // divisi_id tidak diperlukan untuk insert berdasarkan skema baru

        $newId = $suratFunctions->createSurat($userId, $perihal, $isi);
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