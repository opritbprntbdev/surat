<?php
session_start();
// Proteksi: jika belum login, tolak akses
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Akses ditolak, silakan login ulang.']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

try {
    $suratFunctions = new SuratFunctions();

    // Hanya handle GET request untuk daftar surat
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 50);

        $result = $suratFunctions->getSuratList([], $page, $limit);

        successResponse($result);
    } else {
        errorResponse('Method not allowed', 405);
    }

} catch (Exception $e) {
    errorResponse('Internal server error: ' . $e->getMessage(), 500);
}
?>