<?php
require_once __DIR__ . '/../config/database.php';

class LogHelper
{
    /**
     * Tambah baris ke tabel log_aktivitas. Abaikan error jika tabel belum ada.
     */
    public static function add(int $userId, string $aksi, ?string $keterangan = null): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO log_aktivitas (user_id, aksi, keterangan, waktu) VALUES (?, ?, ?, NOW())");
            $ket = $keterangan; // bisa null
            $stmt->bind_param('iss', $userId, $aksi, $ket);
            $stmt->execute();
        } catch (\Throwable $e) {
            // Jangan gagalkan alur utama; cukup catat ke error_log
            error_log('LogHelper::add failed: ' . $e->getMessage());
        }
    }
}
?>
