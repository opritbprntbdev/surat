<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/cabang_numbering.php';

// Guard: user must be logged in
if (!isset($_SESSION['user_id'])) {
    errorResponse('Akses ditolak: belum login.', 401);
}

$userId = (int)($_SESSION['user_id']);
$role = strtoupper($_SESSION['role'] ?? 'CABANG');
$cabangId = (int)($_SESSION['cabang_id'] ?? 0);

// Hanya CABANG atau ADMIN yang bisa akses
if (!in_array($role, ['CABANG', 'ADMIN']) || $cabangId <= 0) {
    errorResponse('Akses ditolak: fitur ini khusus untuk Cabang.', 403);
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = strtolower(trim($_GET['action'] ?? ''));

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        switch ($action) {
            case 'reserve':
                // Reserve nomor baru
                $jenis = strtoupper(trim($payload['jenis_surat'] ?? 'KELUAR'));
                if (!in_array($jenis, ['MASUK', 'KELUAR'])) {
                    errorResponse('Jenis surat harus MASUK atau KELUAR', 422);
                }

                $tanggal = $payload['tanggal'] ?? null;
                $result = CabangNumbering::reserve($cabangId, $jenis, $userId, $tanggal);
                successResponse($result, 'Nomor berhasil direserve');
                break;

            case 'cancel':
                // Cancel nomor RESERVED
                $logId = (int)($payload['log_id'] ?? 0);
                if ($logId <= 0) {
                    errorResponse('Parameter log_id wajib diisi', 422);
                }

                $reason = trim($payload['reason'] ?? 'Dibatalkan oleh user');
                $success = CabangNumbering::cancel($logId, $reason);
                if ($success) {
                    successResponse(['log_id' => $logId], 'Nomor berhasil dibatalkan');
                } else {
                    errorResponse('Gagal membatalkan nomor (mungkin sudah dipakai atau tidak ditemukan)', 400);
                }
                break;

            default:
                errorResponse('Action tidak dikenali', 400);
        }

    } else if ($method === 'GET') {
        switch ($action) {
            case 'list':
                // Daftar log nomor untuk cabang ini
                $filters = [];
                if (!empty($_GET['jenis'])) {
                    $filters['jenis'] = $_GET['jenis'];
                }
                if (!empty($_GET['status'])) {
                    $filters['status'] = $_GET['status'];
                }
                if (!empty($_GET['tanggal_dari'])) {
                    $filters['tanggal_dari'] = $_GET['tanggal_dari'];
                }
                if (!empty($_GET['tanggal_sampai'])) {
                    $filters['tanggal_sampai'] = $_GET['tanggal_sampai'];
                }

                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $pageSize = isset($_GET['page_size']) ? max(10, min(100, (int)$_GET['page_size'])) : 50;

                $result = CabangNumbering::getLog($cabangId, $filters, $page, $pageSize);
                successResponse($result);
                break;

            default:
                errorResponse('Action tidak dikenali', 400);
        }

    } else {
        errorResponse('Method tidak didukung', 405);
    }

} catch (Exception $e) {
    error_log('Error di nomor_surat.php: ' . $e->getMessage());
    errorResponse($e->getMessage(), 500);
}
?>
