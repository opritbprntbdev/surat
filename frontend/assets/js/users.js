// User Management JavaScript
class UserManager {
    constructor() {
        this.currentPage = 1;
        this.perPage = 10;
        this.totalPages = 1;
        this.users = [];
        this.filteredUsers = [];
        this.currentFilter = '';
        this.currentSearch = '';
        
        this.init();
    }
    
    init() {
        this.loadUsers();
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Form submissions
        document.getElementById('userForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleUserSubmit();
        });
        
        document.getElementById('resetPasswordForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleResetPassword();
        });
        
        // Modal close events
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });
        
        // Search input debounce
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.currentSearch = e.target.value.toLowerCase();
                this.filterAndDisplayUsers();
            }, 300);
        });
    }
    
    async loadUsers() {
        try {
            showLoadingOverlay();
            
            const response = await fetch('../../backend/api/user.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.users = data.data.users || [];
                this.filteredUsers = [...this.users];
                this.displayUsers();
                this.updatePagination(data.data.pagination || {});
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            showNotification('Gagal memuat data user', 'error');
        } finally {
            hideLoadingOverlay();
        }
    }
    
    filterAndDisplayUsers() {
        this.filteredUsers = this.users.filter(user => {
            const matchSearch = !this.currentSearch || 
                user.username.toLowerCase().includes(this.currentSearch) ||
                user.nama_lengkap.toLowerCase().includes(this.currentSearch) ||
                user.email.toLowerCase().includes(this.currentSearch);
                
            const matchFilter = !this.currentFilter || user.role === this.currentFilter;
            
            return matchSearch && matchFilter;
        });
        
        this.displayUsers();
        this.updatePaginationInfo();
    }
    
    displayUsers() {
        const tableBody = document.getElementById('usersTableBody');
        const startIndex = (this.currentPage - 1) * this.perPage;
        const endIndex = startIndex + this.perPage;
        const pageUsers = this.filteredUsers.slice(startIndex, endIndex);
        
        if (pageUsers.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="empty-state">
                            <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p>Tidak ada user yang ditemukan</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = pageUsers.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>
                    <div class="user-info">
                        <div class="user-avatar">${user.nama_lengkap.charAt(0).toUpperCase()}</div>
                        <span>${user.username}</span>
                    </div>
                </td>
                <td>${user.nama_lengkap}</td>
                <td>${user.email}</td>
                <td>
                    <span class="badge badge-${user.role === 'admin' ? 'primary' : 'secondary'}">
                        ${user.role.toUpperCase()}
                    </span>
                </td>
                <td>${this.formatDate(user.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline" onclick="userManager.editUser(${user.id})" title="Edit">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="userManager.resetUserPassword(${user.id}, '${user.nama_lengkap}')" title="Reset Password">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-2-2a2 2 0 00-2 2m2-2h2a2 2 0 012 2v1a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2h2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v2M9 7h6"></path>
                            </svg>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="userManager.confirmDeleteUser(${user.id}, '${user.nama_lengkap}')" title="Hapus">
                            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    updatePagination(paginationData) {
        if (paginationData.total_pages) {
            this.totalPages = paginationData.total_pages;
        } else {
            this.totalPages = Math.ceil(this.filteredUsers.length / this.perPage);
        }
        
        this.updatePaginationInfo();
        this.renderPagination();
    }
    
    updatePaginationInfo() {
        const total = this.filteredUsers.length;
        const start = total === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
        const end = Math.min(this.currentPage * this.perPage, total);
        
        document.getElementById('paginationInfo').textContent = 
            `Menampilkan ${start}-${end} dari ${total} user`;
    }
    
    renderPagination() {
        const paginationEl = document.getElementById('pagination');
        
        if (this.totalPages <= 1) {
            paginationEl.innerHTML = '';
            return;
        }
        
        let paginationHTML = '';
        
        // Previous button
        if (this.currentPage > 1) {
            paginationHTML += `<button class="pagination-btn" onclick="userManager.goToPage(${this.currentPage - 1})">‹</button>`;
        }
        
        // Page numbers
        for (let i = 1; i <= this.totalPages; i++) {
            if (i === this.currentPage) {
                paginationHTML += `<button class="pagination-btn active">${i}</button>`;
            } else if (i === 1 || i === this.totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                paginationHTML += `<button class="pagination-btn" onclick="userManager.goToPage(${i})">${i}</button>`;
            } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                paginationHTML += `<span class="pagination-dots">...</span>`;
            }
        }
        
        // Next button
        if (this.currentPage < this.totalPages) {
            paginationHTML += `<button class="pagination-btn" onclick="userManager.goToPage(${this.currentPage + 1})">›</button>`;
        }
        
        paginationEl.innerHTML = paginationHTML;
    }
    
    goToPage(page) {
        this.currentPage = page;
        this.displayUsers();
        this.renderPagination();
        this.updatePaginationInfo();
    }
    
    // User CRUD Operations
    openAddUserModal() {
        document.getElementById('modalTitle').textContent = 'Tambah User';
        document.getElementById('submitBtn').textContent = 'Tambah User';
        document.getElementById('userId').value = '';
        document.getElementById('userForm').reset();
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('confirmPasswordGroup').style.display = 'block';
        document.getElementById('password').required = true;
        document.getElementById('confirmPassword').required = true;
        this.clearErrors();
        document.getElementById('userModal').style.display = 'block';
    }
    
    async editUser(userId) {
        try {
            showLoadingOverlay();
            
            const response = await fetch(`../../backend/api/user.php?id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                const user = data.data;
                
                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('submitBtn').textContent = 'Update User';
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('namaLengkap').value = user.nama_lengkap;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                
                // Hide password fields for edit
                document.getElementById('passwordGroup').style.display = 'none';
                document.getElementById('confirmPasswordGroup').style.display = 'none';
                document.getElementById('password').required = false;
                document.getElementById('confirmPassword').required = false;
                
                this.clearErrors();
                document.getElementById('userModal').style.display = 'block';
            } else {
                showNotification('Gagal memuat data user', 'error');
            }
        } catch (error) {
            console.error('Error loading user:', error);
            showNotification('Gagal memuat data user', 'error');
        } finally {
            hideLoadingOverlay();
        }
    }
    
    async handleUserSubmit() {
        const formData = new FormData(document.getElementById('userForm'));
        const userId = document.getElementById('userId').value;
        const isEdit = !!userId;
        
        // Validation
        if (!this.validateUserForm(isEdit)) {
            return;
        }
        
        try {
            showLoadingOverlay();
            
            const method = isEdit ? 'PUT' : 'POST';
            const url = isEdit ? `../../backend/api/user.php?id=${userId}` : '../../backend/api/user.php';
            
            const userData = {};
            for (let [key, value] of formData.entries()) {
                if (key !== 'confirm_password' && value.trim() !== '') {
                    userData[key] = value;
                }
            }
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(isEdit ? 'User berhasil diupdate' : 'User berhasil ditambahkan', 'success');
                this.closeUserModal();
                this.loadUsers();
            } else {
                if (data.errors) {
                    this.displayErrors(data.errors);
                } else {
                    showNotification('Error: ' + (data.error || data.message), 'error');
                }
            }
        } catch (error) {
            console.error('Error saving user:', error);
            showNotification('Gagal menyimpan data user', 'error');
        } finally {
            hideLoadingOverlay();
        }
    }
    
    resetUserPassword(userId, userName) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetUserName').textContent = userName;
        document.getElementById('resetPasswordForm').reset();
        this.clearErrors();
        document.getElementById('resetPasswordModal').style.display = 'block';
    }
    
    async handleResetPassword() {
        const userId = document.getElementById('resetUserId').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        // Validation
        if (!this.validateResetPasswordForm()) {
            return;
        }
        
        try {
            showLoadingOverlay();
            
            const response = await fetch('../../backend/api/reset_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'change_password',
                    user_id: userId,
                    new_password: newPassword
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('Password berhasil direset', 'success');
                this.closeResetPasswordModal();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error resetting password:', error);
            showNotification('Gagal mereset password', 'error');
        } finally {
            hideLoadingOverlay();
        }
    }
    
    confirmDeleteUser(userId, userName) {
        document.getElementById('deleteUserName').textContent = userName;
        document.getElementById('confirmDeleteBtn').onclick = () => this.deleteUser(userId);
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    async deleteUser(userId) {
        try {
            showLoadingOverlay();
            
            const response = await fetch(`../../backend/api/user.php?id=${userId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification('User berhasil dihapus', 'success');
                this.closeDeleteModal();
                this.loadUsers();
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            showNotification('Gagal menghapus user', 'error');
        } finally {
            hideLoadingOverlay();
        }
    }
    
    // Validation functions
    validateUserForm(isEdit) {
        let isValid = true;
        this.clearErrors();
        
        const username = document.getElementById('username').value.trim();
        const namaLengkap = document.getElementById('namaLengkap').value.trim();
        const email = document.getElementById('email').value.trim();
        const role = document.getElementById('role').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (!username) {
            this.showError('usernameError', 'Username wajib diisi');
            isValid = false;
        } else if (username.length < 3) {
            this.showError('usernameError', 'Username minimal 3 karakter');
            isValid = false;
        }
        
        if (!namaLengkap) {
            this.showError('namaLengkapError', 'Nama lengkap wajib diisi');
            isValid = false;
        }
        
        if (!email) {
            this.showError('emailError', 'Email wajib diisi');
            isValid = false;
        } else if (!this.isValidEmail(email)) {
            this.showError('emailError', 'Format email tidak valid');
            isValid = false;
        }
        
        if (!role) {
            this.showError('roleError', 'Role wajib dipilih');
            isValid = false;
        }
        
        if (!isEdit) {
            if (!password) {
                this.showError('passwordError', 'Password wajib diisi');
                isValid = false;
            } else if (password.length < 6) {
                this.showError('passwordError', 'Password minimal 6 karakter');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                this.showError('confirmPasswordError', 'Konfirmasi password tidak cocok');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    validateResetPasswordForm() {
        let isValid = true;
        this.clearErrors();
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmNewPassword').value;
        
        if (!newPassword) {
            this.showError('newPasswordError', 'Password baru wajib diisi');
            isValid = false;
        } else if (newPassword.length < 6) {
            this.showError('newPasswordError', 'Password minimal 6 karakter');
            isValid = false;
        }
        
        if (newPassword !== confirmPassword) {
            this.showError('confirmNewPasswordError', 'Konfirmasi password tidak cocok');
            isValid = false;
        }
        
        return isValid;
    }
    
    // Utility functions
    showError(elementId, message) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }
    
    clearErrors() {
        const errorElements = document.querySelectorAll('.error-message');
        errorElements.forEach(element => {
            element.textContent = '';
            element.style.display = 'none';
        });
    }
    
    displayErrors(errors) {
        for (const [field, message] of Object.entries(errors)) {
            let errorElementId;
            // Map field names to error element IDs
            switch(field) {
                case 'nama_lengkap':
                    errorElementId = 'namaLengkapError';
                    break;
                default:
                    errorElementId = field + 'Error';
            }
            this.showError(errorElementId, message);
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Modal functions
    closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
        document.getElementById('userForm').reset();
        this.clearErrors();
    }
    
    closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
        document.getElementById('resetPasswordForm').reset();
        this.clearErrors();
    }
    
    closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    closeAllModals() {
        this.closeUserModal();
        this.closeResetPasswordModal();
        this.closeDeleteModal();
    }
}

// Global functions for onclick handlers
function openAddUserModal() {
    userManager.openAddUserModal();
}

function searchUsers() {
    // Handled by event listener in UserManager
}

function filterUsers() {
    const roleFilter = document.getElementById('roleFilter').value;
    userManager.currentFilter = roleFilter;
    userManager.filterAndDisplayUsers();
}

// Initialize when DOM is loaded
let userManager;
document.addEventListener('DOMContentLoaded', function() {
    userManager = new UserManager();
});