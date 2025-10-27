<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>
<?php $role = strtoupper($_SESSION['role'] ?? 'CABANG'); ?>

<!-- Override layout height/overflow so long editor content can scroll -->
<style>
    /* Global CSS sets body{overflow:hidden} and .gmail-container{height:100vh};
       That blocks page scrolling on this compose screen when content is long. */
    body {
        overflow-y: auto !important;
    }

    .gmail-container {
        height: auto !important;
        min-height: 100vh;
    }

    .page-content {
        overflow: visible;
    }

    /* Optional: keep editor toolbar visible while content grows */
    .tox.tox-tinymce {
        max-width: 100%;
    }

    /* Compose page actions */
    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        flex-wrap: wrap;
        margin-top: 12px;
    }

    .form-actions .btn {
        height: 40px;
        padding: 8px 16px;
    }

    @media (max-width: 576px) {
        .form-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .form-actions .btn {
            width: 100%;
        }
    }
</style>

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
                        <label for="kepada">Kepada</label>
                        <input type="text" id="kepada" name="kepada" placeholder="UMUM"
                            value="<?php echo $role === 'CABANG' ? 'UMUM' : ''; ?>" <?php echo $role === 'CABANG' ? 'readonly' : ''; ?> />
                        <small class="form-hint">Untuk Cabang: wajib ke UMUM</small>
                    </div>
                    <div class="form-group">
                        <label for="perihal">Perihal</label>
                        <input type="text" id="perihal" name="perihal" placeholder="Perihal surat" required />
                    </div>
                    <div class="form-group">
                        <label for="isi_surat">Isi Surat</label>
                        <textarea id="isi_surat" name="isi_surat" rows="10"
                            placeholder="Tulis isi surat di sini..."></textarea>
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

<?php require_once 'layouts/footer.php'; ?>

<!-- TinyMCE CDN (tanpa API key via jsDelivr) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script>
    (function () {
        const form = document.getElementById('compose-form');
        const errorEl = document.getElementById('compose-error');
        const role = '<?php echo $role; ?>';

        // Init TinyMCE
        if (window.tinymce) {
            tinymce.init({
                selector: '#isi_surat',
                height: 480,
                menubar: 'file edit view insert format tools table help',
                toolbar_sticky: true,
                plugins: 'autolink autosave lists link table advlist charmap hr code codesample paste quickbars autoresize',
                toolbar: [
                    'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor removeformat',
                    '| alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | link charmap hr | superscript subscript | code'
                ].join(' '),
                quickbars_selection_toolbar: 'bold italic underline | forecolor backcolor | link',
                quickbars_insert_toolbar: 'table hr charmap',
                font_family_formats: 'Arial=arial,helvetica,sans-serif;Times New Roman=times new roman,times;Calibri=calibri,arial,helvetica,sans-serif;Cambria=cambria,georgia,serif',
                fontsize_formats: '10pt 11pt 12pt 13pt 14pt 16pt 18pt',
                advlist_bullet_styles: 'default,circle,square',
                advlist_number_styles: 'default,lower-alpha,lower-roman,upper-alpha,upper-roman',
                paste_as_text: false,
                paste_data_images: false,
                paste_merge_formats: true,
                paste_convert_word_fake_lists: true,
                paste_webkit_styles: 'none',
                paste_retain_style_properties: '',
                autoresize_bottom_margin: 20,
                autosave_interval: '15s',
                autosave_retention: '30m',
                branding: false,
                statusbar: false,
                elementpath: false,
                resize: false,
                promotion: false,
                block_unsupported_drop: true,
                content_style: 'body{font-family:Arial,Helvetica,sans-serif;font-size:12pt;line-height:1.6;color:#202124;} h1{font-size:18pt;} h2{font-size:16pt;} img{max-width:100%;height:auto;} table{border-collapse:collapse;} th,td{padding:4px 6px;} table[border] th,table[border] td{border-color:#cfd3d7;}',
                toolbar_mode: 'wrap'
            });
        }

        async function postJSON(url, data) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                credentials: 'same-origin'
            });
            let json;
            try { json = await res.json(); } catch (e) { json = { success: false, error: 'Response bukan JSON' }; }
            if (!res.ok || json.success === false) {
                const msg = json && (json.error || json.message) || ('Error ' + res.status);
                throw new Error(msg);
            }
            return json;
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorEl.style.display = 'none';
            const kepada = document.getElementById('kepada').value.trim();
            const perihal = document.getElementById('perihal').value.trim();
            const isi_surat = (window.tinymce && tinymce.get('isi_surat')) ? tinymce.get('isi_surat').getContent() : document.getElementById('isi_surat').value;
            if (!perihal) {
                errorEl.textContent = 'Perihal wajib diisi';
                errorEl.style.display = 'block';
                return;
            }
            try {
                // Untuk CABANG, paksa kepada = UMUM di server sisi logika nanti; field ini hanya untuk tampilan sekarang
                const resp = await postJSON('<?php echo rtrim($base_url, '/'); ?>/../backend/api/surat.php', { perihal, isi_surat, kepada });
                // Redirect ke Surat Terkirim
                window.location.href = '<?php echo $base_url; ?>surat-keluar.php';
            } catch (err) {
                errorEl.textContent = err.message || 'Gagal mengirim surat';
                errorEl.style.display = 'block';
            }
        });

        // Simple typeahead via datalist for UMUM role (search Divisi/Direksi/Cabang)
        const kepadaInput = document.getElementById('kepada');
        if (kepadaInput && role === 'UMUM') {
            const dataListId = 'kepada-list';
            let dl = document.getElementById(dataListId);
            if (!dl) { dl = document.createElement('datalist'); dl.id = dataListId; document.body.appendChild(dl); }
            kepadaInput.setAttribute('list', dataListId);

            let lastQ = '';
            let timer = null;
            kepadaInput.addEventListener('input', () => {
                const q = kepadaInput.value.trim();
                if (q === lastQ) return;
                lastQ = q;
                if (timer) clearTimeout(timer);
                timer = setTimeout(async () => {
                    if (q.length < 2) { dl.innerHTML = ''; return; }
                    try {
                        const res = await fetch('<?php echo rtrim($base_url, '/'); ?>/../backend/api/recipients.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
                        const json = await res.json();
                        if (!json.success) return;
                        dl.innerHTML = '';
                        (json.data || []).forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.label;
                            opt.setAttribute('data-type', item.type);
                            opt.setAttribute('data-id', item.id);
                            dl.appendChild(opt);
                        });
                    } catch (err) { /* silent */ }
                }, 250);
            });
        }
    })();
</script>