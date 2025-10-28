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
     * Helper: ambil satu user id ber-role UMUM (sebagai inbox pusat UMUM)
     */
    private function getUmumUserId(): ?int
    {
        // Prioritas 1: role persis 'UMUM'
        $row = Database::fetchOne("SELECT id FROM user WHERE UPPER(role)='UMUM' LIMIT 1");
        if ($row && isset($row['id'])) {
            return (int) $row['id'];
        }
        // Prioritas 2: role yang mengandung kata 'UMUM' (misal ADMIN.UMUM)
        $row = Database::fetchOne("SELECT id FROM user WHERE UPPER(role) LIKE '%UMUM%' ORDER BY id ASC LIMIT 1");
        if ($row && isset($row['id'])) {
            return (int) $row['id'];
        }
        return null;
    }

    /**
     * Backfill data surat_penerima untuk surat-surat lama yang belum memiliki routing.
     * Aturan:
     * - Jika surat sudah punya baris di surat_penerima -> skip
     * - Jika penerima_id != 0 -> jadikan penerima_id sebagai AKTIF
     * - Jika penerima_id = 0 dan status MENUNGGU_UMUM -> assign ke UMUM sebagai AKTIF
     * - Selain itu fallback ke UMUM bila tersedia
     * Idempotent: aman dipanggil berulang.
     */
    private function backfillSuratPenerima(): void
    {
        $umumId = $this->getUmumUserId();
        if (!$umumId) {
            return; // tidak bisa backfill tanpa user UMUM
        }

        // Gunakan INSERT...SELECT untuk semua surat yang belum ada di surat_penerima
        $sql = "
            INSERT INTO surat_penerima (surat_id, user_id, tipe_penerima, diterima_at)
            SELECT s.id,
                   CASE 
                     WHEN COALESCE(s.penerima_id, 0) <> 0 THEN s.penerima_id
                     WHEN s.status = 'MENUNGGU_UMUM' THEN %d
                     ELSE %d
                   END AS user_id,
                   'AKTIF' AS tipe_penerima,
                   COALESCE(s.created_at, NOW()) AS diterima_at
            FROM surat s
            WHERE NOT EXISTS (
              SELECT 1 FROM surat_penerima sp WHERE sp.surat_id = s.id
            )
        ";

        $this->db->query(sprintf($sql, $umumId, $umumId));
    }

    public function getSuratList(?string $box = null, ?string $role = null, ?int $userId = null): array
    {
        $role = $role ? strtoupper($role) : null;
        $box = $box ? strtolower($box) : null;

        // Pastikan data lama telah memiliki routing dasar di surat_penerima
        $this->backfillSuratPenerima();

        // Kotak Masuk/Arsip mengacu ke tabel surat_penerima (AKTIF vs ARSIP)
        if ($userId) {
            if ($box === 'sent') {
                // Surat terkirim oleh user ini
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

            if ($box === 'archive') {
                // Arsip untuk user ini (bukan aktif)
                $sql = "
                    SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                           pengirim.nama_lengkap as pengirim_nama
                    FROM surat_penerima sp
                    JOIN surat s ON s.id = sp.surat_id
                    JOIN user pengirim ON s.pengirim_id = pengirim.id
                    WHERE sp.user_id = ? AND sp.tipe_penerima <> 'AKTIF'
                    ORDER BY sp.diterima_at DESC
                    LIMIT 50";
                $suratList = Database::fetchAll($sql, [$userId]);
                $total = Database::fetchValue("SELECT COUNT(*) FROM surat_penerima WHERE user_id=? AND tipe_penerima<>'AKTIF'", [$userId]) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }

            // Default: inbox aktif untuk user ini
            $sql = "
                SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                       pengirim.nama_lengkap as pengirim_nama
                FROM surat_penerima sp
                JOIN surat s ON s.id = sp.surat_id
                JOIN user pengirim ON s.pengirim_id = pengirim.id
                WHERE sp.user_id = ? AND sp.tipe_penerima = 'AKTIF'
                ORDER BY sp.diterima_at DESC
                LIMIT 50";
            $suratList = Database::fetchAll($sql, [$userId]);
            $total = Database::fetchValue("SELECT COUNT(*) FROM surat_penerima WHERE user_id=? AND tipe_penerima='AKTIF'", [$userId]) ?? 0;
            return ['data' => $suratList, 'total' => $total];
        }

        // Fallback umum bila userId tidak tersedia
        $sql = "
            SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                   pengirim.nama_lengkap as pengirim_nama
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            ORDER BY s.tanggal_surat DESC
            LIMIT 50";
        $suratList = Database::fetchAll($sql);
        $total = Database::fetchValue("SELECT COUNT(*) FROM surat") ?? 0;
        return ['data' => $suratList, 'total' => $total];
    }

    /**
     * BARU: Fungsi untuk mengambil detail satu surat berdasarkan ID.
     */
    public function getSuratById(int $id, ?int $currentUserId = null): ?array
    {
        // Pastikan surat lama punya routing minimal
        $this->backfillSuratPenerima();

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
        $surat = Database::fetchOne($sql, [$id]);
        if (!$surat) {
            return null;
        }

        // Ambil daftar disposisi
        $dispos = Database::fetchAll(
            "SELECT d.id, d.user_id, u.nama_lengkap as user_nama, d.disposition_text, d.created_at
             FROM dispositions d JOIN user u ON d.user_id=u.id
             WHERE d.surat_id=? ORDER BY d.created_at ASC",
            [$id]
        );

        // Ambil riwayat penerima (routing)
        $route = Database::fetchAll(
            "SELECT sp.id, sp.user_id, u.nama_lengkap as user_nama, sp.tipe_penerima, sp.diterima_at, sp.ditindak_at
             FROM surat_penerima sp JOIN user u ON sp.user_id=u.id
             WHERE sp.surat_id=? ORDER BY sp.diterima_at ASC",
            [$id]
        );

        $activeForUser = false;
        if ($currentUserId) {
            $row = Database::fetchOne(
                "SELECT id FROM surat_penerima WHERE surat_id=? AND user_id=? AND tipe_penerima='AKTIF' LIMIT 1",
                [$id, $currentUserId]
            );
            $activeForUser = $row ? true : false;
        }

        $surat['dispositions'] = $dispos;
        $surat['routing'] = $route;
        $surat['active_for_user'] = $activeForUser;
        return $surat;
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
        $newId = $stmt->insert_id;

        // Tambahkan rute awal: assign ke UMUM sebagai AKTIF (inbox UMUM)
        $umumId = $this->getUmumUserId();
        if ($umumId) {
            $ins = $this->db->prepare("INSERT INTO surat_penerima (surat_id, user_id, tipe_penerima, diterima_at) VALUES (?, ?, 'AKTIF', NOW())");
            $ins->bind_param('ii', $newId, $umumId);
            $ins->execute();
        }

        return $newId;
    }

    /**
     * Disposisi ke user tertentu: set penerima_id dan ubah status.
     * Mengembalikan true jika berhasil.
     */
    /**
     * Helper: tandai penerima saat ini selesai (pindah ke ARSIP)
     */
    private function closeActiveForUser(int $suratId, int $userId): void
    {
        $upd = $this->db->prepare("UPDATE surat_penerima SET tipe_penerima='ARSIP', ditindak_at=NOW() WHERE surat_id=? AND user_id=? AND tipe_penerima='AKTIF'");
        $upd->bind_param('ii', $suratId, $userId);
        $upd->execute();
    }

    /**
     * Helper: tambah penerima aktif baru
     */
    private function addActiveForUser(int $suratId, int $userId): void
    {
        $ins = $this->db->prepare("INSERT INTO surat_penerima (surat_id, user_id, tipe_penerima, diterima_at) VALUES (?, ?, 'AKTIF', NOW())");
        $ins->bind_param('ii', $suratId, $userId);
        $ins->execute();
    }

    /**
     * Minta disposisi ke target (dipanggil oleh UMUM atau penerima sebelumnya)
     */
    public function requestDisposition(int $suratId, int $targetUserId, int $byUserId, ?string $note = null): bool
    {
        // Arsipkan tugas aktif milik pengaju
        $this->closeActiveForUser($suratId, $byUserId);
        // Tambah tugas aktif untuk target
        $this->addActiveForUser($suratId, $targetUserId);

        // Update status surat generik
        $this->db->query("UPDATE surat SET status='MENUNGGU_DISPOSISI' WHERE id=" . (int) $suratId);

        // Log opsional
        try {
            $line = date('c') . "\treq_dispo\tsurat_id=$suratId\tto_user=$targetUserId\tby=$byUserId\tnote=" . str_replace(["\n", "\t"], ' ', (string) $note) . "\n";
            @file_put_contents(__DIR__ . '/../logs/disposisi.log', $line, FILE_APPEND);
        } catch (\Throwable $e) {
        }

        return true;
    }

    /**
     * Submit teks disposisi oleh penerima lalu kembalikan ke UMUM.
     */
    public function submitDisposition(int $suratId, int $byUserId, string $text): bool
    {
        // Simpan teks disposisi
        $ins = $this->db->prepare("INSERT INTO dispositions (surat_id, user_id, disposition_text, created_at) VALUES (?, ?, ?, NOW())");
        $ins->bind_param('iis', $suratId, $byUserId, $text);
        $ins->execute();

        // Tutup tugas aktif milik penulis
        $this->closeActiveForUser($suratId, $byUserId);

        // Kembalikan ke UMUM
        $umumId = $this->getUmumUserId();
        if ($umumId) {
            $this->addActiveForUser($suratId, $umumId);
        }

        // Update status jadi SIAP_DISEBARKAN
        $this->db->query("UPDATE surat SET status='SIAP_DISEBARKAN' WHERE id=" . (int) $suratId);
        return true;
    }

    /**
     * Distribusi final oleh UMUM ke banyak penerima akhir
     */
    public function finalDistribution(int $suratId, int $byUmumUserId, array $targetUserIds): bool
    {
        // Arsipkan tugas aktif milik UMUM
        $this->closeActiveForUser($suratId, $byUmumUserId);
        // Tambah tugas aktif untuk tiap penerima akhir
        foreach ($targetUserIds as $uid) {
            $this->addActiveForUser($suratId, (int) $uid);
        }
        // Update status surat
        $this->db->query("UPDATE surat SET status='TERDISTRIBUSI' WHERE id=" . (int) $suratId);
        return true;
    }
}
?>