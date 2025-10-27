<?php
require_once __DIR__ . '/../config/database.php';

class SuratFunctions
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getSuratList(?string $box = null, ?string $role = null, ?int $userId = null): array
    {
        $role = $role ? strtoupper($role) : null;
        $box = $box ? strtolower($box) : null;

        // Default behavior by role and box
        if ($role === 'UMUM') {
            // UMUM inbox: surat menunggu umum
            $sql = "
                SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                       pengirim.nama_lengkap as pengirim_nama
                FROM surat s
                JOIN user pengirim ON s.pengirim_id = pengirim.id
                WHERE s.status = 'MENUNGGU_UMUM'
                ORDER BY s.tanggal_surat DESC
                LIMIT 50";
            $suratList = Database::fetchAll($sql);
            $total = Database::fetchValue("SELECT COUNT(*) FROM surat WHERE status='MENUNGGU_UMUM'") ?? 0;
            return ['data' => $suratList, 'total' => $total];
        }

        if ($role === 'CABANG' && $userId) {
            if ($box === 'sent') {
                $sql = "
                    SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                           pengirim.nama_lengkap as pengirim_nama
                    FROM surat s
                    JOIN user pengirim ON s.pengirim_id = pengirim.id
                    WHERE s.pengirim_id = ?
                    ORDER BY s.tanggal_surat DESC
                    LIMIT 50";
                $suratList = Database::fetchAll($sql, [$userId]);
                $total = Database::fetchValue("SELECT COUNT(*) FROM surat WHERE pengirim_id = ?", [$userId]) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }
            // Cabang inbox: hanya surat yang ditujukan ke user cabang ini (penerima_id = user)
            $sql = "
                SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                       pengirim.nama_lengkap as pengirim_nama
                FROM surat s
                JOIN user pengirim ON s.pengirim_id = pengirim.id
                WHERE s.penerima_id = ?
                ORDER BY s.tanggal_surat DESC
                LIMIT 50";
            $suratList = Database::fetchAll($sql, [$userId]);
            $total = Database::fetchValue("SELECT COUNT(*) FROM surat WHERE penerima_id = ?", [$userId]) ?? 0;
            return ['data' => $suratList, 'total' => $total];
        }

        // Default fallback: list terbaru (umum)
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
                COALESCE(penerima.nama_lengkap, 'UMUM') as penerima_nama
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            LEFT JOIN user penerima ON s.penerima_id = penerima.id
            WHERE s.id = ?
        ";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Create new surat: default route to UMUM group inbox.
     * @return int Inserted surat id
     */
    public function createSurat(int $pengirimId, string $perihal, ?string $isiSurat = null): int
    {
        if ($perihal === '') {
            throw new InvalidArgumentException('Perihal wajib diisi');
        }

        // strip <script> tags as minimal sanitization
        if ($isiSurat !== null) {
            $isiSurat = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $isiSurat);
        } else {
            $isiSurat = '';
        }

        // Minimal numbering: use temporary draft number; UMUM can renumber later
        $nomorSurat = 'DRAFT-' . date('YmdHis') . '-' . $pengirimId;

        $sql = "INSERT INTO surat (jenis_surat, nomor_surat, nomor_urut, tanggal_surat, perihal, isi_surat, template_id, pengirim_id, penerima_id, status, file_lampiran)
                VALUES ('KELUAR', ?, 0, CURDATE(), ?, ?, NULL, ?, 0, 'MENUNGGU_UMUM', NULL)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssi', $nomorSurat, $perihal, $isiSurat, $pengirimId);
        $stmt->execute();
        return $stmt->insert_id;
    }

    /**
     * Disposisi ke user tertentu: set penerima_id dan ubah status.
     * Mengembalikan true jika berhasil.
     */
    public function disposisiKeUser(int $suratId, int $userTargetId, ?string $note, int $byUserId): bool
    {
        // Pastikan surat ada dan masih di UMUM
        $cek = Database::fetchOne("SELECT id, status FROM surat WHERE id=?", [$suratId]);
        if (!$cek) { return false; }

        // Update surat: set penerima ke user target dan status TERDISPOSISI
        $sql = "UPDATE surat SET penerima_id = ?, status = 'TERDISPOSISI' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userTargetId, $suratId);
        $stmt->execute();

        // Optional: catat log sederhana ke file
        try {
            $line = date('c') . "\tsurat_id=$suratId\tto_user=$userTargetId\tby=$byUserId\tnote=" . str_replace(["\n", "\t"], ' ', (string)$note) . "\n";
            @file_put_contents(__DIR__ . '/../logs/disposisi.log', $line, FILE_APPEND);
        } catch (\Throwable $e) { /* ignore */ }

        return true;
    }
}
?>