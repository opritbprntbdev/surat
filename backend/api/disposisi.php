<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

// Guard: user must be logged in
if (!isset($_SESSION['user_id'])) {
    errorResponse('Akses ditolak: belum login.', 401);
}

$userId = (int)($_SESSION['user_id']);
$role = strtoupper($_SESSION['role'] ?? 'CABANG');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $suratFn = new SuratFunctions();

    if ($method !== 'POST') {
        errorResponse('Method tidak didukung', 405);
    }

    // Read JSON or form
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { $payload = $_POST; }

    $action = strtolower(trim($payload['action'] ?? ''));
    if ($action === '') {
        $action = strtolower(trim($_GET['action'] ?? ''));
    }

    // Helper: cek apakah user ini penerima AKTIF untuk surat tersebut
    $isActiveAssignee = function(int $suratId, int $uid): bool {
        $row = Database::fetchOne("SELECT id FROM surat_penerima WHERE surat_id=? AND user_id=? AND tipe_penerima='AKTIF' LIMIT 1", [$suratId, $uid]);
        return $row ? true : false;
    };

    switch ($action) {
        case 'request_disposition':
        case 'request': {
            $surat_id = (int)($payload['surat_id'] ?? 0);
            $target_user_id = (int)($payload['target_user_id'] ?? 0);
            $note = $payload['note'] ?? null;

            if ($surat_id <= 0 || $target_user_id <= 0) {
                errorResponse('Parameter tidak lengkap (surat_id, target_user_id).', 422);
            }

            // Hanya UMUM atau assignee aktif yang boleh meneruskan permintaan disposisi
            if (!($role === 'UMUM' || $isActiveAssignee($surat_id, $userId))) {
                errorResponse('Tidak berwenang melakukan aksi ini.', 403);
            }

            $suratFn->requestDisposition($surat_id, $target_user_id, $userId, $note);
            successResponse(['surat_id' => $surat_id, 'to' => $target_user_id], 'Permintaan disposisi telah dikirim.');
            break;
        }

        case 'submit_disposition':
        case 'submit': {
            $surat_id = (int)($payload['surat_id'] ?? 0);
            $text = trim($payload['text'] ?? $payload['disposition_text'] ?? '');

            if ($surat_id <= 0 || $text === '') {
                errorResponse('Parameter tidak lengkap (surat_id, text).', 422);
            }

            // Hanya assignee aktif yang boleh submit disposisi
            if (!$isActiveAssignee($surat_id, $userId)) {
                errorResponse('Anda bukan penerima aktif untuk surat ini.', 403);
            }

            $suratFn->submitDisposition($surat_id, $userId, $text);
            successResponse(['surat_id' => $surat_id], 'Disposisi berhasil disimpan dan dikembalikan ke UMUM.');
            break;
        }

        case 'final_distribution':
        case 'final': {
            $surat_id = (int)($payload['surat_id'] ?? 0);
            $targets = $payload['target_user_ids'] ?? [];
            if ($surat_id <= 0 || !is_array($targets) || count($targets) === 0) {
                errorResponse('Parameter tidak lengkap (surat_id, target_user_ids).', 422);
            }

            if ($role !== 'UMUM') {
                errorResponse('Hanya UMUM yang boleh melakukan distribusi final.', 403);
            }

            if (!$isActiveAssignee($surat_id, $userId)) {
                errorResponse('Surat ini tidak berada pada inbox aktif Anda.', 403);
            }

            // Sanitasi target user ids
            $targetIds = array_values(array_unique(array_map('intval', $targets)));
            $suratFn->finalDistribution($surat_id, $userId, $targetIds);
            successResponse(['surat_id' => $surat_id, 'targets' => $targetIds], 'Surat berhasil didistribusikan.');
            break;
        }

        default:
            errorResponse('Action tidak dikenali.', 400);
    }

} catch (Exception $e) {
    error_log('Error di disposisi.php: ' . $e->getMessage());
    errorResponse('Terjadi kesalahan pada server.', 500);
}