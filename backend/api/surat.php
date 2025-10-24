<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

try {
    $suratFunctions = new SuratFunctions();

    // Cek apakah ada parameter 'id' di URL
    if (isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $surat = $suratFunctions->getSuratById($id);
        if ($surat) {
            successResponse($surat);
        } else {
            errorResponse('Surat tidak ditemukan', 404);
        }
    } else {
        // Jika tidak ada 'id', kembalikan daftar surat
        $result = $suratFunctions->getSuratList();
        successResponse($result);
    }

} catch (Exception $e) {
    error_log('Error di surat.php: ' . $e->getMessage());
    errorResponse('Terjadi kesalahan pada server.', 500);
}
?>