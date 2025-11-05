<?php require_once __DIR__ . '/../../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

<?php
// Guard: Hanya CABANG atau ADMIN yang bisa akses
$role = strtoupper($_SESSION['role'] ?? '');
$cabangId = $_SESSION['cabang_id'] ?? 0;

if (!in_array($role, ['CABANG', 'ADMIN']) || $cabangId <= 0) {
    header('Location: ../../index.php');
    exit;
}
?>

<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="mobile-menu-btn" class="mobile-menu-btn" title="Menu">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </div>
        <div class="header-center">
            <h1 style="font-size:16px; font-weight:600;">Daftar Nomor Surat</h1>
        </div>
        <div class="header-right"></div>
    </header>

    <div class="page-content" style="padding:20px;">
        <!-- Filter Section -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="padding:20px;">
                <div
                    style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500; font-size:14px;">Jenis
                            Surat</label>
                        <select id="filter-jenis" class="form-select">
                            <option value="">Semua</option>
                            <option value="MASUK">Surat Masuk</option>
                            <option value="KELUAR">Surat Keluar</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500; font-size:14px;">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua</option>
                            <option value="RESERVED">Reserved</option>
                            <option value="USED">Terpakai</option>
                            <option value="CANCELLED">Dibatalkan</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500; font-size:14px;">Tanggal
                            Dari</label>
                        <input type="date" id="filter-dari" class="form-input">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500; font-size:14px;">Tanggal
                            Sampai</label>
                        <input type="date" id="filter-sampai" class="form-input">
                    </div>
                </div>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-primary" onclick="loadData()">Filter</button>
                    <button class="btn" onclick="resetFilter()">Reset</button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card">
            <div style="overflow-x:auto;">
                <table class="table" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Nomor Surat</th>
                            <th style="min-width:100px;">Jenis</th>
                            <th style="min-width:150px;">Tanggal Ambil</th>
                            <th style="min-width:120px;">User</th>
                            <th style="min-width:100px;">Status</th>
                            <th style="min-width:200px;">Surat</th>
                            <th style="min-width:150px;">Keterangan</th>
                            <th style="min-width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="nomor-tbody">
                        <tr>
                            <td colspan="8" style="text-align:center; padding:60px 20px; color:#999;">
                                <svg style="width:48px; height:48px; margin:0 auto 10px; opacity:0.3;" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <div>Memuat data...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; border-top:1px solid #eee;">
                <div id="page-info" style="font-size:14px; color:#666;">Hal 1 dari 1</div>
                <div style="display:flex; gap:5px;">
                    <button id="btn-prev" onclick="prevPage()" class="btn" disabled>‹ Prev</button>
                    <button id="btn-next" onclick="nextPage()" class="btn" disabled>Next ›</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
    let currentPage = 1;
    let totalPages = 1;

    document.addEventListener('DOMContentLoaded', () => {
        loadData();
    });

    async function loadData(page = 1) {
        const jenis = document.getElementById('filter-jenis').value;
        const status = document.getElementById('filter-status').value;
        const dari = document.getElementById('filter-dari').value;
        const sampai = document.getElementById('filter-sampai').value;

        const params = new URLSearchParams({
            action: 'list',
            page: page,
            page_size: 50
        });

        if (jenis) params.append('jenis', jenis);
        if (status) params.append('status', status);
        if (dari) params.append('tanggal_dari', dari);
        if (sampai) params.append('tanggal_sampai', sampai);

        try {
            const response = await fetch(`../../../backend/api/nomor_surat.php?${params}`);
            const result = await response.json();

            console.log('API Response:', result);

            if (result.success) {
                const logs = result.data.data || [];
                const total = result.data.total || 0;
                
                renderTable(logs);
                
                // Calculate pagination
                const pageSize = 50;
                totalPages = Math.ceil(total / pageSize);
                currentPage = page;
                
                updatePagination();
            } else {
                showError(result.message || 'Gagal memuat data');
            }
        } catch (error) {
            console.error('Error loading data:', error);
            showError('Terjadi kesalahan saat memuat data');
        }
    }

    function renderTable(logs) {
        const tbody = document.getElementById('nomor-tbody');

        if (!logs || logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align:center; padding:60px 20px; color:#999;">
                        <svg style="width:48px; height:48px; margin:0 auto 10px; opacity:0.3;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <div>Tidak ada data nomor surat</div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => {
            const statusBadge = {
                'RESERVED': '<span class="badge" style="background:#fff3cd; color:#856404;">Reserved</span>',
                'USED': '<span class="badge" style="background:#d4edda; color:#155724;">Terpakai</span>',
                'CANCELLED': '<span class="badge" style="background:#f8d7da; color:#721c24;">Dibatalkan</span>'
            }[log.status] || log.status;

            const jenisBadge = {
                'MASUK': '<span class="badge" style="background:#d1ecf1; color:#0c5460;">Masuk</span>',
                'KELUAR': '<span class="badge" style="background:#e2e3e5; color:#383d41;">Keluar</span>'
            }[log.jenis_surat] || log.jenis_surat;

            const tanggal = new Date(log.tanggal_ambil).toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            let suratLink = '-';
            if (log.surat_id && log.surat_perihal) {
                suratLink = `<a href="../surat/surat_detail.php?id=${log.surat_id}" style="color:#1a73e8; text-decoration:none;" target="_blank" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">${log.surat_perihal}</a>`;
            }

            let aksi = '-';
            if (log.status === 'RESERVED') {
                aksi = `<button onclick="cancelNomor(${log.id}, '${log.nomor_surat}')" class="btn" style="background:#dc3545; color:white; padding:6px 12px; font-size:13px;">Batal</button>`;
            }

            return `
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:12px;"><strong>${log.nomor_surat}</strong></td>
                    <td style="padding:12px;">${jenisBadge}</td>
                    <td style="padding:12px; color:#5f6368; font-size:13px;">${tanggal}</td>
                    <td style="padding:12px; color:#5f6368;">${log.user_nama || '-'}</td>
                    <td style="padding:12px;">${statusBadge}</td>
                    <td style="padding:12px;">${suratLink}</td>
                    <td style="padding:12px; color:#5f6368; font-size:13px;">${log.keterangan || '-'}</td>
                    <td style="padding:12px;">${aksi}</td>
                </tr>
            `;
        }).join('');
    }

    function updatePagination() {
        document.getElementById('btn-prev').disabled = currentPage <= 1;
        document.getElementById('btn-next').disabled = currentPage >= totalPages;
        document.getElementById('page-info').textContent = `Hal ${currentPage} dari ${totalPages}`;
    }

    function prevPage() {
        if (currentPage > 1) loadData(currentPage - 1);
    }

    function nextPage() {
        if (currentPage < totalPages) loadData(currentPage + 1);
    }

    function resetFilter() {
        document.getElementById('filter-jenis').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-dari').value = '';
        document.getElementById('filter-sampai').value = '';
        loadData(1);
    }

    async function cancelNomor(logId, nomorSurat) {
        if (!confirm(`Batalkan nomor ${nomorSurat}?\n\nNomor ini akan dibatalkan dan tidak bisa digunakan.`)) {
            return;
        }

        try {
            const response = await fetch('../../../backend/api/nomor_surat.php?action=cancel', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    log_id: logId,
                    reason: 'Dibatalkan oleh user'
                })
            });

            const result = await response.json();

            if (result.success) {
                alert('Nomor berhasil dibatalkan');
                loadData(currentPage);
            } else {
                showError(result.message || 'Gagal membatalkan nomor');
            }
        } catch (error) {
            console.error('Error canceling nomor:', error);
            showError('Terjadi kesalahan saat membatalkan nomor');
        }
    }

    function showError(message) {
        alert(message);
    }
</script>