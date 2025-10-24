<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>

<!-- Main Content untuk Halaman Kotak Masuk -->
<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="refresh-btn" class="refresh-btn" title="Refresh">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
            </button>
        </div>
        <div class="header-center">
            <div class="search-container">
                <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="search-input" placeholder="Cari surat..." class="search-input">
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar" title="<?php echo $nama_user; ?>">
                <?php echo $initial_user; ?>
            </div>
        </div>
    </header>

    <!-- Area Konten Surat -->
    <div class="email-content">
        <!-- Panel Kiri: Daftar Surat -->
        <div class="email-list" id="email-list">
            <!-- Daftar surat akan dimuat oleh JavaScript -->
        </div>

        <!-- Panel Kanan: Detail Surat -->
        <div class="email-detail" id="email-detail">
            <div class="email-detail-placeholder">
                <p>Pilih surat untuk dibaca</p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'layouts/footer.php'; ?>