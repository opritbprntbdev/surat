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
                <input type="text" id="page-search-input" placeholder="Cari surat berbintang..." class="search-input">
            </div>
            <h1 style="font-size:16px; font-weight:600;">Berbintang</h1>
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

    <div class="email-content">
        <div class="email-list" id="email-list"></div>
        <div class="email-detail" id="email-detail">
            <div class="email-detail-placeholder">
                <p>Pilih surat untuk melihat detail</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
// Loader for Starred box
(function(){
    const emailList = document.getElementById('email-list');
    const detailPane = document.getElementById('email-detail');
    const searchInput = document.getElementById('page-search-input');
    if (!emailList) return;

    let rows = [];

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

    function applyFilter(){
        const q = (searchInput && searchInput.value ? searchInput.value : '').toLowerCase().trim();
        if (!q) { render(rows); return; }
        const hit = (v)=> String(v||'').toLowerCase().includes(q);
        const filtered = rows.filter(s => hit(s.perihal) || hit(s.pengirim_nama) || hit(s.nomor_surat) || hit(s.status) || hit(s.last_dispo_text));
        render(filtered);
    }

    async function loadStarred(){
        emailList.innerHTML = Components.createLoadingState('Memuat surat berbintang...');
        try{
            const resp = await API.getSuratList({ box: 'starred' });
            rows = resp.data?.data || [];
            if (!rows.length){
                emailList.innerHTML = Components.createEmptyState('Tidak ada surat berbintang', 'Klik ikon bintang pada surat untuk menambahkannya ke daftar ini.');
                return;
            }
            applyFilter();
        }catch(err){
            emailList.innerHTML = Components.createErrorState('Gagal memuat', err.message, loadStarred);
        }
    }

    if (searchInput){
        const deb = Utils.debounce(applyFilter, 200);
        ['input','keyup','change'].forEach(evt => searchInput.addEventListener(evt, deb));
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', loadStarred); else loadStarred();
})();
</script>
