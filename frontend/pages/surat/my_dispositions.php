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
            <h1 style="font-size:16px; font-weight:600;">Disposisi Saya</h1>
        </div>
        <div class="header-right"></div>
    </header>

    <div class="email-content">
        <div class="email-list" id="email-list"></div>
        <div class="email-detail" id="email-detail">
            <div class="email-detail-placeholder">
                <p>Pilih item untuk melihat detail surat</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<script>
(function(){
  const emailList = document.getElementById('email-list');
  const detailPane = document.getElementById('email-detail');
  if (!emailList) return;

  async function loadMine(){
    emailList.innerHTML = Components.createLoadingState('Memuat jawaban disposisi saya...');
    try{
      const resp = await API.getSuratList({ box: 'my_disposisi' });
      const rows = resp.data?.data || [];
      if (!rows.length){
        emailList.innerHTML = Components.createEmptyState('Belum ada jawaban', 'Anda belum mengisi disposisi.');
        return;
      }
      emailList.innerHTML = '';
      rows.forEach((s)=>{
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
      emailList.innerHTML = Components.createErrorState('Gagal memuat', err.message, loadMine);
    }
  }

  document.addEventListener('DOMContentLoaded', loadMine);
})();
</script>
