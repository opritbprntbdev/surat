<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Cabang Numbering Engine dengan Log & Reserve System
 * - Counter terpisah per cabang + jenis (MASUK/KELUAR) + tahun
 * - Auto reset per tahun (YEAR policy)
 * - Format: {URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}
 * - Log semua nomor yang diambil untuk audit trail
 */
class CabangNumbering
{
    /**
     * Reserve nomor baru untuk cabang (status RESERVED)
     * @param int $cabangId
     * @param string $jenisSurat MASUK|KELUAR
     * @param int $userId User yang ambil nomor
     * @param string|null $tanggal Tanggal surat (default hari ini)
     * @return array ['nomor' => string, 'urut' => int, 'log_id' => int]
     */
    public static function reserve(int $cabangId, string $jenisSurat, int $userId, ?string $tanggal = null): array
    {
        $db = Database::getInstance();
        $jenisSurat = strtoupper($jenisSurat);
        if (!in_array($jenisSurat, ['MASUK', 'KELUAR'])) {
            throw new InvalidArgumentException('Jenis surat harus MASUK atau KELUAR');
        }

        $tanggal = $tanggal ?: date('Y-m-d');
        $year = date('Y', strtotime($tanggal));
        $month = date('m', strtotime($tanggal)); // 01-12

        try {
            // Auto-cleanup: cancel nomor RESERVED yang expired (>30 menit)
            $db->query("
                UPDATE log_nomor_surat 
                SET status='CANCELLED', keterangan='Auto-cancelled: expired >30 min'
                WHERE status='RESERVED' 
                  AND tanggal_ambil < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");

            // Lock table untuk atomic increment
            $db->query("LOCK TABLES nomor_surat_cabang_config WRITE, cabang READ, log_nomor_surat WRITE");

            // Ambil/buat config untuk cabang + jenis + tahun
            $stmt = $db->prepare("
                SELECT id, format, last_urut, reset_policy 
                FROM nomor_surat_cabang_config 
                WHERE cabang_id=? AND jenis_surat=? AND tahun=? 
                LIMIT 1
            ");
            $stmt->bind_param('iss', $cabangId, $jenisSurat, $year);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $nextUrut = 0;
            $format = '{URUT}.{BULAN}.KC.{KODE_KANTOR}/BPR-NTB/{TAHUN}';

            if ($row) {
                $nextUrut = ((int)($row['last_urut'] ?? 0)) + 1;
                $format = $row['format'];
                // Update counter
                $upd = $db->prepare("UPDATE nomor_surat_cabang_config SET last_urut=? WHERE id=?");
                $id = (int)$row['id'];
                $upd->bind_param('ii', $nextUrut, $id);
                $upd->execute();
            } else {
                // Buat config baru
                $nextUrut = 1;
                $ins = $db->prepare("
                    INSERT INTO nomor_surat_cabang_config 
                    (cabang_id, jenis_surat, tahun, format, reset_policy, last_urut)
                    VALUES (?, ?, ?, ?, 'YEAR', 1)
                ");
                $ins->bind_param('isss', $cabangId, $jenisSurat, $year, $format);
                $ins->execute();
            }

            // Render nomor sesuai format
            $nomor = self::renderFormat($format, $nextUrut, $month, $year, $cabangId);

            // Insert log (status RESERVED)
            $insLog = $db->prepare("
                INSERT INTO log_nomor_surat 
                (cabang_id, jenis_surat, nomor_surat, nomor_urut, tanggal_ambil, user_id, surat_id, status)
                VALUES (?, ?, ?, ?, NOW(), ?, NULL, 'RESERVED')
            ");
            $insLog->bind_param('issii', $cabangId, $jenisSurat, $nomor, $nextUrut, $userId);
            $insLog->execute();
            $logId = $insLog->insert_id;

            $db->query("UNLOCK TABLES");

            return [
                'nomor' => $nomor,
                'urut' => $nextUrut,
                'log_id' => $logId,
                'jenis' => $jenisSurat
            ];

        } catch (\Throwable $e) {
            @$db->query("UNLOCK TABLES");
            error_log('CabangNumbering::reserve error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tandai nomor sebagai USED (attach ke surat)
     * @param int $logId ID dari log_nomor_surat
     * @param int $suratId ID surat yang menggunakan nomor ini
     */
    public static function markUsed(int $logId, int $suratId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE log_nomor_surat 
            SET status='USED', surat_id=?, keterangan='Nomor digunakan pada surat'
            WHERE id=? AND status='RESERVED'
        ");
        $stmt->bind_param('ii', $suratId, $logId);
        return $stmt->execute();
    }

    /**
     * Cancel nomor RESERVED
     * @param int $logId
     * @param string $reason
     */
    public static function cancel(int $logId, string $reason = 'Dibatalkan oleh user'): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE log_nomor_surat 
            SET status='CANCELLED', keterangan=?
            WHERE id=? AND status='RESERVED'
        ");
        $stmt->bind_param('si', $reason, $logId);
        return $stmt->execute();
    }

    /**
     * Ambil daftar log nomor untuk cabang tertentu
     * @param int $cabangId
     * @param array $filters ['jenis' => 'KELUAR', 'status' => 'USED', 'tanggal_dari' => '2025-11-01', 'tanggal_sampai' => '2025-11-30']
     * @param int $page
     * @param int $pageSize
     * @return array ['data' => [...], 'total' => int]
     */
    public static function getLog(int $cabangId, array $filters = [], int $page = 1, int $pageSize = 50): array
    {
        $db = Database::getInstance();
        $where = " WHERE l.cabang_id = ? ";
        $params = [$cabangId];

        if (!empty($filters['jenis']) && in_array(strtoupper($filters['jenis']), ['MASUK', 'KELUAR'])) {
            $where .= " AND l.jenis_surat = ? ";
            $params[] = strtoupper($filters['jenis']);
        }

        if (!empty($filters['status']) && in_array(strtoupper($filters['status']), ['RESERVED', 'USED', 'CANCELLED'])) {
            $where .= " AND l.status = ? ";
            $params[] = strtoupper($filters['status']);
        }

        if (!empty($filters['tanggal_dari'])) {
            $where .= " AND DATE(l.tanggal_ambil) >= ? ";
            $params[] = $filters['tanggal_dari'];
        }

        if (!empty($filters['tanggal_sampai'])) {
            $where .= " AND DATE(l.tanggal_ambil) <= ? ";
            $params[] = $filters['tanggal_sampai'];
        }

        $offset = ($page - 1) * $pageSize;
        $limitClause = " LIMIT $pageSize OFFSET $offset ";

        $sql = "
            SELECT l.id, l.cabang_id, l.jenis_surat, l.nomor_surat, l.nomor_urut,
                   l.tanggal_ambil, l.user_id, l.surat_id, l.status, l.keterangan,
                   u.nama_lengkap as user_nama,
                   s.perihal as surat_perihal
            FROM log_nomor_surat l
            LEFT JOIN user u ON u.id = l.user_id
            LEFT JOIN surat s ON s.id = l.surat_id
            $where
            ORDER BY l.tanggal_ambil DESC
            $limitClause
        ";

        $data = Database::fetchAll($sql, $params);
        
        $countSql = "SELECT COUNT(*) FROM log_nomor_surat l $where";
        $total = Database::fetchValue($countSql, $params) ?? 0;

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Render format nomor surat dengan placeholder
     * @param string $format Template format
     * @param int $urut Nomor urut
     * @param string $month Bulan 01-12
     * @param string $year Tahun 4 digit
     * @param int $cabangId
     * @return string
     */
    private static function renderFormat(string $format, int $urut, string $month, string $year, int $cabangId): string
    {
        // Ambil kode kantor dari cabang
        $row = Database::fetchOne("SELECT kode_kantor FROM cabang WHERE id=? LIMIT 1", [$cabangId]);
        $kodeKantor = $row['kode_kantor'] ?? '000';

        $replacements = [
            '{URUT}' => str_pad((string)$urut, 3, '0', STR_PAD_LEFT), // 001, 002, ..., 193
            '{BULAN}' => $month, // 01-12
            '{KODE_KANTOR}' => $kodeKantor, // 007
            '{TAHUN}' => $year, // 2025
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
}
?>
