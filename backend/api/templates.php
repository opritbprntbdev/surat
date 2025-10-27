<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();

// Helper function to send JSON response
function json_response($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Check for user session
if (!isset($_SESSION['user_id'])) {
    json_response(401, ['error' => 'Unauthorized']);
}

$conn = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];
$role = strtoupper($_SESSION['role'] ?? 'CABANG');
$is_allowed_to_edit = ($role === 'ADMIN' || $role === 'UMUM');

switch ($method) {
    case 'GET':
        // Get all templates (for everyone) or a single template
        if (isset($_GET['id'])) {
            $stmt = $conn->prepare("SELECT id, nama_template, konten_html, is_active FROM surat_templates WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            json_response(200, ['data' => $result]);
        } else {
            $stmt = $conn->prepare("SELECT id, nama_template, is_active, created_at FROM surat_templates ORDER BY nama_template ASC");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            json_response(200, ['data' => $result]);
        }
        break;

    case 'POST':
        // Create a new template (Admin/Umum only)
        if (!$is_allowed_to_edit) {
            json_response(403, ['error' => 'Forbidden']);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nama_template']) || empty($data['konten_html'])) {
            json_response(400, ['error' => 'Nama template dan konten tidak boleh kosong']);
        }
        
        $stmt = $conn->prepare("INSERT INTO surat_templates (nama_template, konten_html, created_by, updated_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $data['nama_template'], $data['konten_html'], $user_id, $user_id);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            json_response(201, ['message' => 'Template berhasil dibuat', 'id' => $new_id]);
        } else {
            json_response(500, ['error' => 'Gagal menyimpan template']);
        }
        break;

    case 'PUT':
        // Update a template (Admin/Umum only)
        if (!$is_allowed_to_edit || !isset($_GET['id'])) {
            json_response(403, ['error' => 'Forbidden or ID not provided']);
        }
        $id = (int)$_GET['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nama_template']) || empty($data['konten_html'])) {
            json_response(400, ['error' => 'Nama template dan konten tidak boleh kosong']);
        }

        $stmt = $conn->prepare("UPDATE surat_templates SET nama_template = ?, konten_html = ?, updated_by = ? WHERE id = ?");
        $stmt->bind_param("ssii", $data['nama_template'], $data['konten_html'], $user_id, $id);

        if ($stmt->execute()) {
            json_response(200, ['message' => 'Template berhasil diperbarui']);
        } else {
            json_response(500, ['error' => 'Gagal memperbarui template']);
        }
        break;

    case 'DELETE':
        // Delete a template (Admin/Umum only)
        if (!$is_allowed_to_edit || !isset($_GET['id'])) {
            json_response(403, ['error' => 'Forbidden or ID not provided']);
        }
        $id = (int)$_GET['id'];

        $stmt = $conn->prepare("DELETE FROM surat_templates WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            json_response(200, ['message' => 'Template berhasil dihapus']);
        } else {
            json_response(500, ['error' => 'Gagal menghapus template']);
        }
        break;

    default:
        json_response(405, ['error' => 'Method Not Allowed']);
        break;
}
?>