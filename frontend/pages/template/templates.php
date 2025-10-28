<?php
$page_title = "Manajemen Template";
require_once __DIR__ . '/../../../backend/config/config.php';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';
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
            <h1 class="page-title">Manajemen Template Surat</h1>
        </div>
        <div class="header-right">
            <a href="template-editor.php" class="btn btn-primary">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Tambah Baru</span>
            </a>
        </div>
    </header>

    <div class="page-content">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="templates-table">
                        <thead>
                            <tr>
                                <th>Nama Template</th>
                                <th>Status</th>
                                <th>Tanggal Dibuat</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data akan dimuat di sini oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.querySelector('#templates-table tbody');

        async function loadTemplates() {
            tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Memuat data...</td></tr>';
            try {
                const response = await fetch('../../../backend/api/templates.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const result = await response.json();

                if (result.data && result.data.length > 0) {
                    tableBody.innerHTML = '';
                    result.data.forEach(template => {
                        const row = document.createElement('tr');
                        row.dataset.id = template.id;
                        row.innerHTML = `
                        <td>${escapeHtml(template.nama_template)}</td>
                        <td><span class="badge ${template.is_active == 1 ? 'badge-success' : 'badge-danger'}">${template.is_active == 1 ? 'Aktif' : 'Tidak Aktif'}</span></td>
                        <td>${new Date(template.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
                        <td class="text-right">
                            <a href="template-editor.php?id=${template.id}" class="btn btn-sm btn-outline">Edit</a>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${template.id}">Hapus</button>
                        </td>
                    `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada template yang dibuat.</td></tr>';
                }
            } catch (error) {
                console.error('Error loading templates:', error);
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Gagal memuat data. Silakan coba lagi.</td></tr>';
            }
        }

        async function deleteTemplate(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus template ini? Tindakan ini tidak dapat dibatalkan.')) {
                return;
            }

            try {
                const response = await fetch(`../../../backend/api/templates.php?id=${id}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Template berhasil dihapus.');
                    loadTemplates(); // Muat ulang daftar
                } else {
                    throw new Error(result.error || 'Gagal menghapus template.');
                }
            } catch (error) {
                console.error('Error deleting template:', error);
                alert(error.message);
            }
        }

        tableBody.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('delete-btn')) {
                const id = e.target.dataset.id;
                deleteTemplate(id);
            }
        });

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        loadTemplates();
    });
</script>

<?php include '../../layouts/footer.php'; ?>