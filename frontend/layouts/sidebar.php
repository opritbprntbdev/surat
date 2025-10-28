<?php
// Pastikan base URL tersedia
if (!isset($base_url)) {
    $base_url = '/surat/frontend/';
}

// Ambil path file PHP yang sedang aktif
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];
// Ambil role user untuk kontrol menu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'CABANG';
$isAdmin = strtoupper($role) === 'ADMIN';
$isCabang = strtoupper($role) === 'CABANG';
$isUmum = strtoupper($role) === 'UMUM';
?>
<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <?php if ($isCabang || $isUmum || $isAdmin): ?>
            <a href="<?php echo $base_url; ?>pages/surat/compose.php" id="compose-btn" class="compose-btn">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Tulis Surat</span>
            </a>
        <?php endif; ?>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <!-- Kotak Masuk -->
            <a href="<?php echo $base_url; ?>index.php"
                class="nav-item <?php echo ($current_page == 'index.php' && !strpos($current_path, '/pages/')) ? 'active' : ''; ?>">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                    </path>
                </svg>
                <span>Kotak Masuk</span>
            </a>

            <!-- Surat Terkirim -->
            <a href="<?php echo $base_url; ?>pages/surat/sent.php"
                class="nav-item <?php echo (strpos($current_path, '/pages/surat/sent.php') !== false) ? 'active' : ''; ?>">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                <span>Surat Terkirim</span>
            </a>

            <!-- Arsip -->
            <a href="<?php echo $base_url; ?>arsip.php"
                class="nav-item <?php echo ($current_page == 'arsip.php') ? 'active' : ''; ?>">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                    </path>
                </svg>
                <span>Arsip</span>
            </a>

            <!-- User Management - PERBAIKAN DI SINI -->
            <?php if ($isAdmin): ?>
                <a href="<?php echo $base_url; ?>pages/users/index.php"
                    class="nav-item <?php echo (strpos($current_path, '/pages/users/') !== false) ? 'active' : ''; ?>">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                        </path>
                    </svg>
                    <span>User Management</span>
                </a>
            <?php endif; ?>

            <!-- Manajemen Template - HANYA UNTUK UMUM/ADMIN -->
            <?php if ($isUmum || $isAdmin): ?>
                <a href="<?php echo $base_url; ?>pages/template/templates.php"
                    class="nav-item <?php echo (strpos($current_path, '/pages/template/') !== false) ? 'active' : ''; ?>">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <span>Manajemen Template</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
</aside>