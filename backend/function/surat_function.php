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
                     WHEN s.status = 'MENUNGGU_TINDAKAN_UMUM' THEN %d
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

    public function getSuratList(?string $box = null, ?string $role = null, ?int $userId = null, array $opts = []): array
    {
        $role = $role ? strtoupper($role) : null;
        $box = $box ? strtolower($box) : null;
        $myUnanswered = !empty($opts['my_unanswered']);
        $uid = (int) ($userId ?? 0);
        $facet = strtoupper($opts['facet'] ?? 'ALL');
        $readFilter = strtoupper($opts['read'] ?? 'ALL');
        $q = isset($opts['q']) ? trim((string) $opts['q']) : '';
        $page = isset($opts['page']) ? max(1, (int) $opts['page']) : 1;
        $pageSize = isset($opts['page_size']) ? max(10, min(100, (int) $opts['page_size'])) : 50;
        $offset = ($page - 1) * $pageSize;
        $limitClause = " LIMIT $pageSize OFFSET $offset ";
        // Tracking-specific options
        $tracking = !empty($opts['tracking']);
        $statusFilter = strtoupper($opts['status'] ?? ''); // IN_PROGRESS | DONE
        $sort = strtolower($opts['sort'] ?? ''); // last_activity | created | starred
        $onlyMine = !empty($opts['only_mine']);

        // Pastikan data lama telah memiliki routing dasar di surat_penerima
        $this->backfillSuratPenerima();

        // Kotak Masuk/Arsip mengacu ke tabel surat_penerima (AKTIF vs ARSIP)
        if ($userId) {
            if ($box === 'sent') {
                // Surat terkirim oleh user ini
                $where = " WHERE s.pengirim_id = ? ";
                $params = [$userId];
                if ($facet !== 'ALL') {
                    $where .= " AND UPPER(COALESCE(pengirim.role,'')) = ? ";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'UNREAD')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'STARRED')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($readFilter === 'UNSTARRED')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $where .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?)) ";
                    array_push($params, $like, $like, $like, $like, $like);
                }

                // Optional extra filters for tracking mode
                if ($tracking) {
                    if ($statusFilter === 'IN_PROGRESS') {
                        $where .= " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } elseif ($statusFilter === 'DONE') {
                        $where .= " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    }
                    if ($onlyMine) {
                        $where .= " AND (s.pengirim_id = ? OR EXISTS(SELECT 1 FROM surat_penerima spm WHERE spm.surat_id=s.id AND spm.user_id=?)) ";
                        array_push($params, $userId, $userId);
                    }
                }

                $orderClause = " ORDER BY s.tanggal_surat DESC ";
                if ($tracking && $sort) {
                    if ($sort === 'last_activity')
                        $orderClause = " ORDER BY last_activity_at DESC ";
                    elseif ($sort === 'starred')
                        $orderClause = " ORDER BY starred DESC, last_activity_at DESC ";
                    elseif ($sort === 'created')
                        $orderClause = " ORDER BY s.tanggal_surat DESC ";
                }

                $sql = "
                    SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                           pengirim.nama_lengkap as pengirim_nama,
                           UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                           (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id) AS dispo_count,
                           (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id AND d.user_id = ?) AS my_dispo_count,
                           (SELECT d.disposition_text FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_text,
                           (SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_created_at,
                           (SELECT u.role FROM dispositions d JOIN user u ON u.id=d.user_id WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_user_role,
                           (
                               SELECT COUNT(*) FROM surat_penerima sp2
                               WHERE sp2.surat_id = s.id AND sp2.request_batch = (
                                   SELECT sp3.request_batch FROM surat_penerima sp3
                                   WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                   ORDER BY sp3.diterima_at DESC LIMIT 1
                               )
                           ) AS progress_total,
                           (
                               SELECT COUNT(*) FROM surat_penerima sp2
                               WHERE sp2.surat_id = s.id AND sp2.request_batch = (
                                   SELECT sp3.request_batch FROM surat_penerima sp3
                                   WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                   ORDER BY sp3.diterima_at DESC LIMIT 1
                               ) AND sp2.tipe_penerima <> 'AKTIF'
                           ) AS progress_done,
                           (SELECT COUNT(*) FROM surat_penerima spa WHERE spa.surat_id=s.id AND spa.tipe_penerima='AKTIF') AS current_holder_count,
                           (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') FROM surat_penerima spj JOIN user u ON u.id=spj.user_id WHERE spj.surat_id=s.id AND spj.tipe_penerima='AKTIF') AS current_holders,
                           GREATEST(
                             COALESCE((SELECT MAX(COALESCE(spx.ditindak_at, spx.diterima_at)) FROM surat_penerima spx WHERE spx.surat_id=s.id), '0000-00-00 00:00:00'),
                             COALESCE((SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1),'0000-00-00 00:00:00'),
                             COALESCE(s.tanggal_surat, '0000-00-00 00:00:00')
                           ) AS last_activity_at,
                           EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) AS starred,
                           EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                    FROM surat s
                    JOIN user pengirim ON s.pengirim_id = pengirim.id
                    $where
                    $orderClause
                    $limitClause
                ";
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));

                // Total dengan filter yang sama
                $countSql = "SELECT COUNT(*) FROM surat s JOIN user pengirim ON s.pengirim_id=pengirim.id $where";
                $total = Database::fetchValue($countSql, $params) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }

            if ($box === 'archive') {
                // Arsip untuk user ini (bukan aktif)
                $where = " WHERE sp.user_id = ? AND sp.tipe_penerima <> 'AKTIF' ";
                $params = [$userId];
                if ($facet !== 'ALL') {
                    $where .= " AND UPPER(COALESCE(pengirim.role,'')) = ? ";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'UNREAD')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'STARRED')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($readFilter === 'UNSTARRED')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $where .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?)) ";
                    array_push($params, $like, $like, $like, $like, $like);
                }

                if ($tracking) {
                    if ($statusFilter === 'IN_PROGRESS') {
                        $where .= " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } elseif ($statusFilter === 'DONE') {
                        $where .= " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    }
                }
                $orderClause = " ORDER BY sp.diterima_at DESC ";
                if ($tracking && $sort) {
                    if ($sort === 'last_activity')
                        $orderClause = " ORDER BY last_activity_at DESC ";
                    elseif ($sort === 'starred')
                        $orderClause = " ORDER BY starred DESC, last_activity_at DESC ";
                    elseif ($sort === 'created')
                        $orderClause = " ORDER BY s.tanggal_surat DESC ";
                }
                $sql = "
                                  SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                                      pengirim.nama_lengkap as pengirim_nama,
                                      UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                                                     (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id) AS dispo_count,
                                                     (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id AND d.user_id = ?) AS my_dispo_count,
                                                     (SELECT d.disposition_text FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_text,
                                                     (SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_created_at,
                                                                     (SELECT u.role FROM dispositions d JOIN user u ON u.id=d.user_id WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_user_role,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                     ) AS progress_total,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                             AND sp2.tipe_penerima <> 'AKTIF'
                                                     ) AS progress_done,
                                                     (SELECT COUNT(*) FROM surat_penerima spa WHERE spa.surat_id=s.id AND spa.tipe_penerima='AKTIF') AS current_holder_count,
                                                     (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') FROM surat_penerima spj JOIN user u ON u.id=spj.user_id WHERE spj.surat_id=s.id AND spj.tipe_penerima='AKTIF') AS current_holders,
                                                     GREATEST(
                                                       COALESCE((SELECT MAX(COALESCE(spx.ditindak_at, spx.diterima_at)) FROM surat_penerima spx WHERE spx.surat_id=s.id), '0000-00-00 00:00:00'),
                                                       COALESCE((SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1),'0000-00-00 00:00:00'),
                                                       COALESCE(s.tanggal_surat, '0000-00-00 00:00:00')
                                                     ) AS last_activity_at,
                                                     EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) AS starred,
                                                     EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                                        FROM surat_penerima sp
                                        JOIN surat s ON s.id = sp.surat_id
                                        JOIN user pengirim ON s.pengirim_id = pengirim.id
                                        $where
                                        $orderClause
                                        $limitClause";
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));
                $countSql = "SELECT COUNT(*) FROM surat_penerima sp JOIN surat s ON s.id=sp.surat_id JOIN user pengirim ON s.pengirim_id=pengirim.id $where";
                $total = Database::fetchValue($countSql, $params) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }

            if ($box === 'starred') {
                // Daftar surat berbintang untuk user ini (semua status/kotak)
                $where = " WHERE ss.user_id = ? ";
                $params = [$userId];
                if ($facet !== 'ALL') {
                    $where .= " AND UPPER(COALESCE(pengirim.role,'')) = ? ";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'UNREAD')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'UNSTARRED')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss2 WHERE ss2.surat_id=s.id AND ss2.user_id={$uid}) ";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $where .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?)) ";
                    array_push($params, $like, $like, $like, $like, $like);
                }

                if ($tracking) {
                    if ($statusFilter === 'IN_PROGRESS') {
                        $where .= " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } elseif ($statusFilter === 'DONE') {
                        $where .= " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    }
                    if ($onlyMine) {
                        $where .= " AND (s.pengirim_id = ? OR EXISTS(SELECT 1 FROM surat_penerima spm WHERE spm.surat_id=s.id AND spm.user_id=?)) ";
                        array_push($params, $userId, $userId);
                    }
                }
                $orderClause = " ORDER BY s.tanggal_surat DESC ";
                if ($tracking && $sort) {
                    if ($sort === 'last_activity')
                        $orderClause = " ORDER BY last_activity_at DESC ";
                    elseif ($sort === 'starred')
                        $orderClause = " ORDER BY starred DESC, last_activity_at DESC ";
                    elseif ($sort === 'created')
                        $orderClause = " ORDER BY s.tanggal_surat DESC ";
                }
                $sql = "
                                  SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                                      pengirim.nama_lengkap as pengirim_nama,
                                      UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                                                     (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id) AS dispo_count,
                                                     (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id AND d.user_id = ?) AS my_dispo_count,
                                                     (SELECT d.disposition_text FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_text,
                                                     (SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_created_at,
                                                     (SELECT u.role FROM dispositions d JOIN user u ON u.id=d.user_id WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_user_role,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                     ) AS progress_total,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                             AND sp2.tipe_penerima <> 'AKTIF'
                                                     ) AS progress_done,
                                                     (SELECT COUNT(*) FROM surat_penerima spa WHERE spa.surat_id=s.id AND spa.tipe_penerima='AKTIF') AS current_holder_count,
                                                     (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') FROM surat_penerima spj JOIN user u ON u.id=spj.user_id WHERE spj.surat_id=s.id AND spj.tipe_penerima='AKTIF') AS current_holders,
                                                     GREATEST(
                                                       COALESCE((SELECT MAX(COALESCE(spx.ditindak_at, spx.diterima_at)) FROM surat_penerima spx WHERE spx.surat_id=s.id), '0000-00-00 00:00:00'),
                                                       COALESCE((SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1),'0000-00-00 00:00:00'),
                                                       COALESCE(s.tanggal_surat, '0000-00-00 00:00:00')
                                                     ) AS last_activity_at,
                                                     1 AS starred,
                                                     EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                                        FROM surat_star ss
                                        JOIN surat s ON s.id = ss.surat_id
                                        JOIN user pengirim ON s.pengirim_id = pengirim.id
                                        $where
                                        $orderClause
                                        $limitClause";
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));
                $countSql = "SELECT COUNT(*) FROM surat_star ss JOIN surat s ON s.id=ss.surat_id JOIN user pengirim ON s.pengirim_id=pengirim.id $where";
                $total = Database::fetchValue($countSql, $params) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }

            if ($box === 'my_disposisi' || $box === 'my_dispo' || $box === 'my') {
                // Daftar semua jawaban disposisi milik user ini
                $where = " WHERE d.user_id = ? ";
                $params = [$userId];
                if ($facet !== 'ALL') {
                    $where .= " AND UPPER(COALESCE(pengirim.role,'')) = ? ";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'UNREAD')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) ";
                if ($readFilter === 'STARRED')
                    $where .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($readFilter === 'UNSTARRED')
                    $where .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) ";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $where .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR d.disposition_text LIKE ?) ";
                    array_push($params, $like, $like, $like, $like, $like);
                }

                if ($tracking) {
                    if ($statusFilter === 'IN_PROGRESS') {
                        $where .= " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } elseif ($statusFilter === 'DONE') {
                        $where .= " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    }
                }
                $orderClause = " ORDER BY d.created_at DESC ";
                if ($tracking && $sort) {
                    if ($sort === 'last_activity')
                        $orderClause = " ORDER BY last_activity_at DESC ";
                    elseif ($sort === 'starred')
                        $orderClause = " ORDER BY starred DESC, last_activity_at DESC ";
                    elseif ($sort === 'created')
                        $orderClause = " ORDER BY s.tanggal_surat DESC ";
                }
                $sql = "
                                  SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                                      pengirim.nama_lengkap as pengirim_nama,
                                      UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                                                     d.disposition_text AS last_dispo_text,
                                                     d.created_at AS last_dispo_created_at,
                                                     (SELECT COUNT(*) FROM dispositions d2 WHERE d2.surat_id = s.id) AS dispo_count,
                                                     (SELECT COUNT(*) FROM dispositions d3 WHERE d3.surat_id = s.id AND d3.user_id = ?) AS my_dispo_count,
                                                                     (SELECT u.role FROM dispositions dd JOIN user u ON u.id=dd.user_id WHERE dd.surat_id = s.id ORDER BY dd.created_at DESC LIMIT 1) AS last_dispo_user_role,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                     ) AS progress_total,
                                                     (
                                                         SELECT COUNT(*) FROM surat_penerima sp2
                                                         WHERE sp2.surat_id = s.id
                                                             AND sp2.request_batch = (
                                                                 SELECT sp3.request_batch FROM surat_penerima sp3
                                                                 WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                                 ORDER BY sp3.diterima_at DESC LIMIT 1
                                                             )
                                                             AND sp2.tipe_penerima <> 'AKTIF'
                                                     ) AS progress_done,
                                                     (SELECT COUNT(*) FROM surat_penerima spa WHERE spa.surat_id=s.id AND spa.tipe_penerima='AKTIF') AS current_holder_count,
                                                     (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') FROM surat_penerima spj JOIN user u ON u.id=spj.user_id WHERE spj.surat_id=s.id AND spj.tipe_penerima='AKTIF') AS current_holders,
                                                     GREATEST(
                                                       COALESCE((SELECT MAX(COALESCE(spx.ditindak_at, spx.diterima_at)) FROM surat_penerima spx WHERE spx.surat_id=s.id), '0000-00-00 00:00:00'),
                                                       COALESCE((SELECT d4.created_at FROM dispositions d4 WHERE d4.surat_id = s.id ORDER BY d4.created_at DESC LIMIT 1),'0000-00-00 00:00:00'),
                                                       COALESCE(s.tanggal_surat, '0000-00-00 00:00:00')
                                                     ) AS last_activity_at,
                                                                     EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) AS starred,
                                                                     EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                                        FROM dispositions d
                                        JOIN surat s ON s.id = d.surat_id
                                        JOIN user pengirim ON s.pengirim_id = pengirim.id
                                        $where
                                        $orderClause
                                        $limitClause";
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));
                $countSql = "SELECT COUNT(*) FROM dispositions d JOIN surat s ON s.id=d.surat_id JOIN user pengirim ON s.pengirim_id=pengirim.id $where";
                $total = Database::fetchValue($countSql, $params) ?? 0;
                return ['data' => $suratList, 'total' => $total];
            }

            // Default: inbox aktif untuk user ini
            // Jika filter 'belum dijawab' diaktifkan, gunakan NOT EXISTS untuk menyaring
            if ($myUnanswered) {
                $sql = "
                          SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                              pengirim.nama_lengkap as pengirim_nama,
                              UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                                             (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id) AS dispo_count,
                                             (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id AND d.user_id = ?) AS my_dispo_count,
                                             (SELECT d.disposition_text FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_text,
                                             (SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_created_at,
                                             (SELECT u.role FROM dispositions d JOIN user u ON u.id=d.user_id WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_user_role,
                                             (
                                                 SELECT COUNT(*) FROM surat_penerima sp2
                                                 WHERE sp2.surat_id = s.id
                                                     AND sp2.request_batch = (
                                                         SELECT sp3.request_batch FROM surat_penerima sp3
                                                         WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                         ORDER BY sp3.diterima_at DESC LIMIT 1
                                                     )
                                             ) AS progress_total,
                                             (
                                                 SELECT COUNT(*) FROM surat_penerima sp2
                                                 WHERE sp2.surat_id = s.id
                                                     AND sp2.request_batch = (
                                                         SELECT sp3.request_batch FROM surat_penerima sp3
                                                         WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                         ORDER BY sp3.diterima_at DESC LIMIT 1
                                                     )
                                                     AND sp2.tipe_penerima <> 'AKTIF'
                                            ) AS progress_done,
                                            EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) AS starred,
                                            EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                                FROM surat_penerima sp
                                JOIN surat s ON s.id = sp.surat_id
                                JOIN user pengirim ON s.pengirim_id = pengirim.id
                                WHERE sp.user_id = ? AND sp.tipe_penerima = 'AKTIF'
                                    AND NOT EXISTS (
                                        SELECT 1 FROM dispositions d2 WHERE d2.surat_id = s.id AND d2.user_id = ?
                                    )";
                $params = [$userId, $userId];
                if ($facet !== 'ALL') {
                    $sql .= " AND UPPER(COALESCE(pengirim.role,'')) = ?";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $sql .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
                if ($readFilter === 'UNREAD')
                    $sql .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
                if ($readFilter === 'STARRED')
                    $sql .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
                if ($readFilter === 'UNSTARRED')
                    $sql .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $sql .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?))";
                    array_push($params, $like, $like, $like, $like, $like);
                }
                $sql .= " ORDER BY sp.diterima_at DESC $limitClause";
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));
            } else {
                if ($tracking) {
                    if ($statusFilter === 'IN_PROGRESS') {
                        // always true for inbox but harmless
                        $sqlStatusExtra = " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } elseif ($statusFilter === 'DONE') {
                        $sqlStatusExtra = " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF') ";
                    } else {
                        $sqlStatusExtra = '';
                    }
                } else {
                    $sqlStatusExtra = '';
                }
                $sql = "
                                SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
                                             pengirim.nama_lengkap as pengirim_nama,
                                             UPPER(COALESCE(pengirim.role,'')) AS pengirim_role,
                                             (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id) AS dispo_count,
                                             (SELECT COUNT(*) FROM dispositions d WHERE d.surat_id = s.id AND d.user_id = ?) AS my_dispo_count,
                                             (SELECT d.disposition_text FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_text,
                                             (SELECT d.created_at FROM dispositions d WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_created_at,
                                                             (SELECT u.role FROM dispositions d JOIN user u ON u.id=d.user_id WHERE d.surat_id = s.id ORDER BY d.created_at DESC LIMIT 1) AS last_dispo_user_role,
                                             (
                                                 SELECT COUNT(*) FROM surat_penerima sp2
                                                 WHERE sp2.surat_id = s.id
                                                     AND sp2.request_batch = (
                                                         SELECT sp3.request_batch FROM surat_penerima sp3
                                                         WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                         ORDER BY sp3.diterima_at DESC LIMIT 1
                                                     )
                                             ) AS progress_total,
                                             (
                                                 SELECT COUNT(*) FROM surat_penerima sp2
                                                 WHERE sp2.surat_id = s.id
                                                     AND sp2.request_batch = (
                                                         SELECT sp3.request_batch FROM surat_penerima sp3
                                                         WHERE sp3.surat_id = s.id AND sp3.request_batch IS NOT NULL
                                                         ORDER BY sp3.diterima_at DESC LIMIT 1
                                                     )
                                                     AND sp2.tipe_penerima <> 'AKTIF'
                                                             ) AS progress_done,
                                             (SELECT COUNT(*) FROM surat_penerima spa WHERE spa.surat_id=s.id AND spa.tipe_penerima='AKTIF') AS current_holder_count,
                                             (SELECT GROUP_CONCAT(u.nama_lengkap SEPARATOR ', ') FROM surat_penerima spj JOIN user u ON u.id=spj.user_id WHERE spj.surat_id=s.id AND spj.tipe_penerima='AKTIF') AS current_holders,
                                             GREATEST(
                                               COALESCE((SELECT MAX(COALESCE(spx.ditindak_at, spx.diterima_at)) FROM surat_penerima spx WHERE spx.surat_id=s.id), '0000-00-00 00:00:00'),
                                               COALESCE((SELECT d4.created_at FROM dispositions d4 WHERE d4.surat_id = s.id ORDER BY d4.created_at DESC LIMIT 1),'0000-00-00 00:00:00'),
                                               COALESCE(s.tanggal_surat, '0000-00-00 00:00:00')
                                             ) AS last_activity_at,
                                                             EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid}) AS starred,
                                                             EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid}) AS is_read
                                FROM surat_penerima sp
                                JOIN surat s ON s.id = sp.surat_id
                                JOIN user pengirim ON s.pengirim_id = pengirim.id
                                WHERE sp.user_id = ? AND sp.tipe_penerima = 'AKTIF'" . $sqlStatusExtra;
                $params = [$userId];
                if ($facet !== 'ALL') {
                    $sql .= " AND UPPER(COALESCE(pengirim.role,'')) = ?";
                    $params[] = $facet;
                }
                if ($readFilter === 'READ')
                    $sql .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
                if ($readFilter === 'UNREAD')
                    $sql .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
                if ($readFilter === 'STARRED')
                    $sql .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
                if ($readFilter === 'UNSTARRED')
                    $sql .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
                if ($q !== '') {
                    $like = '%' . $this->db->real_escape_string($q) . '%';
                    $sql .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?))";
                    array_push($params, $like, $like, $like, $like, $like);
                }
                $orderClause = " ORDER BY sp.diterima_at DESC ";
                if ($tracking && $sort) {
                    if ($sort === 'last_activity')
                        $orderClause = " ORDER BY last_activity_at DESC ";
                    elseif ($sort === 'starred')
                        $orderClause = " ORDER BY starred DESC, last_activity_at DESC ";
                    elseif ($sort === 'created')
                        $orderClause = " ORDER BY s.tanggal_surat DESC ";
                }
                $sql .= $orderClause . $limitClause;
                $suratList = Database::fetchAll($sql, array_merge([$userId], $params));
            }
            // Hitung total berdasarkan filter yang sama (tanpa LIMIT)
            $countSql = "SELECT COUNT(*) FROM surat_penerima sp JOIN surat s ON s.id=sp.surat_id JOIN user pengirim ON s.pengirim_id=pengirim.id WHERE sp.user_id=? AND sp.tipe_penerima='AKTIF'";
            $countParams = [$userId];
            if ($myUnanswered) {
                $countSql .= " AND NOT EXISTS (SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.user_id=?)";
                $countParams[] = $userId;
            }
            if ($tracking) {
                if ($statusFilter === 'IN_PROGRESS') {
                    $countSql .= " AND EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF')";
                } elseif ($statusFilter === 'DONE') {
                    $countSql .= " AND NOT EXISTS(SELECT 1 FROM surat_penerima spx WHERE spx.surat_id=s.id AND spx.tipe_penerima='AKTIF')";
                }
            }
            if ($facet !== 'ALL') {
                $countSql .= " AND UPPER(COALESCE(pengirim.role,'')) = ?";
                $countParams[] = $facet;
            }
            if ($readFilter === 'READ')
                $countSql .= " AND EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
            if ($readFilter === 'UNREAD')
                $countSql .= " AND NOT EXISTS(SELECT 1 FROM surat_read sr WHERE sr.surat_id=s.id AND sr.user_id={$uid})";
            if ($readFilter === 'STARRED')
                $countSql .= " AND EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
            if ($readFilter === 'UNSTARRED')
                $countSql .= " AND NOT EXISTS(SELECT 1 FROM surat_star ss WHERE ss.surat_id=s.id AND ss.user_id={$uid})";
            if ($q !== '') {
                $like = '%' . $this->db->real_escape_string($q) . '%';
                $countSql .= " AND (s.perihal LIKE ? OR s.nomor_surat LIKE ? OR s.status LIKE ? OR pengirim.nama_lengkap LIKE ? OR EXISTS(SELECT 1 FROM dispositions d2 WHERE d2.surat_id=s.id AND d2.disposition_text LIKE ?))";
                array_push($countParams, $like, $like, $like, $like, $like);
            }
            $total = Database::fetchValue($countSql, $countParams) ?? 0;
            return ['data' => $suratList, 'total' => $total];
        }

        // Fallback umum bila userId tidak tersedia
        $sql = "
            SELECT s.id, s.nomor_surat, s.perihal, s.tanggal_surat, s.status,
             pengirim.nama_lengkap as pengirim_nama,
             UPPER(COALESCE(pengirim.role,'')) AS pengirim_role
            FROM surat s
            JOIN user pengirim ON s.pengirim_id = pengirim.id
            ORDER BY s.tanggal_surat DESC
            LIMIT 50";
        $suratList = Database::fetchAll($sql);
        $total = Database::fetchValue("SELECT COUNT(*) FROM surat") ?? 0;
        return ['data' => $suratList, 'total' => $total];
    }

    /**
     * Hitung jumlah surat AKTIF (inbox) yang belum dibaca oleh user tertentu.
     * "Belum dibaca" diartikan sebagai tidak adanya baris pada surat_read untuk (surat_id,user_id).
     */
    public function getUnreadCountForUser(int $userId): int
    {
        $sql = "
                        SELECT COUNT(*) AS cnt
                        FROM surat_penerima sp
                        JOIN surat s ON s.id = sp.surat_id
                        WHERE sp.user_id = ?
                            AND sp.tipe_penerima = 'AKTIF'
                            AND NOT EXISTS (
                                SELECT 1 FROM surat_read sr
                                WHERE sr.surat_id = s.id AND sr.user_id = ?
                            )
                ";
        $row = Database::fetchOne($sql, [$userId, $userId]);
        return (int) ($row['cnt'] ?? 0);
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

        // Hitung progres terakhir (berdasarkan batch request terbaru jika ada)
        try {
            $latest = Database::fetchOne(
                "SELECT request_batch FROM surat_penerima WHERE surat_id=? AND request_batch IS NOT NULL ORDER BY diterima_at DESC LIMIT 1",
                [$id]
            );
            if ($latest && !empty($latest['request_batch'])) {
                $batch = $latest['request_batch'];
                $targets = Database::fetchAll(
                    "SELECT sp.user_id, u.nama_lengkap AS user_nama, sp.tipe_penerima, sp.diterima_at, sp.ditindak_at
                     FROM surat_penerima sp JOIN user u ON u.id=sp.user_id
                     WHERE sp.surat_id=? AND sp.request_batch=?
                     ORDER BY sp.diterima_at ASC",
                    [$id, $batch]
                );
                $total = count($targets);
                $done = 0;
                $detail = [];
                foreach ($targets as $t) {
                    $isDone = ($t['tipe_penerima'] !== 'AKTIF');
                    if ($isDone) {
                        $done++;
                    }
                    $detail[] = [
                        'user_id' => (int) $t['user_id'],
                        'user_nama' => $t['user_nama'],
                        'status' => $isDone ? 'SELESAI' : 'MENUNGGU',
                        'diterima_at' => $t['diterima_at'],
                        'ditindak_at' => $t['ditindak_at']
                    ];
                }
                $surat['progress'] = [
                    'batch' => $batch,
                    'total' => $total,
                    'done' => $done,
                    'remaining' => max(0, $total - $done),
                    'targets' => $detail
                ];
            }
        } catch (\Throwable $e) {
            // Abaikan jika kolom belum ada
        }
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
                VALUES ('KELUAR', ?, 0, CURDATE(), ?, ?, NULL, ?, 0, 'MENUNGGU_TINDAKAN_UMUM', NULL)";
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
        // Hindari duplikasi penerima AKTIF untuk surat yang sama
        $chk = $this->db->prepare("SELECT id FROM surat_penerima WHERE surat_id=? AND user_id=? AND tipe_penerima='AKTIF' LIMIT 1");
        $chk->bind_param('ii', $suratId, $userId);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        if ($exists) {
            return;
        }

        $ins = $this->db->prepare("INSERT INTO surat_penerima (surat_id, user_id, tipe_penerima, diterima_at) VALUES (?, ?, 'AKTIF', NOW())");
        $ins->bind_param('ii', $suratId, $userId);
        $ins->execute();
    }

    /**
     * Helper: tambah penerima aktif baru dengan batch (untuk tracking progres request paralel)
     */
    private function addActiveForUserWithBatch(int $suratId, int $userId, ?string $batch): void
    {
        // Hindari duplikasi penerima AKTIF untuk surat yang sama
        $chk = $this->db->prepare("SELECT id FROM surat_penerima WHERE surat_id=? AND user_id=? AND tipe_penerima='AKTIF' LIMIT 1");
        $chk->bind_param('ii', $suratId, $userId);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        if ($exists) {
            return;
        }

        if ($batch) {
            // Jika kolom request_batch tersedia, set nilainya; fallback ke insert biasa jika kolom belum ada
            try {
                $ins = $this->db->prepare("INSERT INTO surat_penerima (surat_id, user_id, tipe_penerima, diterima_at, request_batch) VALUES (?, ?, 'AKTIF', NOW(), ?)");
                $ins->bind_param('iis', $suratId, $userId, $batch);
                $ins->execute();
                return;
            } catch (\Throwable $e) {
                // Kolom mungkin belum dimigrasi; lanjutkan tanpa batch
            }
        }

        $this->addActiveForUser($suratId, $userId);
    }

    /**
     * Minta disposisi ke target (dipanggil oleh UMUM atau penerima sebelumnya)
     */
    public function requestDisposition(int $suratId, int $targetUserId, int $byUserId, ?string $note = null): bool
    {
        // Arsipkan tugas aktif milik pengaju
        $this->closeActiveForUser($suratId, $byUserId);
        // Tambah tugas aktif untuk target
        $batch = substr(bin2hex(random_bytes(8)), 0, 16);
        $this->addActiveForUserWithBatch($suratId, $targetUserId, $batch);

        // Update status surat: gunakan format final MENUNGGU_DISPOSISI_<ROLE>
        try {
            $row = Database::fetchOne("SELECT UPPER(COALESCE(role,'')) AS role_u FROM user WHERE id=? LIMIT 1", [$targetUserId]);
            $role = $row['role_u'] ?? '';
            $roleNormalized = preg_replace('/[^A-Z0-9]+/', '_', $role);
            $status = 'MENUNGGU_DISPOSISI' . ($roleNormalized ? ('_' . $roleNormalized) : '');

            $stmt = $this->db->prepare("UPDATE surat SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $suratId);
            $stmt->execute();
        } catch (\Throwable $e) {
            // Jangan gagalkan alur hanya karena update status, cukup catat log
            error_log('requestDisposition: gagal update status surat_id=' . $suratId . ' => ' . $e->getMessage());
        }

        // Log opsional
        try {
            $line = date('c') . "\treq_dispo\tsurat_id=$suratId\tto_user=$targetUserId\tby=$byUserId\tnote=" . str_replace(["\n", "\t"], ' ', (string) $note) . "\n";
            @file_put_contents(__DIR__ . '/../logs/disposisi.log', $line, FILE_APPEND);
        } catch (\Throwable $e) {
        }

        return true;
    }

    /**
     * Minta disposisi ke banyak target sekaligus (paralel)
     */
    public function requestDispositionMulti(int $suratId, array $targetUserIds, int $byUserId, ?string $note = null): bool
    {
        // Sanitasi dan unik
        $ids = array_values(array_unique(array_map('intval', $targetUserIds)));
        if (count($ids) === 0) {
            return false;
        }

        // Arsipkan tugas aktif milik pengaju (sekali saja)
        $this->closeActiveForUser($suratId, $byUserId);

        // Tambah tugas aktif untuk semua target (dengan batch yang sama)
        $batch = substr(bin2hex(random_bytes(8)), 0, 16);
        foreach ($ids as $uid) {
            if ($uid <= 0)
                continue;
            $this->addActiveForUserWithBatch($suratId, $uid, $batch);
        }

        // Update status: jika semua target memiliki role yang sama, gunakan MENUNGGU_DISPOSISI_<ROLE>,
        // jika beragam/multi-role maka gunakan MENUNGGU_DISPOSISI (umum)
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = $ids;
            $rows = Database::fetchAll("SELECT DISTINCT UPPER(COALESCE(role,'')) AS role_u FROM user WHERE id IN ($placeholders)", $params);
            $roles = array_values(array_filter(array_map(fn($r) => $r['role_u'] ?? '', $rows)));
            $status = 'MENUNGGU_DISPOSISI';
            if (count($roles) === 1) {
                $roleNormalized = preg_replace('/[^A-Z0-9]+/', '_', $roles[0]);
                if ($roleNormalized !== '') {
                    $status .= '_' . $roleNormalized;
                }
            }
            $stmt = $this->db->prepare("UPDATE surat SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $suratId);
            $stmt->execute();
        } catch (\Throwable $e) {
            error_log('requestDispositionMulti: gagal update status surat_id=' . $suratId . ' => ' . $e->getMessage());
        }

        // Log opsional
        try {
            $line = date('c') . "\treq_dispo_multi\tsurat_id=$suratId\tto_users=" . implode(',', $ids) . "\tby=$byUserId\tnote=" . str_replace(["\n", "\t"], ' ', (string) $note) . "\n";
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
        // Catatan: Pada database lama kolom `status` bisa berupa ENUM lama sehingga update bisa gagal.
        // Agar tidak menyebabkan 500, tangkap error dan log saja; migrasi schema tetap direkomendasikan.
        try {
            $this->db->query("UPDATE surat SET status='SIAP_DISEBARKAN' WHERE id=" . (int) $suratId);
        } catch (\Throwable $e) {
            error_log('submitDisposition: gagal update status surat_id=' . $suratId . ' => ' . $e->getMessage());
        }
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
        // Update status surat akhir
        try {
            $this->db->query("UPDATE surat SET status='SELESAI' WHERE id=" . (int) $suratId);
        } catch (\Throwable $e) {
            error_log('finalDistribution: gagal update status surat_id=' . $suratId . ' => ' . $e->getMessage());
        }
        return true;
    }
}
?>