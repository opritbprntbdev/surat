<?php
session_start();

// Jika user belum login, tendang ke halaman login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Ambil data user dari session untuk ditampilkan
$nama_user = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'User');
$initial_user = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat App - Kotak Masuk</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📧</text></svg>">
</head>

<body>
    <div class="gmail-container">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <button id="compose-btn" class="compose-btn">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tulis Surat</span>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <button class="nav-item active" data-category="inbox">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                        <span>Kotak Masuk</span>
                        <span class="count" id="inbox-count">0</span>
                    </button>
                    <a href="../backend/api/logout.php" class="nav-item">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
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

            <!-- Email Content Area -->
            <div class="email-content">
                <!-- Panel Kiri: Daftar Surat -->
                <div class="email-list" id="email-list">
                    <!-- Daftar surat akan dimuat di sini oleh JavaScript -->
                </div>

                <!-- Panel Kanan: Detail Surat -->
                <div class="email-detail" id="email-detail">
                    <div class="email-detail-placeholder">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <p>Pilih surat untuk dibaca</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>