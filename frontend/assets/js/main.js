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
      // Tambahkan link PDF preview di header actions (opsional)
      const pdfLink = document.createElement('a');
      pdfLink.href = '../backend/api/pdf.php?id=' + encodeURIComponent(suratId);
      pdfLink.className = 'btn btn-outline';
      pdfLink.target = '_blank';
      pdfLink.textContent = 'PDF';
      const hdr = content.querySelector('.email-detail-header');
      if (hdr) {
        const act = document.createElement('div');
        act.style.marginTop = '8px';
        act.appendChild(pdfLink);
        hdr.appendChild(act);
      }
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

      // Jika role UMUM, wire tombol Disposisi
      const role = (document.body && document.body.dataset && document.body.dataset.role)
        ? document.body.dataset.role.toUpperCase()
        : 'CABANG';
      if (role === 'UMUM') {
        const btn = content.querySelector('.dispose-btn');
        if (btn) {
          btn.addEventListener('click', () => App.openDisposisiModal(suratData));
        }
      }

      // Wire up PDF and Zoom buttons
      const pdfBtn = content.querySelector('.pdf-btn');
      if (pdfBtn) {
          pdfBtn.addEventListener('click', (e) => {
              const id = e.currentTarget.dataset.suratId;
              window.open(`../backend/api/pdf.php?id=${id}`, '_blank');
          });
      }

      const zoomSelect = content.querySelector('#zoom-select');
      const letterPage = content.querySelector('.letter-page');
      const wrapper = content.querySelector('.letter-page-wrapper');

      const applyZoom = () => {
          if (!letterPage || !wrapper) return;
          const zoomLevel = zoomSelect.value;

          if (zoomLevel === 'fit') {
              letterPage.style.transform = `scale(1)`;
              const scale = wrapper.offsetWidth / letterPage.offsetWidth;
              letterPage.style.transform = `scale(${scale})`;
              letterPage.style.transformOrigin = 'top left';
          } else {
              letterPage.style.transform = `scale(${zoomLevel})`;
              letterPage.style.transformOrigin = 'top center';
          }
      };

      if (zoomSelect) {
          zoomSelect.addEventListener('change', applyZoom);
          // Use a small timeout to allow the DOM to render before calculating width
          setTimeout(() => {
              zoomSelect.value = 'fit';
              applyZoom();
          }, 50);
      }

    } catch (error) {
      detailPane.innerHTML = Components.createErrorState(
        "Gagal Memuat Detail",
        error.message,
        () => this.selectSurat(suratId)
      );
    }
  },

  openDisposisiModal(surat) {
    // Simple modal
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>Disposisi Surat</h3>
          <button class="close-btn" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="disp-to">Kepada (User)</label>
            <input type="text" id="disp-to" class="form-input" placeholder="Cari nama user..." list="disp-list" autocomplete="off" />
            <datalist id="disp-list"></datalist>
          </div>
          <div class="form-group">
            <label for="disp-note">Catatan</label>
            <textarea id="disp-note" class="form-textarea" placeholder="Opsional"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn" id="disp-cancel">Batal</button>
          <button class="btn btn-primary" id="disp-send">Kirim</button>
        </div>
      </div>`;

  document.body.appendChild(modal);
  modal.style.display = 'block';

    const close = () => { if (modal && modal.parentNode) modal.parentNode.removeChild(modal); };
    modal.querySelector('.close-btn').addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    modal.querySelector('#disp-cancel').addEventListener('click', close);

    // Typeahead users
    const input = modal.querySelector('#disp-to');
    const list = modal.querySelector('#disp-list');
    let lastQ = ''; let t = null;
    input.addEventListener('input', () => {
      const q = input.value.trim();
      if (q === lastQ) return; lastQ = q;
      if (t) clearTimeout(t);
      t = setTimeout(async () => {
        if (q.length < 2) { list.innerHTML = ''; return; }
        try {
          const res = await fetch('../backend/api/recipients.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
          const json = await res.json();
          list.innerHTML = '';
          (json.data || []).filter(x => x.type === 'USER').forEach(x => {
            const opt = document.createElement('option');
            opt.value = x.label;
            opt.dataset.id = x.id;
            list.appendChild(opt);
          });
        } catch {}
      }, 250);
    });

    const resolveUserId = () => {
      const val = input.value.trim();
      const opt = Array.from(list.options).find(o => o.value === val);
      return opt ? parseInt(opt.dataset.id, 10) : NaN;
    };

    modal.querySelector('#disp-send').addEventListener('click', async () => {
      const userId = resolveUserId();
      if (!userId || Number.isNaN(userId)) { alert('Pilih penerima dari daftar.'); return; }
      const note = modal.querySelector('#disp-note').value.trim();
      try {
        const resp = await fetch('../backend/api/disposisi.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin',
          body: JSON.stringify({ surat_id: surat.id, user_id: userId, note })
        });
        const json = await resp.json();
        if (!resp.ok || json.success === false) throw new Error(json.message || 'Gagal');
        close();
        // Refresh list and detail
        App.loadSurat();
        // Force clear detail
        const detailPane = Utils.$('#email-detail');
        if (detailPane) detailPane.innerHTML = Components.createEmptyState('Berhasil', 'Surat telah didisposisi.');
      } catch (err) {
        alert(err.message || 'Gagal mengirim disposisi');
      }
    });
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
