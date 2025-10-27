<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/pdo_connection.php';
require_once '../helpers/email_helper.php'; // Anda perlu buat helper email jika belum ada

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'request':
            // Request reset password
            $email = $input['email'] ?? '';

            if (empty($email)) {
                throw new Exception('Email wajib diisi');
            }

            // Cek user dengan email tersebut
            $stmt = $conn->prepare("SELECT id, username, nama_lengkap FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Jangan beri tahu email tidak terdaftar (security)
                echo json_encode([
                    'success' => true,
                    'message' => 'Jika email terdaftar, instruksi reset password akan dikirim'
                ]);
                exit;
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Simpan token
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);

            // Kirim email (implementasi sesuaikan dengan mail server Anda)
            $resetLink = "http://localhost/surat/frontend/reset-password.html?token=" . $token;
            $emailBody = "
                <h3>Reset Password</h3>
                <p>Halo {$user['nama_lengkap']},</p>
                <p>Klik link berikut untuk reset password Anda:</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>Link ini akan kadaluarsa dalam 1 jam.</p>
            ";

            // Uncomment jika sudah setup email
            // sendEmail($email, 'Reset Password - Surat App', $emailBody);

            echo json_encode([
                'success' => true,
                'message' => 'Instruksi reset password telah dikirim ke email',
                'token' => $token // Hapus ini di production, hanya untuk testing
            ]);
            break;

        case 'verify':
            // Verify token
            $token = $input['token'] ?? '';

            if (empty($token)) {
                throw new Exception('Token tidak valid');
            }

            $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Token tidak valid atau sudah kadaluarsa');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Token valid'
            ]);
            break;

        case 'reset':
            // Reset password dengan token
            $token = $input['token'] ?? '';
            $newPassword = $input['password'] ?? '';

            if (empty($token) || empty($newPassword)) {
                throw new Exception('Token dan password baru wajib diisi');
            }

            // Validasi password
            if (strlen($newPassword) < 6) {
                throw new Exception('Password minimal 6 karakter');
            }

            // Verify token
            $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Token tidak valid atau sudah kadaluarsa');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Password berhasil direset'
            ]);
            break;

        case 'change':
            // Change password (user sudah login)
            session_start();

            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Unauthorized');
            }

            $oldPassword = $input['old_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (empty($oldPassword) || empty($newPassword)) {
                throw new Exception('Password lama dan baru wajib diisi');
            }

            // Verify old password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!password_verify($oldPassword, $user['password'])) {
                throw new Exception('Password lama tidak sesuai');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ]);
            break;

        case 'change_password':
            // Admin reset password for other user
            session_start();

            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Unauthorized');
            }

            // Check if current user is admin (optional, tergantung requirement)
            // $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            // $stmt->execute([$_SESSION['user_id']]);
            // $currentUser = $stmt->fetch();
            // if ($currentUser['role'] !== 'admin') {
            //     throw new Exception('Hanya admin yang dapat mereset password user lain');
            // }

            $userId = $input['user_id'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (empty($userId) || empty($newPassword)) {
                throw new Exception('User ID dan password baru wajib diisi');
            }

            // Validasi password
            if (strlen($newPassword) < 6) {
                throw new Exception('Password minimal 6 karakter');
            }

            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                throw new Exception('User tidak ditemukan');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Password berhasil direset'
            ]);
            break;

        default:
            throw new Exception('Action tidak valid');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>