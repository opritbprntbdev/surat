// Minimal User Management (CRUD + Reset Password)
(function () {
  // Use root-relative path to avoid issues with nested pages
  const apiBase = "/surat/backend/api";

  const state = {
    page: 1,
    limit: 10,
    totalPages: 1,
    search: "",
    role: "",
  };

  const qs = (sel) => document.querySelector(sel);
  const byId = (id) => document.getElementById(id);
  const notify = (msg) => window.alert(msg);

  function formatDate(d) {
    if (!d) return "-";
    const dt = new Date(d);
    return dt.toLocaleString("id-ID", {
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  async function fetchJSON(url, options = {}) {
    try {
      const res = await fetch(url, options);
      const contentType = res.headers.get("content-type") || "";
      const text = await res.text();
      let json = null;
      if (contentType.includes("application/json")) {
        try {
          json = JSON.parse(text);
        } catch (_) {
          json = null;
        }
      }
      if (!json) {
        return { success: false, message: `HTTP ${res.status}` };
      }
      if (!res.ok) {
        return {
          success: false,
          message: json.message || json.error || `HTTP ${res.status}`,
        };
      }
      return json;
    } catch (e) {
      return { success: false, message: e.message || "Network error" };
    }
  }

  async function loadUsers() {
    const params = new URLSearchParams({
      page: String(state.page),
      limit: String(state.limit),
    });
    if (state.search) params.set("search", state.search);
    if (state.role) params.set("role", state.role);

    const data = await fetchJSON(`${apiBase}/user.php?${params.toString()}`);
    if (!data.success) {
      notify(data.error || data.message || "Gagal memuat data");
      renderTable([]);
      renderPagination(1, 1, 0);
      return;
    }

    const users = data.data.users || [];
    const p = data.data.pagination || {
      current_page: 1,
      total_pages: 1,
      total_users: users.length,
      per_page: state.limit,
    };
    state.totalPages = p.total_pages || 1;
    renderTable(users);
    renderPagination(p.current_page, p.total_pages, p.total_users);
  }

  function renderTable(users) {
    const tbody = byId("usersTableBody");
    if (!users.length) {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada data</td></tr>`;
      return;
    }
    tbody.innerHTML = users
      .map(
        (u) => `
        <tr>
          <td>${u.id}</td>
          <td>
            <div class="user-info">
              <div class="user-avatar">${(u.nama_lengkap || "?")
                .charAt(0)
                .toUpperCase()}</div>
              <span>${u.username}</span>
            </div>
          </td>
          <td>${u.nama_lengkap || "-"}</td>
          <td><span class="badge">${(u.role || "-").toUpperCase()}</span></td>
          <td>${formatDate(u.created_at)}</td>
          <td>
            <div class="action-buttons">
              <button class="btn btn-sm btn-outline" title="Edit" onclick="userManager.editUser(${
                u.id
              })">Edit</button>
              <button class="btn btn-sm btn-warning" title="Reset Password" onclick="userManager.resetUserPassword(${
                u.id
              }, '${(u.nama_lengkap || "").replace(/'/g, "'")}')">Reset</button>
              <button class="btn btn-sm btn-danger" title="Nonaktifkan" onclick="userManager.confirmDeleteUser(${
                u.id
              }, '${(u.nama_lengkap || "").replace(/'/g, "'")}')">Hapus</button>
            </div>
          </td>
        </tr>`
      )
      .join("");
  }

  function renderPagination(current, total, totalUsers) {
    const info = byId("paginationInfo");
    const start = totalUsers === 0 ? 0 : (current - 1) * state.limit + 1;
    const end = Math.min(current * state.limit, totalUsers);
    info.textContent = `Menampilkan ${start}-${end} dari ${totalUsers} user`;

    const el = byId("pagination");
    if (total <= 1) {
      el.innerHTML = "";
      return;
    }
    let html = "";
    if (current > 1)
      html += `<button class="pagination-btn" data-page="${
        current - 1
      }">‹</button>`;
    for (let i = 1; i <= total; i++) {
      if (i === current)
        html += `<button class="pagination-btn active">${i}</button>`;
      else
        html += `<button class="pagination-btn" data-page="${i}">${i}</button>`;
    }
    if (current < total)
      html += `<button class="pagination-btn" data-page="${
        current + 1
      }">›</button>`;
    el.innerHTML = html;

    el.querySelectorAll("button[data-page]").forEach((btn) => {
      btn.addEventListener("click", () => {
        state.page = parseInt(btn.getAttribute("data-page"), 10);
        loadUsers();
      });
    });
  }

  // CRUD + Reset
  async function editUser(id) {
    const data = await fetchJSON(`${apiBase}/user.php?id=${id}`);
    if (!data.success) return notify(data.error || "Gagal memuat user");
    const u = data.data;
    byId("modalTitle").textContent = "Edit User";
    byId("submitBtn").textContent = "Update User";
    byId("userId").value = u.id;
    byId("username").value = u.username || "";
    byId("namaLengkap").value = u.nama_lengkap || "";
    byId("role").value = u.role || "";
    // hide password on edit
    byId("passwordGroup").style.display = "none";
    byId("confirmPasswordGroup").style.display = "none";
    byId("password").required = false;
    byId("confirmPassword").required = false;
    clearErrors();
    byId("userModal").style.display = "flex";
    document.body.classList.add('modal-open');
  }

  function openAddUserModal() {
    byId("modalTitle").textContent = "Tambah User";
    byId("submitBtn").textContent = "Tambah User";
    byId("userId").value = "";
    byId("userForm").reset();
    byId("passwordGroup").style.display = "block";
    byId("confirmPasswordGroup").style.display = "block";
    byId("password").required = true;
    byId("confirmPassword").required = true;
    clearErrors();
    byId("userModal").style.display = "flex";
    document.body.classList.add('modal-open');
  }

  function closeUserModal() {
    byId("userModal").style.display = "none";
    byId("userForm").reset();
    clearErrors();
    document.body.classList.remove('modal-open');
  }

  function closeResetPasswordModal() {
    byId("resetPasswordModal").style.display = "none";
    byId("resetPasswordForm").reset();
    clearErrors();
    document.body.classList.remove('modal-open');
  }

  function closeDeleteModal() {
    byId("deleteModal").style.display = "none";
    document.body.classList.remove('modal-open');
  }

  function clearErrors() {
    document.querySelectorAll(".error-message").forEach((e) => {
      e.textContent = "";
      e.style.display = "none";
    });
  }

  function showError(id, msg) {
    const el = byId(id);
    if (el) {
      el.textContent = msg;
      el.style.display = "block";
    }
  }

  function validateUserForm(isEdit) {
    let ok = true;
    clearErrors();
    const username = byId("username").value.trim();
    const nama = byId("namaLengkap").value.trim();
    const role = byId("role").value;
    const pwd = byId("password").value;
    const cpwd = byId("confirmPassword").value;
    if (!username || username.length < 3) {
      showError("usernameError", "Username minimal 3 karakter");
      ok = false;
    }
    if (!nama) {
      showError("namaLengkapError", "Nama lengkap wajib diisi");
      ok = false;
    }
    if (!role) {
      showError("roleError", "Role wajib dipilih");
      ok = false;
    }
    if (!isEdit) {
      if (!pwd || pwd.length < 6) {
        showError("passwordError", "Password minimal 6 karakter");
        ok = false;
      }
      if (pwd !== cpwd) {
        showError("confirmPasswordError", "Konfirmasi password tidak cocok");
        ok = false;
      }
    }
    return ok;
  }

  async function submitUser(e) {
    e.preventDefault();
    const id = byId("userId").value;
    const isEdit = !!id;
    if (!validateUserForm(isEdit)) return;
    const payload = {
      username: byId("username").value.trim(),
      nama_lengkap: byId("namaLengkap").value.trim(),
      role: byId("role").value,
    };
    if (!isEdit) payload.password = byId("password").value;

    const url = isEdit ? `${apiBase}/user.php?id=${id}` : `${apiBase}/user.php`;
    const method = isEdit ? "PUT" : "POST";
    const data = await fetchJSON(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    if (!data.success) {
      if (data.errors) {
        Object.entries(data.errors).forEach(([k, v]) => {
          let map;
          if (k === "nama_lengkap") map = "namaLengkapError";
          else if (k === "role" || k === "roleError") map = "roleError";
          else if (k === "password" || k === "passwordError")
            map = "passwordError";
          else if (k === "username") map = "usernameError";
          else map = k + "Error";
          showError(map, v);
        });
      } else notify(data.error || data.message || "Gagal menyimpan");
      return;
    }
    notify(isEdit ? "User diupdate" : "User ditambahkan");
    closeUserModal();
    loadUsers();
  }

  function resetUserPassword(userId, nama) {
    byId("resetUserId").value = userId;
    byId("resetUserName").textContent = nama || "";
    byId("resetPasswordForm").reset();
    clearErrors();
    byId("resetPasswordModal").style.display = "flex";
    document.body.classList.add('modal-open');
  }

  function validateResetPasswordForm() {
    clearErrors();
    const pwd = byId("newPassword").value;
    const cpwd = byId("confirmNewPassword").value;
    let ok = true;
    if (!pwd || pwd.length < 6) {
      showError("newPasswordError", "Password minimal 6 karakter");
      ok = false;
    }
    if (pwd !== cpwd) {
      showError("confirmNewPasswordError", "Konfirmasi tidak cocok");
      ok = false;
    }
    return ok;
  }

  async function submitResetPassword(e) {
    e.preventDefault();
    if (!validateResetPasswordForm()) return;
    const userId = byId("resetUserId").value;
    const newPwd = byId("newPassword").value;
    const data = await fetchJSON(`${apiBase}/reset_password.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "change_password",
        user_id: userId,
        new_password: newPwd,
      }),
    });
    if (!data.success)
      return notify(data.error || data.message || "Reset gagal");
    notify("Password direset");
    closeResetPasswordModal();
  }

  function confirmDeleteUser(id, nama) {
    byId("deleteUserName").textContent = nama || "";
    byId("confirmDeleteBtn").onclick = () => deleteUser(id);
    byId("deleteModal").style.display = "flex";
    document.body.classList.add('modal-open');
  }

  async function deleteUser(id) {
    const data = await fetchJSON(`${apiBase}/user.php?id=${id}`, {
      method: "DELETE",
    });
    if (!data.success)
      return notify(data.error || data.message || "Hapus gagal");
    notify("User dinonaktifkan");
    closeDeleteModal();
    loadUsers();
  }

  function wireEvents() {
    // Search debounce
    let t;
    byId("searchInput").addEventListener("input", (e) => {
      clearTimeout(t);
      t = setTimeout(() => {
        state.search = (e.target.value || "").trim();
        state.page = 1;
        loadUsers();
      }, 300);
    });
    // Role filter
    byId("roleFilter").addEventListener("change", (e) => {
      state.role = e.target.value || "";
      state.page = 1;
      loadUsers();
    });
    // Forms
    byId("userForm").addEventListener("submit", submitUser);
    byId("resetPasswordForm").addEventListener("submit", submitResetPassword);
    // Close modals when clicking outside
    window.addEventListener("click", (e) => {
      if (e.target.classList && e.target.classList.contains("modal")) {
        closeUserModal();
        closeResetPasswordModal();
        closeDeleteModal();
      }
    });
  }

  // Expose for inline onclick in HTML
  window.userManager = {
    editUser,
    openAddUserModal,
    closeUserModal,
    closeResetPasswordModal,
    closeDeleteModal,
    confirmDeleteUser,
    resetUserPassword,
  };

  // Also expose openAddUserModal used by button
  window.openAddUserModal = openAddUserModal;
  // Compatibility for inline onchange="filterUsers()" in select
  window.filterUsers = function () {
    const sel = document.getElementById("roleFilter");
    if (sel) {
      state.role = sel.value || "";
      state.page = 1;
      loadUsers();
    }
  };

  document.addEventListener("DOMContentLoaded", () => {
    wireEvents();
    loadUsers();
  });
})();
