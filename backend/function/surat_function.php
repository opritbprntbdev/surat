<?php
require_once __DIR__ . '/../config/database.php';

class SuratFunctions
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getSuratList(): array
    {
        // ... (fungsi ini tidak berubah)
        $sql = "
            SELECT 
                s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                pengirim.nama_lengkap as pengirim_nama
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            ORDER BY s.tanggal_surat DESC
            LIMIT 50
        ";
        $suratList = Database::fetchAll($sql);
        $total = Database::fetchValue("SELECT COUNT(*) FROM surat") ?? 0;
        return ['data' => $suratList, 'total' => $total];
    }

    /**
     * BARU: Fungsi untuk mengambil detail satu surat berdasarkan ID.
     */
    public function getSuratById(int $id): ?array
    {
        $sql = "
            SELECT 
                s.*,
                pengirim.nama_lengkap as pengirim_nama,
                pengirim.username as pengirim_username,
                penerima.nama_lengkap as penerima_nama
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            JOIN user penerima ON s.penerima_id = penerima.id
            WHERE s.id = ?
        ";
        return Database::fetchOne($sql, [$id]);
    }
}
?>