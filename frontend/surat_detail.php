<?php
$page_title = "Detail Surat";
require_once __DIR__ . '/../backend/config/config.php';
include 'layouts/header.php';
include 'layouts/sidebar.php';

$surat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
?>

<style>
    .surat-meta {
        font-size: 0.9em;
        color: #6c757d;
        margin-top: 1rem;
    }
    .surat-meta p {
        margin: 0.25rem 0;
    }
    .surat-content {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #dee2e6;
    }
    .status-badge {
        display: inline-block;
        padding: .25em .6em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: .25rem;
        color: #fff;
    }
    .status-menunggu_persetujuan { background-color: #ffc107; color: #000; }
    .status-diteruskan { background-color: #17a2b8; }
    .status-selesai { background-color: #28a745; }
    .status-ditolak { background-color: #dc3545; }
</style>

<main class="main-content">
    <header class="header">
        <div class="header-left">
             <a href="javascript:history.back()" class="btn" title="Kembali">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
        </div>
        <div class="header-center">
            <h1 style="font-size:16px; font-weight:600;">Detail Surat</h1>
        </div>
        <div class="header-right"></div>
    </header>

    <div class="page-content">
        <div id="surat-detail-container" class="card">
            <div class="card-body">
                <p>Memuat detail surat...</p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const suratId = <?php echo $surat_id; ?>;
    const container = document.getElementById('surat-detail-container');

    if (suratId <= 0) {
        container.innerHTML = '<div class="card-body"><p class="error-message">ID surat tidak valid.</p></div>';
        return;
    }

    fetch(`../backend/api/surat.php?id=${suratId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Surat tidak ditemukan (HTTP ${response.status})`);
            }
            return response.json();
        })
        .then(result => {
            if (result.success && result.data) {
                const surat = result.data;
                const formattedDate = new Date(surat.tanggal_kirim).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
                const statusText = (surat.status || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                let html = `
                    <div class="card-header">
                        <h2>${surat.perihal || 'Tanpa Perihal'}</h2>
                        <div class="surat-meta">
                            <p><strong>Dari:</strong> ${surat.pengirim_nama || 'N/A'} (${surat.pengirim_jabatan || 'N/A'})</p>
                            <p><strong>Tanggal:</strong> ${formattedDate}</p>
                            <p><strong>Status:</strong> <span class="status-badge status-${(surat.status || '').toLowerCase()}">${statusText}</span></p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="surat-content">
                            ${surat.isi_surat || '<p><i>Isi surat tidak tersedia.</i></p>'}
                        </div>
                    </div>
                `;
                container.innerHTML = html;
            } else {
                throw new Error(result.error || 'Gagal memuat data surat.');
            }
        })
        .catch(error => {
            console.error('Error fetching surat detail:', error);
            container.innerHTML = `<div class="card-body"><p class="error-message">${error.message}</p></div>`;
        });
});
</script>

<?php include 'layouts/footer.php'; ?>
