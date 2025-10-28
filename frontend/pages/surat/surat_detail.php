<?php
$page_title = "Detail Surat";
require_once __DIR__ . '/../../../backend/config/config.php';
require_once __DIR__ . '/../../layouts/header.php';
require_once __DIR__ . '/../../layouts/sidebar.php';

$surat_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$role = strtoupper($_SESSION['role'] ?? 'CABANG');
?>

<style>
    .surat-meta { font-size: 0.9em; color: #6c757d; margin-top: 1rem; }
    .surat-meta p { margin: 0.25rem 0; }
    .surat-content { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #dee2e6; }
    .status-badge { display: inline-block; padding: .25em .6em; font-size: 75%; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; color: #fff; }
    .status-menunggu_persetujuan { background-color: #ffc107; color: #000; }
    .status-diteruskan { background-color: #17a2b8; }
    .status-selesai { background-color: #28a745; }
    .status-ditolak { background-color: #dc3545; }
    /* Chips untuk multi-select penerima */
    .chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
    .chip { display:inline-flex; align-items:center; gap:6px; padding:4px 8px; background:#eef2f7; border-radius:12px; font-size:12px; }
    .chip .remove { cursor:pointer; color:#666; }
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
    const role = '<?php echo $role; ?>';
    const container = document.getElementById('surat-detail-container');

    if (suratId <= 0) {
        container.innerHTML = '<div class="card-body"><p class="error-message">ID surat tidak valid.</p></div>';
        return;
    }

    const apiBase = (window.API_BASE || '/surat/backend/api').replace(/\/$/, '');
    fetch(`${apiBase}/surat.php?id=${suratId}`)
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

                        <hr />
                        <div class="actions" id="actions-area"></div>

                        <div class="history" style="margin-top:16px;">
                            <h3 style="margin:0 0 8px;">Riwayat Disposisi</h3>
                            ${renderDispositions(surat.dispositions)}
                            <h3 style="margin:16px 0 8px;">Jejak Perjalanan</h3>
                            ${renderRouting(surat.routing)}
                        </div>
                    </div>
                `;
                container.innerHTML = html;
                renderActions(role, surat);
            } else {
                throw new Error(result.error || 'Gagal memuat data surat.');
            }
        })
        .catch(error => {
            console.error('Error fetching surat detail:', error);
            container.innerHTML = `<div class="card-body"><p class="error-message">${error.message}</p></div>`;
        });

    function renderDispositions(list) {
        if (!Array.isArray(list) || list.length === 0) return '<p><i>Belum ada disposisi.</i></p>';
        return `<ul style="margin:0; padding-left:18px;">${list.map(d => `<li><strong>${d.user_nama}</strong> (${new Date(d.created_at).toLocaleString('id-ID')})<br/>${escapeHtml(d.disposition_text)}</li>`).join('')}</ul>`;
    }

    function renderRouting(list) {
        if (!Array.isArray(list) || list.length === 0) return '<p><i>Data rute belum tersedia.</i></p>';
        return `<ul style="margin:0; padding-left:18px;">${list.map(r => `<li><strong>${r.user_nama}</strong> - ${r.tipe_penerima} (diterima: ${new Date(r.diterima_at).toLocaleString('id-ID')}${r.ditindak_at ? ', ditindak: ' + new Date(r.ditindak_at).toLocaleString('id-ID') : ''})</li>`).join('')}</ul>`;
    }

    function renderActions(role, surat) {
        const el = document.getElementById('actions-area');
        if (!el) return;
        let html = '';

                if (role === 'UMUM' && surat.active_for_user && (/^MENUNGGU/.test(surat.status || ''))) {
            html += `
                <div class="card" style="margin:12px 0;">
                  <div class="card-header"><strong>Minta Disposisi</strong></div>
                  <div class="card-body">
                    <div class="form-group">
                                            <label>Kepada</label>
                                            <input type="text" id="req-to" class="form-input" placeholder="Ketik nama user..." list="req-list" autocomplete="off" />
                                            <datalist id="req-list"></datalist>
                    </div>
                    <div class="form-group">
                      <label>Catatan (opsional)</label>
                      <input type="text" id="req-note" class="form-input" />
                    </div>
                    <button id="btn-request" class="btn btn-primary">Kirim Permintaan Disposisi</button>
                  </div>
                </div>`;
        }

        if (surat.active_for_user && role !== 'UMUM') {
            html += `
                <div class="card" style="margin:12px 0;">
                  <div class="card-header"><strong>Tulis Disposisi Anda</strong></div>
                  <div class="card-body">
                    <div class="form-group">
                      <textarea id="disp-text" rows="4" class="form-textarea" placeholder="Tulis arahan atau keputusan..."></textarea>
                    </div>
                    <button id="btn-submit-disp" class="btn btn-primary">Kirim Kembali ke UMUM</button>
                  </div>
                </div>`;
        }

                if (role === 'UMUM' && surat.active_for_user && surat.status === 'SIAP_DISEBARKAN') {
            html += `
                <div class="card" style="margin:12px 0;">
                  <div class="card-header"><strong>Sebarkan Sesuai Disposisi</strong></div>
                  <div class="card-body">
                    <div class="form-group">
                                            <label>Tambah Penerima</label>
                                            <div style="display:flex; gap:8px;">
                                                <input type="text" id="final-to" class="form-input" placeholder="Ketik nama user..." list="final-list" autocomplete="off" />
                                                <button id="final-add" class="btn" type="button">Tambah</button>
                                            </div>
                                            <datalist id="final-list"></datalist>
                                            <div id="final-chips" class="chips"></div>
                    </div>
                    <button id="btn-final" class="btn btn-primary">Kirim</button>
                  </div>
                </div>`;
        }

        el.innerHTML = html || '<p><i>Tidak ada aksi tersedia.</i></p>';

        // --- Wire typeahead single (request) ---
        const reqInput = document.getElementById('req-to');
        const reqList = document.getElementById('req-list');
        let reqTimer = null, reqLastQ = '';
        if (reqInput && reqList) {
            // Fetch defaults on focus (empty query)
            reqInput.addEventListener('focus', () => {
                debounceReq('');
            });
            reqInput.addEventListener('input', () => {
                const q = reqInput.value.trim();
                debounceReq(q);
            });
        }
        function debounceReq(q){
            if (q === reqLastQ) return; reqLastQ = q;
            if (reqTimer) clearTimeout(reqTimer);
            reqTimer = setTimeout(async ()=>{
                try{
                    const base = (window.API_BASE || '/surat/backend/api').replace(/\/$/, '');
                    const res = await fetch(`${base}/recipients.php?q=` + encodeURIComponent(q));
                    const json = await res.json();
                    reqList.innerHTML = '';
                    (json.data || []).filter(x=>x.type==='USER').forEach(x=>{
                        const opt = document.createElement('option');
                        opt.value = x.label; opt.dataset.id = x.id; reqList.appendChild(opt);
                    });
                }catch{}
            }, 200);
        }
        const btnReq = document.getElementById('btn-request');
        if (btnReq) {
            btnReq.addEventListener('click', async () => {
                const val = reqInput ? reqInput.value.trim() : '';
                const opt = reqList ? Array.from(reqList.options).find(o=>o.value===val) : null;
                const targetId = opt ? parseInt(opt.dataset.id,10) : NaN;
                const note = (document.getElementById('req-note')?.value) || '';
                if (!targetId || Number.isNaN(targetId)) { alert('Pilih penerima dari daftar.'); return; }
                await callDisposisi('request', { surat_id: surat.id, target_user_id: targetId, note });
            });
        }

        const btnSub = document.getElementById('btn-submit-disp');
        if (btnSub) {
            btnSub.addEventListener('click', async () => {
                const text = (document.getElementById('disp-text').value || '').trim();
                if (!text) { alert('Teks disposisi wajib diisi'); return; }
                await callDisposisi('submit', { surat_id: surat.id, text });
            });
        }

        // --- Wire typeahead multi (final) ---
        const finalInput = document.getElementById('final-to');
        const finalList = document.getElementById('final-list');
        const finalChips = document.getElementById('final-chips');
        const selected = new Map(); // id -> label
        let tmr = null, lastQ = '';
        if (finalInput && finalList) {
            // Fetch defaults when focused with empty query
            finalInput.addEventListener('focus', ()=>{ debounceFinal(''); });
            finalInput.addEventListener('input', ()=>{
                const q = finalInput.value.trim();
                debounceFinal(q);
            });
        }
        function debounceFinal(q){
            if (q === lastQ) return; lastQ = q;
            if (tmr) clearTimeout(tmr);
            tmr = setTimeout(async ()=>{
                try{
                    const base = (window.API_BASE || '/surat/backend/api').replace(/\/$/, '');
                    const res = await fetch(`${base}/recipients.php?q=` + encodeURIComponent(q));
                    const json = await res.json();
                    finalList.innerHTML = '';
                    (json.data || []).filter(x=>x.type==='USER').forEach(x=>{
                        const opt = document.createElement('option');
                        opt.value = x.label; opt.dataset.id = x.id; finalList.appendChild(opt);
                    });
                }catch{}
            }, 200);
        }
        const addBtn = document.getElementById('final-add');
        if (addBtn) {
            addBtn.addEventListener('click', ()=>{
                const val = finalInput.value.trim();
                const opt = Array.from(finalList.options).find(o=>o.value===val);
                const id = opt ? parseInt(opt.dataset.id,10) : NaN;
                if (!id || Number.isNaN(id)) { alert('Pilih penerima dari daftar.'); return; }
                if (selected.has(id)) { finalInput.value=''; return; }
                selected.set(id, val);
                renderChips();
                finalInput.value='';
            });
        }
        function renderChips(){
            if (!finalChips) return;
            finalChips.innerHTML = '';
            selected.forEach((label,id)=>{
                const chip = document.createElement('span');
                chip.className = 'chip';
                chip.innerHTML = `${label} <span class="remove" title="Hapus">&times;</span>`;
                chip.querySelector('.remove').addEventListener('click', ()=>{ selected.delete(id); renderChips(); });
                finalChips.appendChild(chip);
            });
        }
        const btnFinal = document.getElementById('btn-final');
        if (btnFinal) {
            btnFinal.addEventListener('click', async ()=>{
                const ids = Array.from(selected.keys());
                if (!ids.length) { alert('Tambahkan minimal satu penerima.'); return; }
                await callDisposisi('final', { surat_id: surat.id, target_user_ids: ids });
            });
        }
    }

    async function callDisposisi(action, payload) {
        try {
            const base = (window.API_BASE || '/surat/backend/api').replace(/\/$/, '');
            const resp = await fetch(base + '/disposisi.php?action=' + encodeURIComponent(action), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                throw new Error(data.error || data.message || 'Gagal memproses.');
            }
            alert(data.message || 'Berhasil.');
            window.location.reload();
        } catch (e) {
            console.error(e);
            alert(e.message);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>

<?php include '../../layouts/footer.php'; ?>
