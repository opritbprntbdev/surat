<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Username dan password wajib diisi']);
    exit;
}

$db = Database::getInstance();
$stmt = $db->prepare("SELECT id, username, password, nama_lengkap, role, divisi_id, status FROM user WHERE username = ? AND status = 'AKTIF' LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Username tidak ditemukan atau tidak aktif']);
    exit;
}

// Password pakai MD5 seperti di SQL insert awal
if (md5($password) !== $user['password']) {
    echo json_encode(['success' => false, 'error' => 'Password salah']);
    exit;
}

// Login sukses, set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['nama_lengkap'] = $user['nama_lengkap'];
$_SESSION['role'] = $user['role'];
$_SESSION['divisi_id'] = $user['divisi_id'];

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role' => $user['role'],
        'divisi_id' => $user['divisi_id']
    ]
]);