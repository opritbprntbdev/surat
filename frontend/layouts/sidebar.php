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
                <span class="count" id="inbox-unread-count" style="display:none">0</span>
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

            <!-- Disposisi Saya (sembunyikan untuk UMUM) -->
            <?php if (!$isUmum): ?>
                <a href="<?php echo $base_url; ?>pages/surat/my_dispositions.php"
                    class="nav-item <?php echo (strpos($current_path, '/pages/surat/my_dispositions.php') !== false) ? 'active' : ''; ?>">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Disposisi Saya</span>
                </a>
            <?php endif; ?>

            <!-- Berbintang -->
            <a href="<?php echo $base_url; ?>pages/surat/starred.php"
                class="nav-item <?php echo (strpos($current_path, '/pages/surat/starred.php') !== false) ? 'active' : ''; ?>">
                <svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
                <span>Berbintang</span>
            </a>

            <!-- Arsip -->
            <a href="<?php echo $base_url; ?>pages/surat/archive.php"
                class="nav-item <?php echo (strpos($current_path, '/pages/surat/archive.php') !== false) ? 'active' : ''; ?>">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <!-- Archive box icon (non-trash) -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7.5h18M5.25 7.5l.625 10A2.25 2.25 0 007.12 19.5h9.76a2.25 2.25 0 002.245-2.126l.625-10M9 12h6"/>
                </svg>
                <span>Arsip</span>
            </a>

            <!-- Tracking (semua role) -->
            <a href="<?php echo $base_url; ?>pages/surat/tracking.php"
                class="nav-item <?php echo (strpos($current_path, '/pages/surat/tracking.php') !== false) ? 'active' : ''; ?>">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <!-- Route/track icon -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20.25c0-1.243 1.007-2.25 2.25-2.25h1.5A3.75 3.75 0 0016.5 14.25V9.75M7.5 6.75h9M7.5 6.75A1.5 1.5 0 119 5.25a1.5 1.5 0 01-1.5 1.5zM16.5 9.75A1.5 1.5 0 1118 8.25a1.5 1.5 0 01-1.5 1.5z"/>
                </svg>
                <span>Tracking</span>
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