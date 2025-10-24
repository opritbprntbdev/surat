<?php
require_once __DIR__ . '/../config/database.php';

class SuratFunctions
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get list of surat with filtering and pagination
     */
    public function getSuratList(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT 
                s.id,
                s.nomor_surat,
                s.perihal,
                s.tanggal_surat,
                s.status,
                pengirim.nama_lengkap as pengirim_nama,
                penerima.nama_lengkap as penerima_nama,
                SUBSTRING(s.isi_surat, 1, 100) as preview
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            JOIN user penerima ON s.penerima_id = penerima.id
            ORDER BY s.tanggal_surat DESC
            LIMIT ? OFFSET ?
        ";

        $suratList = Database::fetchAll($sql, [$limit, $offset]);

        $total = Database::fetchValue("SELECT COUNT(*) FROM surat") ?? 0;

        return [
            'data' => $suratList,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
}
?>