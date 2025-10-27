<?php
$page_title = "Editor Template";
require_once __DIR__ . '/../backend/config/config.php';
include 'layouts/header.php';
include 'layouts/sidebar.php';
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
            <h1 class="page-title">Editor Template</h1>
        </div>
        <div class="header-right"></div>
    </header>

    <div class="page-content">
        <div class="card">
            <div class="card-body">
                <form id="template-form" class="form">
                    <div class="form-group">
                        <label for="nama_template">Nama Template</label>
                        <input type="text" id="nama_template" name="nama_template" class="form-input"
                            placeholder="Contoh: Surat Permohonan Cuti" required>
                    </div>
                    <div class="form-group">
                        <label for="konten_html">Isi Template</label>
                        <p class="form-hint">Gunakan placeholder seperti <code>{{nama_variabel}}</code> untuk data
                            dinamis.</p>
                        <textarea id="konten_html" name="konten_html" class="form-textarea"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Template</button>
                        <a href="templates.php" class="btn">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('template-form');
        const namaTemplateInput = document.getElementById('nama_template');
        const pageTitle = document.querySelector('.page-title');

        const urlParams = new URLSearchParams(window.location.search);
        const templateId = urlParams.get('id');
        const isEditMode = templateId !== null;

        tinymce.init({
            selector: '#konten_html',
            height: 500,
            menubar: 'file edit view insert format tools table help',
            plugins: 'autolink autosave lists link table advlist charmap code codesample quickbars autoresize',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor removeformat | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link charmap | code',
            toolbar_sticky: true,
            quickbars_selection_toolbar: 'bold italic underline | forecolor backcolor | link',
            quickbars_insert_toolbar: 'table charmap',
            paste_as_text: true, // Changed to true for better cross-browser paste consistency
            paste_data_images: true,
            branding: false,
            statusbar: false,
            elementpath: false,
            promotion: false,
            content_style: 'body{font-family:Arial,Helvetica,sans-serif;font-size:12pt;line-height:1.6;}',
        });

        if (isEditMode) {
            pageTitle.textContent = 'Edit Template';
            fetch(`../backend/api/templates.php?id=${templateId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.data) {
                        namaTemplateInput.value = result.data.nama_template;
                        tinymce.get('konten_html').setContent(result.data.konten_html || '');
                    } else {
                        alert('Template tidak ditemukan.');
                        window.location.href = 'templates.php';
                    }
                })
                .catch(error => {
                    console.error('Error fetching template:', error);
                    alert('Gagal memuat data template.');
                });
        } else {
            pageTitle.textContent = 'Tambah Template Baru';
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const nama_template = namaTemplateInput.value.trim();
            const konten_html = tinymce.get('konten_html').getContent();

            if (!nama_template) {
                alert('Nama template tidak boleh kosong.');
                return;
            }

            const apiUrl = isEditMode
                ? `../backend/api/templates.php?id=${templateId}`
                : '../backend/api/templates.php';

            const apiMethod = isEditMode ? 'PUT' : 'POST';

            fetch(apiUrl, {
                method: apiMethod,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nama_template, konten_html })
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok) {
                        alert(data.message || 'Template berhasil disimpan.');
                        window.location.href = 'templates.php';
                    } else {
                        throw new Error(data.error || 'Terjadi kesalahan.');
                    }
                })
                .catch(error => {
                    console.error('Error saving template:', error);
                    alert(error.message);
                });
        });
    });
</script>

<?php include 'layouts/footer.php'; ?>