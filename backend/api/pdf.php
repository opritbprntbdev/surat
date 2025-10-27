<?php
// Generate PDF for a surat using mPDF
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/surat_function.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

// Try to load Composer autoload for mPDF
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php', // project root
    __DIR__ . '/../vendor/autoload.php',    // backend/vendor
];
$loaded = false;
foreach ($autoloadPaths as $p) {
    if (file_exists($p)) {
        require_once $p;
        $loaded = true;
        break;
    }
}
if (!$loaded) {
    http_response_code(500);
    echo 'mPDF not installed. Please run: composer require mpdf/mpdf';
    exit;
}

// We'll refer to mPDF classes via fully-qualified names later, to avoid static analysis errors when not installed yet.

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_GET['surat_id'] ?? 0);
if ($id <= 0) {
    http_response_code(422);
    echo 'Parameter id/surat_id wajib';
    exit;
}

$sf = new SuratFunctions();
$surat = $sf->getSuratById($id);
if (!$surat) {
    http_response_code(404);
    echo 'Surat tidak ditemukan';
    exit;
}

$perihal = $surat['perihal'] ?? 'Surat';
$nomor = $surat['nomor_surat'] ?? '';
$tanggal = $surat['tanggal_surat'] ?? date('Y-m-d');
$pengirim = $surat['pengirim_nama'] ?? '';
$isiHtml = $surat['isi_surat'] ?? '';

// Basic A4 stylesheet
$css = <<<CSS
@page { margin: 20mm 15mm; }
body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11pt; line-height: 1.6; color: #202124; }
h1 { font-size: 18pt; margin: 0 0 8pt 0; }
h2 { font-size: 14pt; margin: 0 0 6pt 0; }
p { margin: 0 0 8pt 0; }
table { border-collapse: collapse; }
th, td { padding: 4px 6px; }
table[border] th, table[border] td { border-color: #cfd3d7; }
.meta { font-size: 10pt; color: #555; margin-bottom: 8pt; }
.header { text-align:left; margin-bottom: 6pt; }
.divider { border-top: 1px solid #cfd3d7; margin: 8pt 0; }
img { max-width: 100%; height: auto; }
CSS;

// Wrap isi_surat in a container so default styles apply
$bodyHtml = '<div class="letter-content">' . $isiHtml . '</div>';

if (!class_exists('Mpdf\\Mpdf')) {
    http_response_code(500);
    echo 'mPDF class not found. Please run: composer require mpdf/mpdf';
    exit;
}

$mpdf = new \Mpdf\Mpdf(['format' => 'A4', 'default_font' => 'dejavusans', 'tempDir' => sys_get_temp_dir()]);
$mpdf->SetTitle($perihal);

$mpdf->SetHTMLHeader('
<div style="text-align: center; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
    PT BPR NTB PERSERODA
</div>
');

$mpdf->SetHTMLFooter('
<table width="100%" style="vertical-align: bottom; font-family: serif; font-size: 8pt; color: #000000; font-weight: bold; font-style: italic;">
    <tr>
        <td width="33%"><span style="font-weight: bold; font-style: italic;">{DATE j-m-Y}</span></td>
        <td width="33%" align="center" style="font-weight: bold; font-style: italic;"></td>
        <td width="33%" style="text-align: right; ">{PAGENO}/{nbpg}</td>
    </tr>
</table>
');

// 1 = HEADER_CSS, 2 = HTML_BODY (avoid referencing constants directly)
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($bodyHtml, \Mpdf\HTMLParserMode::HTML_BODY);

$filename = 'Surat-' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $nomor ?: ('ID' . $id)) . '.pdf';
$mpdf->Output($filename, 'I'); // inline preview; change to 'D' to force download
exit;
?>