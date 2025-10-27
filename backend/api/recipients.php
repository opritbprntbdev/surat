<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    errorResponse('Unauthorized', 401);
}

$role = strtoupper($_SESSION['role'] ?? 'CABANG');
$q = trim($_GET['q'] ?? '');

// For CABANG, keep it simple: only UMUM
if ($role === 'CABANG') {
    successResponse([
        ['type' => 'UMUM', 'id' => 'UMUM', 'label' => 'UMUM', 'subtitle' => 'Bagian Umum']
    ]);
}

if ($role !== 'UMUM' && $role !== 'ADMIN') {
    errorResponse('Forbidden', 403);
}

$db = Database::getInstance();
$data = [];

try {
    if ($q !== '') {
        // Search DIVISI, DIREKSI, CABANG from divisi table
        $like = "%" . $q . "%";
        $sqlDiv = "SELECT id, nama_divisi, tipe FROM divisi WHERE aktif='YA' AND nama_divisi LIKE ? AND tipe IN ('DIREKSI','DIVISI','CABANG') ORDER BY FIELD(tipe,'DIREKSI','DIVISI','CABANG'), nama_divisi LIMIT 20";
        $stmt = $db->prepare($sqlDiv);
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                'type' => $row['tipe'],
                'id' => (int)$row['id'],
                'label' => $row['nama_divisi'],
                'subtitle' => $row['tipe']
            ];
        }
        // Also search active users for direct disposisi to user
        $sqlUser = "SELECT id, nama_lengkap FROM user WHERE status='AKTIF' AND nama_lengkap LIKE ? ORDER BY nama_lengkap LIMIT 20";
        $stmtU = $db->prepare($sqlUser);
        $stmtU->bind_param('s', $like);
        $stmtU->execute();
        $resU = $stmtU->get_result();
        while ($u = $resU->fetch_assoc()) {
            $data[] = [
                'type' => 'USER',
                'id' => (int)$u['id'],
                'label' => $u['nama_lengkap'],
                'subtitle' => 'USER'
            ];
        }
    }
    successResponse($data);
} catch (Exception $e) {
    error_log('recipients.php error: ' . $e->getMessage());
    errorResponse('Server error', 500);
}
