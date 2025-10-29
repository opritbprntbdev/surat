<?php require_once __DIR__ . '/../../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../../layouts/sidebar.php'; ?>

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
            <div class="search-container">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="page-search-input" placeholder="Cari surat (perihal/nomor/status) untuk tracking..." class="search-input">
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                <label for="source-select" class="sr-only">Sumber</label>
                <select id="source-select" class="page-size">
                    <option value="inbox">Kotak Masuk</option>
                    <option value="sent">Terkirim</option>
                    <option value="archive">Arsip</option>
                    <option value="starred">Berbintang</option>
                </select>
                <span style="font-size:12px;color:#5f6368;">Pilih sumber untuk melihat status perjalanan surat.</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-menu-container">
                <div class="user-avatar" id="user-avatar-btn" title="<?php echo $nama_user; ?>">
                    <?php echo $initial_user; ?>
                </div>
                <div id="user-dropdown" class="user-dropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar"><?php echo $initial_user; ?></div>
                        <div class="dropdown-user-info">
                            <strong><?php echo $nama_user; ?></strong>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                    </div>
                    <a href="/surat/backend/api/logout.php" class="dropdown-item">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="list-pagination"><div id="pager" class="pager"></div></div>

    <div class="email-content">
        <div class="email-list" id="email-list"></div>
        <div class="email-detail" id="email-detail">
            <div class="email-detail-placeholder">
                <p>Ketik kata kunci lalu pilih surat untuk melihat jejak perjalanan.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
(function(){
    const emailList = document.getElementById('email-list');
    const detailPane = document.getElementById('email-detail');
    const searchInput = document.getElementById('page-search-input');
    const sourceSel = document.getElementById('source-select');
    if (!emailList) return;

    let rows = [];
    let page = 1, pageSize = parseInt(localStorage.getItem('SURAT_PAGE_SIZE')||'',10) || 50, total = 0;

    function render(list){
        emailList.innerHTML = '';
        list.forEach((s)=>{
            const item = Components.createSuratItem(s);
            item.addEventListener('click', async ()=>{
                detailPane.innerHTML = Components.createLoadingState('Memuat detail...');
                try{
                    const d = await API.getSuratDetail(s.id);
                    detailPane.innerHTML = '';
                    detailPane.appendChild(Components.createSuratDetail(d.data));
                }catch(err){
                    detailPane.innerHTML = Components.createErrorState('Gagal memuat detail', err.message, ()=>{});
                }
            });
            emailList.appendChild(item);
        });
    }

    function renderPager(){
        const pager = document.getElementById('pager');
        if (!pager) return;
        if (!total) { pager.innerHTML = ''; return; }
        const pages = Math.max(1, Math.ceil(total / pageSize));
        const start = (page-1)*pageSize + 1;
        const end = Math.min(total, page*pageSize);
        pager.innerHTML = `
            <button class="btn" id="pager-prev" ${page<=1?'disabled':''}>&laquo; Prev</button>
            <button class="btn" id="pager-next" ${page>=pages?'disabled':''}>Next &raquo;</button>
            <span class="info">Halaman ${page} dari ${pages} • Menampilkan ${start}-${end} dari ${total}</span>
            <span class="info" style="margin-left:auto">Per halaman</span>
            <select id="page-size-select" class="page-size">
              <option value="20">20</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
        `;
        const prev = document.getElementById('pager-prev');
        const next = document.getElementById('pager-next');
        if (prev) prev.onclick = ()=>{ if (page>1){ page--; load(); } };
        if (next) next.onclick = ()=>{ if (page<pages){ page++; load(); } };
        const sel = document.getElementById('page-size-select');
        if (sel){
            sel.value = String(pageSize);
            sel.onchange = ()=>{
                const v = parseInt(sel.value,10);
                if (!isNaN(v)){
                    localStorage.setItem('SURAT_PAGE_SIZE', String(v));
                    pageSize = v; page = 1; load();
                }
            };
        }
    }

    async function load(){
        emailList.innerHTML = Components.createLoadingState('Memuat data tracking...');
        try{
            const q = (searchInput && searchInput.value ? searchInput.value.trim() : '');
            const src = (sourceSel && sourceSel.value) || 'inbox';
            const params = { q, page, page_size: pageSize };
            if (src === 'sent' || src === 'archive' || src === 'starred') params.box = src;
            const resp = await API.getSuratList(params);
            rows = resp.data?.data || [];
            total = Number(resp.data?.total || 0);
            if (!rows.length){
                emailList.innerHTML = Components.createEmptyState('Tidak ada hasil', 'Ubah kata kunci atau sumber data.');
                renderPager();
                return;
            }
            render(rows);
            renderPager();
        }catch(err){
            emailList.innerHTML = Components.createErrorState('Gagal memuat', err.message, load);
        }
    }

    if (searchInput){
        const deb = Utils.debounce(()=>{ page = 1; load(); }, 200);
        ['input','keyup','change'].forEach(evt => searchInput.addEventListener(evt, deb));
    }
    if (sourceSel){
        sourceSel.addEventListener('change', ()=>{ page = 1; load(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load); else load();
})();
</script>
