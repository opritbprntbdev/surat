<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $mysqli = Database::getInstance();
            // Single user fetch when id provided
            if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
                $id = (int) $_GET['id'];
                $stmt = $mysqli->prepare("SELECT id, username, nama_lengkap, role, created_at FROM user WHERE id = ? AND status = 'AKTIF'");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $res = $stmt->get_result();
                $user = $res->fetch_assoc();
                $stmt->close();

                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'User tidak ditemukan']);
                }
                break;
            }

            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $role = isset($_GET['role']) ? trim($_GET['role']) : '';
            $offset = ($page - 1) * $limit;

            $where = ["status = 'AKTIF'"];
            $whereParams = [];

            if ($search !== '') {
                $where[] = "(username LIKE ? OR nama_lengkap LIKE ?)";
                $like = "%{$search}%";
                $whereParams[] = $like;
                $whereParams[] = $like;
            }

            if ($role !== '') {
                $where[] = "role = ?";
                $whereParams[] = $role;
            }

            $whereClause = implode(' AND ', $where);

            // Count total
            $sqlCount = "SELECT COUNT(*) as total FROM user WHERE $whereClause";
            $stmt = $mysqli->prepare($sqlCount);
            if (!empty($whereParams)) {
                $types = str_repeat('s', count($whereParams));
                $stmt->bind_param($types, ...$whereParams);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total = (int) ($row['total'] ?? 0);
            $stmt->close();

            // Fetch users with pagination (server-side)
            $sql = "SELECT id, username, nama_lengkap, role, created_at
                    FROM user
                    WHERE $whereClause
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $mysqli->prepare($sql);

            $types = '';
            $bindValues = [];
            if (!empty($whereParams)) {
                $types .= str_repeat('s', count($whereParams));
                foreach ($whereParams as $p) { $bindValues[] = $p; }
            }
            $types .= 'ii';
            $bindValues[] = $limit;
            $bindValues[] = $offset;

            $stmt->bind_param($types, ...$bindValues);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $totalPages = (int) ceil(($limit > 0 ? $total / $limit : 0));

            echo json_encode([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_users' => $total,
                        'per_page' => $limit
                    ]
                ]
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            $username = trim($input['username'] ?? '');
            $nama_lengkap = trim($input['nama_lengkap'] ?? '');
            $role = trim($input['role'] ?? '');
            $password = trim($input['password'] ?? '');

            $errors = [];
            if (empty($username))
                $errors['username'] = 'Username wajib diisi';
            if (strlen($username) < 3)
                $errors['username'] = 'Username minimal 3 karakter';
            if (empty($nama_lengkap))
                $errors['nama_lengkap'] = 'Nama lengkap wajib diisi';
            if (empty($role))
                $errors['roleError'] = 'Role wajib dipilih';
            if (empty($password))
                $errors['passwordError'] = 'Password wajib diisi';
            if (strlen($password) < 6)
                $errors['passwordError'] = 'Password minimal 6 karakter';

            $stmt = Database::getInstance()->prepare("SELECT id FROM user WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc())
                $errors['username'] = 'Username sudah digunakan';

            if ($errors) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit;
            }

            $hashedPassword = md5($password);
            $stmt = Database::getInstance()->prepare("INSERT INTO user (username, password, nama_lengkap, role, status) VALUES (?, ?, ?, ?, 'AKTIF')");
            $stmt->bind_param('ssss', $username, $hashedPassword, $nama_lengkap, $role);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'User berhasil ditambahkan']);
            break;

        case 'PUT':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID user tidak valid']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $username = trim($input['username'] ?? '');
            $nama_lengkap = trim($input['nama_lengkap'] ?? '');
            $role = trim($input['role'] ?? '');

            $errors = [];
            if (empty($username))
                $errors['username'] = 'Username wajib diisi';
            if (strlen($username) < 3)
                $errors['username'] = 'Username minimal 3 karakter';
            if (empty($nama_lengkap))
                $errors['nama_lengkap'] = 'Nama lengkap wajib diisi';
            if (empty($role))
                $errors['roleError'] = 'Role wajib dipilih';

            $stmt = Database::getInstance()->prepare("SELECT id FROM user WHERE username = ? AND id != ?");
            $stmt->bind_param('si', $username, $id);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc())
                $errors['username'] = 'Username sudah digunakan';

            if ($errors) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit;
            }

            $stmt = Database::getInstance()->prepare("UPDATE user SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?");
            $stmt->bind_param('sssi', $username, $nama_lengkap, $role, $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'User berhasil diupdate']);
            break;

        case 'DELETE':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID user tidak valid']);
                exit;
            }

            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Tidak bisa menghapus akun sendiri']);
                exit;
            }

            $stmt = Database::getInstance()->prepare("UPDATE user SET status = 'NONAKTIF' WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'User berhasil dinonaktifkan']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Error in user.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Terjadi kesalahan server']);
}
?>