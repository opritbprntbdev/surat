<?php require_once __DIR__ . '/../../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>
<?php $role = strtoupper($_SESSION['role'] ?? 'CABANG'); ?>

<!-- Override layout height/overflow so long editor content can scroll -->
<style>
    body { overflow-y: auto !important; }
    .gmail-container { height: auto !important; min-height: 100vh; }
    .page-content { overflow: visible; }
    .tox.tox-tinymce { max-width: 100%; }
    .form-actions { display: flex; gap: 12px; justify-content: flex-end; flex-wrap: wrap; margin-top: 12px; }
    .form-actions .btn { height: 40px; padding: 8px 16px; }
    @media (max-width: 576px) { .form-actions { flex-direction: column-reverse; align-items: stretch; } .form-actions .btn { width: 100%; } }
</style>

<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="mobile-menu-btn" class="mobile-menu-btn" title="Menu">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <div class="header-center">
            <h1 style="font-size:16px; font-weight:600;">Tulis Surat</h1>
        </div>
        <div class="header-right"></div>
    </header>

    <div class="page-content">
        <div class="card">
            <div class="card-header">
                <h2>Form Tulis Surat</h2>
            </div>
            <div class="card-body">
                <form id="compose-form" class="form">
                    <div class="form-group">
                        <label for="template-select">Gunakan Template</label>
                        <select id="template-select" class="form-select">
                            <option value="">-- Pilih Template --</option>
                        </select>
                    </div>

                    <div id="placeholder-form-container" class="card" style="display: none; margin-bottom: 1rem; background-color: #f8f9fa;">
                        <div class="card-header" style="padding: 0.75rem 1.25rem; background-color: #e9ecef; border-bottom: 1px solid rgba(0,0,0,.125);">
                            <h5 style="margin:0; font-size: 1rem;">Lengkapi Data Surat</h5>
                        </div>
                        <div class="card-body" id="dynamic-form-fields" style="padding: 1.25rem;"></div>
                    </div>

                    <div class="form-group">
                        <label for="kepada">Kepada</label>
                        <input type="text" id="kepada" name="kepada" placeholder="UMUM" value="<?php echo $role === 'CABANG' ? 'UMUM' : ''; ?>" <?php echo $role === 'CABANG' ? 'readonly' : ''; ?> />
                        <small class="form-hint">Untuk Cabang: wajib ke UMUM</small>
                    </div>
                    <div class="form-group">
                        <label for="perihal">Perihal</label>
                        <input type="text" id="perihal" name="perihal" placeholder="Perihal surat" required />
                    </div>
                    <div class="form-group">
                        <label for="isi_surat">Isi Surat</label>
                        <textarea id="isi_surat" name="isi_surat" rows="10" placeholder="Tulis isi surat di sini..."></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Kirim Surat</button>
                        <a href="<?php echo $base_url; ?>index.php" class="btn">Batal</a>
                    </div>
                    <div id="compose-error" class="error-message" style="margin-top:8px; display:none;"></div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once '../../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
(function () {
    const form = document.getElementById('compose-form');
    const errorEl = document.getElementById('compose-error');
    const role = '<?php echo $role; ?>';
    const templateSelect = document.getElementById('template-select');

    async function loadTemplates() {
        try {
            const response = await fetch('../../../backend/api/templates.php');
            const result = await response.json();
            if (result.data) {
                result.data.forEach(template => {
                    if (template.is_active == 1) {
                        const option = document.createElement('option');
                        option.value = template.id;
                        option.textContent = template.nama_template;
                        templateSelect.appendChild(option);
                    }
                });
            }
        } catch (error) {
            console.error('Gagal memuat template:', error);
        }
    }

    templateSelect.addEventListener('change', async function() {
        const templateId = this.value;
        const formContainer = document.getElementById('placeholder-form-container');
        const formFieldsDiv = document.getElementById('dynamic-form-fields');
        formContainer.style.display = 'none';
        formFieldsDiv.innerHTML = '';
        if (!templateId) { tinymce.get('isi_surat').setContent(''); return; }
        try {
            const response = await fetch(`../../../backend/api/templates.php?id=${templateId}`);
            const result = await response.json();
            if (result.data && result.data.konten_html) {
                const templateContent = result.data.konten_html;
                tinymce.get('isi_surat').setContent(templateContent);
                generatePlaceholderForm(templateContent);
            }
        } catch (error) {
            console.error('Gagal memuat konten template:', error);
            alert('Gagal memuat konten template.');
        }
    });

    function generatePlaceholderForm(content) {
        const placeholderRegex = /{{\s*([a-zA-Z0-9_]+)\s*}}/g;
        const uniquePlaceholders = new Set();
        let match;
        while ((match = placeholderRegex.exec(content)) !== null) {
            uniquePlaceholders.add(match[0]);
        }
        const placeholders = Array.from(uniquePlaceholders);
        const formContainer = document.getElementById('placeholder-form-container');
        const formFieldsDiv = document.getElementById('dynamic-form-fields');
        if (placeholders.length > 0) {
            formFieldsDiv.dataset.originalContent = content;
            placeholders.forEach(placeholder => {
                const varName = placeholder.replace(/{{\s*|\s*}}/g, '');
                const labelText = varName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                const fieldGroup = document.createElement('div');
                fieldGroup.className = 'form-group';
                fieldGroup.innerHTML = `
                    <label for="placeholder-${varName}">${labelText}</label>
                    <input type="text" id="placeholder-${varName}" data-placeholder="${placeholder}" class="form-input dynamic-placeholder-input">
                `;
                formFieldsDiv.appendChild(fieldGroup);
            });
            formContainer.style.display = 'block';
        }
    }

    document.getElementById('dynamic-form-fields').addEventListener('input', function(e) {
        if (e.target && e.target.classList.contains('dynamic-placeholder-input')) {
            updateEditorContent();
        }
    });

    function updateEditorContent() {
        const formFieldsDiv = document.getElementById('dynamic-form-fields');
        let currentContent = formFieldsDiv.dataset.originalContent;
        if (!currentContent) return;
        const inputs = formFieldsDiv.querySelectorAll('.dynamic-placeholder-input');
        inputs.forEach(input => {
            const placeholder = input.dataset.placeholder;
            const value = input.value;
            const replacement = value.trim() !== '' ? value : placeholder;
            currentContent = currentContent.replace(new RegExp(escapeRegExp(placeholder), 'g'), replacement);
        });
        const bookmark = tinymce.get('isi_surat').selection.getBookmark();
        tinymce.get('isi_surat').setContent(currentContent);
        tinymce.get('isi_surat').selection.moveToBookmark(bookmark);
    }

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\\]\\]/g, '\\$&');
    }

    tinymce.init({
        selector: '#isi_surat',
        menubar: false,
        plugins: 'lists link image charmap preview',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
        height: 300,
        setup: function (editor) {
            editor.on('init', function () {
                editor.getDoc().body.style.fontSize = '14px';
                editor.getDoc().body.style.lineHeight = '1.6';
            });
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errorEl.style.display = 'none';
        errorEl.textContent = '';
        const requiredFields = ['kepada', 'perihal', 'isi_surat'];
        for (const field of requiredFields) {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                errorEl.textContent = `Harap lengkapi field ${input.previousElementSibling.innerText}.`;
                errorEl.style.display = 'block';
                return;
            }
        }
        const isi_surat = tinymce.get('isi_surat').getContent();
        if (!isi_surat.trim()) {
            errorEl.textContent = `Harap lengkapi field Isi Surat.`;
            errorEl.style.display = 'block';
            return;
        }
        const payload = {
            kepada: document.getElementById('kepada').value,
            perihal: document.getElementById('perihal').value,
            isi_surat: isi_surat
        };
        try {
            const response = await fetch('../../../backend/api/surat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (result.success && result.data && result.data.id) {
                alert(result.message || 'Surat berhasil dikirim!');
                window.location.href = '../surat/sent.php';
            } else {
                errorEl.textContent = result.error || 'Gagal mengirim surat.';
                errorEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Gagal mengirim surat:', error);
            errorEl.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorEl.style.display = 'block';
        }
    });

    loadTemplates();
})();
</script>
