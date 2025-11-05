<?php
require_once __DIR__ . '/../config/database.php';

class Numbering
{
    /**
     * Ambil nomor surat berikutnya untuk divisi dan tahun tertentu.
     * Menggunakan tabel nomor_surat_config (prefix, suffix, tahun, last_number).
     * Untuk mencegah race pada MyISAM, gunakan LOCK TABLES saat update last_number.
     * Return: ['nomor' => string, 'urut' => int, 'tahun' => 'YYYY']
     */
    public static function next(int $divisiId, ?string $tanggalSurat = null): array
    {
        $db = Database::getInstance();
        $year = $tanggalSurat ? date('Y', strtotime($tanggalSurat)) : date('Y');
        $next = 0; $prefix = ''; $suffix = '';
        try {
            $db->query("LOCK TABLES nomor_surat_config WRITE");

            // Ambil config
            $stmt = $db->prepare("SELECT id, prefix, suffix, tahun, last_number FROM nomor_surat_config WHERE divisi_id=? AND tahun=? LIMIT 1");
            $stmt->bind_param('is', $divisiId, $year);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row) {
                $next = ((int)($row['last_number'] ?? 0)) + 1;
                $upd = $db->prepare("UPDATE nomor_surat_config SET last_number=? WHERE id=?");
                $id = (int)$row['id'];
                $upd->bind_param('ii', $next, $id);
                $upd->execute();
                $prefix = (string)($row['prefix'] ?? '');
                $suffix = (string)($row['suffix'] ?? '');
            } else {
                // Buat config default jika belum ada
                $next = 1;
                $ins = $db->prepare("INSERT INTO nomor_surat_config (divisi_id, prefix, suffix, tahun, last_number) VALUES (?, '', '', ?, 1)");
                $ins->bind_param('is', $divisiId, $year);
                $ins->execute();
            }
            $db->query("UNLOCK TABLES");
        } catch (\Throwable $e) {
            @ $db->query("UNLOCK TABLES");
            error_log('Numbering::next failed: ' . $e->getMessage());
            // Fallback sederhana bila gagal: gunakan time-based
            $next = (int)date('zHi');
        }

        $nomor = '';
        if ($prefix !== '') { $nomor .= $prefix . '/'; }
        $nomor .= str_pad((string)$next, 3, '0', STR_PAD_LEFT) . '/' . $year;
        if ($suffix !== '') { $nomor .= '/' . $suffix; }

        return ['nomor' => $nomor, 'urut' => $next, 'tahun' => $year];
    }
}
?>
