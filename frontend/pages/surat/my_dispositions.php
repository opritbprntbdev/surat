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
          <a href="../backend/api/logout.php" class="dropdown-item">
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadMine);
  } else {
    // DOM already ready (script placed late), run immediately
    loadMine();
  }
})();
</script>
