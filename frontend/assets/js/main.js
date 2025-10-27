const App = {
  state: {
    suratList: [],
    currentSuratId: null,
  },

  init() {
    this.bindEvents();
    this.loadSurat();
  },

  bindEvents() {
    const emailList = Utils.$("#email-list");
    if (emailList) {
      emailList.addEventListener("click", (e) => {
        const item = e.target.closest(".email-item");
        if (item && item.dataset.suratId) {
          this.selectSurat(item.dataset.suratId);
        }
      });
    }

    const refreshBtn = Utils.$("#refresh-btn");
    if (refreshBtn) {
      refreshBtn.addEventListener("click", () => this.loadSurat());
    }
  },

  async loadSurat() {
    const emailList = Utils.$("#email-list");
    if (!emailList) return;
    emailList.innerHTML = Components.createLoadingState("Memuat surat...");
    try {
      const response = await API.getSuratList();
      this.state.suratList = response.data.data;
      this.renderSuratList();
    } catch (error) {
      emailList.innerHTML = Components.createErrorState(
        "Gagal Memuat Surat",
        error.message,
        () => this.loadSurat()
      );
    }
  },

  renderSuratList() {
    const emailList = Utils.$("#email-list");
    if (!emailList) return;
    const suratToRender = this.state.suratList;

    if (!suratToRender || suratToRender.length === 0) {
      emailList.innerHTML = Components.createEmptyState(
        "Tidak ada surat",
        "Kotak masuk Anda kosong."
      );
      return;
    }

    emailList.innerHTML = "";
    suratToRender.forEach((surat) => {
      const suratItem = Components.createSuratItem(surat);
      if (surat.id == this.state.currentSuratId) {
        suratItem.classList.add("selected");
      }
      emailList.appendChild(suratItem);
    });
  },

  async selectSurat(suratId) {
    if (this.state.currentSuratId === suratId) return;

    this.state.currentSuratId = suratId;
    this.updateSelectedUI();

    const detailPane = Utils.$("#email-detail");
    if (!detailPane) return;
    detailPane.innerHTML = Components.createLoadingState(
      "Memuat detail surat..."
    );

    try {
      const response = await API.getSuratDetail(suratId);
      const suratData = response.data;
      detailPane.innerHTML = "";
      const content = Components.createSuratDetail(suratData);
      // Tambah tombol back untuk mobile
      if (window.innerWidth <= 767) {
        const header = content.querySelector(".email-detail-header");
        if (header) {
          const backBtn = document.createElement("button");
          backBtn.className = "mobile-back-btn";
          backBtn.innerHTML =
            '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>';
          backBtn.addEventListener("click", () => {
            detailPane.classList.remove("active");
          });
          header.prepend(backBtn);
        }
        detailPane.classList.add("active");
      }
      detailPane.appendChild(content);
    } catch (error) {
      detailPane.innerHTML = Components.createErrorState(
        "Gagal Memuat Detail",
        error.message,
        () => this.selectSurat(suratId)
      );
    }
  },

  updateSelectedUI() {
    const items = Utils.$$(".email-item");
    items.forEach((item) => {
      if (item.dataset.suratId == this.state.currentSuratId) {
        item.classList.add("selected");
      } else {
        item.classList.remove("selected");
      }
    });
  },
};

document.addEventListener("DOMContentLoaded", () => {
  // Jalankan aplikasi utama
  App.init();

  // --- LOGIKA DROPDOWN ---
  const avatarBtn = Utils.$("#user-avatar-btn");
  const dropdown = Utils.$("#user-dropdown");

  if (avatarBtn && dropdown) {
    // Hapus class 'hidden' dari awal jika ada, agar CSS bisa mengontrolnya
    dropdown.classList.remove("hidden");

    avatarBtn.addEventListener("click", (event) => {
      event.stopPropagation(); // Mencegah event "klik di luar" tertrigger
      dropdown.classList.toggle("show"); // Cukup toggle class 'show'
    });

    // Menutup dropdown jika user mengklik di luar area dropdown
    window.addEventListener("click", (event) => {
      if (
        dropdown.classList.contains("show") &&
        !dropdown.contains(event.target) &&
        !avatarBtn.contains(event.target)
      ) {
        dropdown.classList.remove("show");
      }
    });
  }

  // --- SIDEBAR MOBILE TOGGLE ---
  const sidebar = document.getElementById("sidebar");
  const mobileBtn = document.getElementById("mobile-menu-btn");
  let overlayEl = null;

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add("open");
    if (!overlayEl) {
      overlayEl = document.createElement("div");
      overlayEl.className = "sidebar-overlay";
      overlayEl.addEventListener("click", closeSidebar);
    }
    document.body.appendChild(overlayEl);
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove("open");
    if (overlayEl && overlayEl.parentNode) {
      overlayEl.parentNode.removeChild(overlayEl);
    }
  }

  if (mobileBtn && sidebar) {
    mobileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      if (sidebar.classList.contains("open")) closeSidebar();
      else openSidebar();
    });
  }

  // Close on ESC
  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeSidebar();
  });
});
