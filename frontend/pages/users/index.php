<?php require_once '../../layouts/header.php'; ?>
<?php require_once '../../layouts/sidebar.php'; ?>

<main class="main-content">
    <div class="page-content">
        <div class="content-header">
            <div class="header-left">
                <button id="mobile-menu-btn" class="mobile-menu-btn" title="Menu">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1>Manajemen User</h1>
                <p>Kelola pengguna sistem aplikasi surat</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah User
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="filters">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Cari nama atau username..."
                            class="form-control">
                    </div>
                    <div class="filter-group">
                        <select id="roleFilter" class="form-control" onchange="filterUsers()">
                            <option value="">Semua Role</option>
                            <option value="ADMIN">Admin</option>
                            <option value="UMUM">Umum</option>
                            <option value="DIREKSI">Direksi</option>
                            <option value="DIVISI">Divisi</option>
                            <option value="SUB_DIVISI">Sub Divisi</option>
                            <option value="CABANG">Cabang</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- Data akan dimuat via JavaScript -->
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <div class="pagination-info">
                    <span id="paginationInfo">Menampilkan 0 dari 0 user</span>
                </div>
                <div class="pagination" id="pagination">
                    <!-- Pagination akan dimuat via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</main>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Tambah User</h3>
            <span class="close" onclick="userManager.closeUserModal()">&times;</span>
        </div>
        <form id="userForm">
            <input type="hidden" id="userId" name="userId">

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" required>
                <div id="usernameError" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="namaLengkap">Nama Lengkap *</label>
                <input type="text" id="namaLengkap" name="nama_lengkap" class="form-control" required>
                <div id="namaLengkapError" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Pilih Role</option>
                    <option value="ADMIN">Admin</option>
                    <option value="UMUM">Umum</option>
                    <option value="DIREKSI">Direksi</option>
                    <option value="DIVISI">Divisi</option>
                    <option value="SUB_DIVISI">Sub Divisi</option>
                    <option value="CABANG">Cabang</option>
                </select>
                <div id="roleError" class="error-message"></div>
            </div>

            <div class="form-group" id="passwordGroup">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control">
                <div id="passwordError" class="error-message"></div>
            </div>

            <div class="form-group" id="confirmPasswordGroup">
                <label for="confirmPassword">Konfirmasi Password *</label>
                <input type="password" id="confirmPassword" name="confirm_password" class="form-control">
                <div id="confirmPasswordError" class="error-message"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="userManager.closeUserModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Tambah User</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Reset Password</h3>
            <span class="close" onclick="userManager.closeResetPasswordModal()">&times;</span>
        </div>
        <form id="resetPasswordForm">
            <input type="hidden" id="resetUserId">

            <div class="form-group">
                <p>Reset password untuk: <strong id="resetUserName"></strong></p>
            </div>

            <div class="form-group">
                <label for="newPassword">Password Baru *</label>
                <input type="password" id="newPassword" name="new_password" class="form-control" required>
                <div id="newPasswordError" class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="confirmNewPassword">Konfirmasi Password Baru *</label>
                <input type="password" id="confirmNewPassword" name="confirm_new_password" class="form-control"
                    required>
                <div id="confirmNewPasswordError" class="error-message"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    onclick="userManager.closeResetPasswordModal()">Batal</button>
                <button type="submit" class="btn btn-warning">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Konfirmasi Hapus</h3>
            <span class="close" onclick="userManager.closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menonaktifkan user <strong id="deleteUserName"></strong>?</p>
            <p class="text-danger">Tindakan ini akan menonaktifkan user dan tidak bisa login lagi.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="userManager.closeDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Nonaktifkan</button>
        </div>
    </div>
</div>

<script src="../../assets/js/users.js"></script>

<?php require_once '../../layouts/footer.php'; ?>