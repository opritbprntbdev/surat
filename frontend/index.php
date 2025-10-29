<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>

<!-- Main Content untuk Halaman Kotak Masuk -->
<main class="main-content">
    <header class="header">
        <div class="header-left">
            <button id="mobile-menu-btn" class="mobile-menu-btn" title="Menu">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <a href="<?php echo $base_url; ?>pages/surat/compose.php" id="compose-top-btn" class="refresh-btn" title="Tulis Surat" style="margin-left:8px;">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </a>
            <button id="refresh-btn" class="refresh-btn" title="Refresh">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                </svg>
            </button>
            <button id="filter-unanswered-btn" class="refresh-btn" title="Tampilkan yang belum dijawab" style="margin-left:6px;">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="hide-on-mobile" style="margin-left:4px; font-size:12px;">Belum Dijawab</span>
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
            <div class="quick-filters" id="quick-filters" aria-label="Filter cepat">
                <div class="tabs" role="tablist">
                    <button class="tab active" data-facet="ALL" role="tab" aria-selected="true">Semua</button>
                    <button class="tab" data-facet="CABANG" role="tab" aria-selected="false">Cabang</button>
                    <button class="tab" data-facet="DIREKSI" role="tab" aria-selected="false">Direksi</button>
                    <button class="tab" data-facet="DIVISI" role="tab" aria-selected="false">Divisi</button>
                    <button class="tab" data-facet="PIMSUBDIV" role="tab" aria-selected="false">Pimsubdiv</button>
                </div>
                <div class="read-filter">
                    <label for="read-filter-select" class="sr-only">Filter baca</label>
                    <select id="read-filter-select">
                        <option value="ALL">Semua</option>
                        <option value="STARRED">Berbintang</option>
                        <option value="UNSTARRED">Tidak berbintang</option>
                        <option value="UNREAD">Belum dibaca</option>
                        <option value="READ">Dibaca</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="header-right">
            <!-- STRUKTUR DROPDOWN YANG BENAR -->
            <div class="user-menu-container">
                <div class="user-avatar" id="user-avatar-btn" title="<?php echo $nama_user; ?>">
                    <?php echo $initial_user; ?>
                </div>
                <!-- Dropdown Menu -->
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Toolbar Pagination -->
    <div class="list-pagination" id="list-pagination">
        <div id="pager" class="pager"></div>
    </div>

    <!-- Area Konten Surat -->
    <div class="email-content">
        <div class="email-list" id="email-list"></div>
        <div class="email-detail" id="email-detail">
            <div class="email-detail-placeholder">
                <p>Pilih surat untuk dibaca</p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'layouts/footer.php'; ?>