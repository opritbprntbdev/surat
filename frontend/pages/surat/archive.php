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
            <h1 style="font-size:16px; font-weight:600;">Arsip</h1>
        </div>
        <div class="header-right"></div>
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
// Loader untuk Arsip (surat_penerima tipe != AKTIF untuk user saat ini)
(function(){
    const emailList = document.getElementById('email-list');
    const detailPane = document.getElementById('email-detail');
    if (!emailList) return;

    async function loadArchive(){
        emailList.innerHTML = Components.createLoadingState('Memuat arsip...');
        try{
            const resp = await API.getSuratList({ box: 'archive' });
            const data = resp.data?.data || [];
            if (!data.length){
                emailList.innerHTML = Components.createEmptyState('Arsip kosong', 'Belum ada surat di arsip.');
                return;
            }
            emailList.innerHTML = '';
            data.forEach((s)=>{
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
        }catch(err){
            emailList.innerHTML = Components.createErrorState('Gagal memuat', err.message, loadArchive);
        }
    }

    document.addEventListener('DOMContentLoaded', loadArchive);
})();
</script>
