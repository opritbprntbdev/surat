const App = {
  state: {
    suratList: [],
    currentSuratId: null,
    filterMyUnanswered: false,
    filterFacet: "ALL", // ALL, CABANG, DIREKSI, DIVISI, PIMSUBDIV
    filterRead: "ALL", // ALL, READ, UNREAD, STARRED, UNSTARRED
    searchQuery: "", // free text filter for inbox
    page: 1,
    pageSize: 50,
    total: 0,
  },

  init() {
    // Apply persisted page size preference
    const savedSize = parseInt(
      localStorage.getItem("SURAT_PAGE_SIZE") || "",
      10
    );
    if (!isNaN(savedSize) && [20, 50, 100].includes(savedSize)) {
      this.state.pageSize = savedSize;
    }
    this.bindEvents();
    this.loadSurat();
    // Refresh sidebar unread badge on load
    this.refreshUnreadBadge();
  },

  bindEvents() {
    const emailList = Utils.$("#email-list");
    const isTrackingPage =
      document.body && document.body.dataset && document.body.dataset.page === "tracking";
    if (emailList && !isTrackingPage) {
      emailList.addEventListener("click", (e) => {
        // Toggle star without opening detail
        const starBtn = e.target.closest && e.target.closest(".email-star");
        if (starBtn) {
          e.stopPropagation();
          const suratId = Number(starBtn.dataset.suratId);
          const isStarred = starBtn.classList.contains("starred");
          App.toggleStar(suratId, !isStarred, starBtn);
          return;
        }
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

    const filterBtn = Utils.$("#filter-unanswered-btn");
    if (filterBtn) {
      filterBtn.addEventListener("click", () => {
        this.state.filterMyUnanswered = !this.state.filterMyUnanswered;
        filterBtn.classList.toggle("active", this.state.filterMyUnanswered);
        filterBtn.title = this.state.filterMyUnanswered
          ? "Menampilkan surat yang belum Anda jawab"
          : "Tampilkan yang belum dijawab";
        this.loadSurat();
      });
    }

    // Quick filter tabs (facet)
    const facetTabs = Utils.$$("#quick-filters .tab");
    if (facetTabs && facetTabs.length) {
      facetTabs.forEach((btn) => {
        btn.addEventListener("click", (e) => {
          const val = (e.currentTarget.dataset.facet || "ALL").toUpperCase();
          this.state.filterFacet = val;
          // toggle active class
          facetTabs.forEach((b) =>
            b.classList.toggle("active", b === e.currentTarget)
          );
          // Reload with server-side filter
          this.state.page = 1;
          this.loadSurat();
        });
      });
    }

    // Read/star filter
    const readSel = Utils.$("#read-filter-select");
    if (readSel) {
      readSel.addEventListener("change", (e) => {
        this.state.filterRead = (e.target.value || "ALL").toUpperCase();
        this.state.page = 1;
        this.loadSurat();
      });
    }

    // Search input (debounced) – now call server-side search
    const searchInput = Utils.$("#search-input");
    if (searchInput) {
      const apply = Utils.debounce(() => {
        this.state.searchQuery = (searchInput.value || "").trim();
        this.state.page = 1;
        this.loadSurat();
      }, 200);
      ["input", "keyup", "change"].forEach((evt) =>
        searchInput.addEventListener(evt, apply)
      );
    }
  },

  async refreshUnreadBadge() {
    try {
      const el = document.getElementById("inbox-unread-count");
      if (!el) return;
      const res = await API.getUnreadCount();
      const n =
        res && res.data && typeof res.data.unread !== "undefined"
          ? Number(res.data.unread)
          : 0;
      if (n > 0) {
        el.textContent = String(n);
        el.style.display = "inline-block";
      } else {
        el.textContent = "0";
        el.style.display = "none";
      }
    } catch (e) {
      // non-blocking
    }
  },

  async loadSurat() {
    const emailList = Utils.$("#email-list");
    if (!emailList) return;
    emailList.innerHTML = Components.createLoadingState("Memuat surat...");
    try {
      const params = {
        page: this.state.page,
        page_size: this.state.pageSize,
        facet: this.state.filterFacet,
        read: this.state.filterRead,
        q: this.state.searchQuery,
      };
      if (this.state.filterMyUnanswered) params.my_unanswered = 1;
      const response = await API.getSuratList(params);
      this.state.suratList = response.data.data;
      this.state.total = Number(response.data.total || 0);
      this.renderSuratList();
      this.updatePager();
    } catch (error) {
      emailList.innerHTML = Components.createErrorState(
        "Gagal Memuat Surat",
        error.message,
        () => this.loadSurat()
      );
    }
  },

  updatePager() {
    const pager = document.getElementById("pager");
    if (!pager) return;
    const total = this.state.total || 0;
    const page = this.state.page || 1;
    const size = this.state.pageSize || 50;
    const pages = Math.max(1, Math.ceil(total / size));

    if (total === 0) {
      pager.innerHTML = "";
      return;
    }
    const start = (page - 1) * size + 1;
    const end = Math.min(total, page * size);
    pager.innerHTML = `
      <button class="btn" id="pager-prev" ${
        page <= 1 ? "disabled" : ""
      }>&laquo; Prev</button>
      <button class="btn" id="pager-next" ${
        page >= pages ? "disabled" : ""
      }>Next &raquo;</button>
      <span class="info">Halaman ${page} dari ${pages} • Menampilkan ${start}-${end} dari ${total}</span>
      <label style="margin-left:auto; font-size:12px; color:#5f6368;">Items per page:
        <select id="page-size-select" class="page-size">
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </label>
    `;
    const prev = document.getElementById("pager-prev");
    const next = document.getElementById("pager-next");
    if (prev)
      prev.onclick = () => {
        if (this.state.page > 1) {
          this.state.page -= 1;
          this.loadSurat();
        }
      };
    if (next)
      next.onclick = () => {
        if (this.state.page < pages) {
          this.state.page += 1;
          this.loadSurat();
        }
      };

    // Page size selector wiring with persistence
    const sel = document.getElementById("page-size-select");
    if (sel) {
      sel.value = String(size);
      sel.onchange = () => {
        const v = parseInt(sel.value, 10);
        if (!isNaN(v)) {
          localStorage.setItem("SURAT_PAGE_SIZE", String(v));
          this.state.pageSize = v;
          this.state.page = 1;
          this.loadSurat();
        }
      };
    }
  },

  renderSuratList() {
    const emailList = Utils.$("#email-list");
    if (!emailList) return;
    // Server already applied filters; just render what we have
    let suratToRender = Array.isArray(this.state.suratList)
      ? [...this.state.suratList]
      : [];

    // Text search now handled server-side; keep client-side as safety disabled

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
    // Tampilkan overlay (mobile) sesegera mungkin agar terasa responsif
    detailPane.innerHTML = Components.createLoadingState(
      "Memuat detail surat..."
    );
    if (window.innerWidth <= 767) {
      detailPane.classList.add("active");
    }

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
      }
      // Tambah tombol close (X) kecil untuk desktop/web
      {
        const header = content.querySelector(".email-detail-header");
        if (header) {
          const closeBtn = document.createElement("button");
          closeBtn.className = "detail-close-btn";
          closeBtn.setAttribute("aria-label", "Tutup detail");
          closeBtn.innerHTML =
            '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
          closeBtn.addEventListener("click", () => {
            if (window.innerWidth <= 767) {
              detailPane.classList.remove("active");
            } else {
              detailPane.innerHTML = '<div class="email-detail-placeholder"><p>Pilih surat untuk dibaca</p></div>';
              if (App && App.state) {
                App.state.currentSuratId = null;
                if (typeof App.updateSelectedUI === "function") App.updateSelectedUI();
              }
            }
          });
          header.appendChild(closeBtn);
        }
      }
      detailPane.appendChild(content);

      // Jika role UMUM, wire tombol Disposisi
      const role =
        document.body && document.body.dataset && document.body.dataset.role
          ? document.body.dataset.role.toUpperCase()
          : "CABANG";
      if (role === "UMUM") {
        const btn = content.querySelector(".dispose-btn");
        if (btn) {
          btn.addEventListener("click", () =>
            App.openDisposisiModal(suratData)
          );
        }
      }

      // Jika penerima aktif bukan UMUM, wire submit disposisi
      const btnSubmitDisp = content.querySelector("#btn-submit-disp");
      if (btnSubmitDisp) {
        btnSubmitDisp.addEventListener("click", async () => {
          const textEl = content.querySelector("#disp-text");
          const text = textEl && textEl.value ? textEl.value.trim() : "";
          if (!text) {
            alert("Teks disposisi wajib diisi");
            return;
          }
          try {
            const base = (window.API_BASE || "/surat/backend/api").replace(
              /\/$/,
              ""
            );
            const resp = await fetch(`${base}/disposisi.php?action=submit`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              credentials: "same-origin",
              body: JSON.stringify({ surat_id: suratData.id, text }),
            });
            const json = await resp.json();
            if (!resp.ok || json.success === false)
              throw new Error(json.error || json.message || "Gagal");
            // After submitting, reload list
            App.loadSurat();
            detailPane.innerHTML = Components.createEmptyState(
              "Berhasil",
              "Disposisi tersimpan dan surat dikembalikan ke UMUM."
            );
          } catch (err) {
            alert(err.message || "Gagal menyimpan disposisi");
          }
        });
      }

      // Wire up PDF and Zoom buttons
      const pdfBtn = content.querySelector(".pdf-btn");
      if (pdfBtn) {
        pdfBtn.addEventListener("click", (e) => {
          const id = e.currentTarget.dataset.suratId;
          const base = (window.API_BASE || "/surat/backend/api").replace(
            /\/$/,
            ""
          );
          window.open(`${base}/pdf.php?id=${id}`, "_blank");
        });
      }

      const zoomSelect = content.querySelector("#zoom-select");
      const letterPage = content.querySelector(".letter-page");
      const wrapper = content.querySelector(".letter-page-wrapper");

      const applyZoom = () => {
        if (!letterPage || !wrapper) return;
        const zoomLevel = zoomSelect.value;

        if (zoomLevel === "fit") {
          letterPage.style.transform = `scale(1)`;
          const scale = wrapper.offsetWidth / letterPage.offsetWidth;
          letterPage.style.transform = `scale(${scale})`;
          letterPage.style.transformOrigin = "top left";
        } else {
          letterPage.style.transform = `scale(${zoomLevel})`;
          letterPage.style.transformOrigin = "top center";
        }
      };

      if (zoomSelect) {
        zoomSelect.addEventListener("change", applyZoom);
        // Use a small timeout to allow the DOM to render before calculating width
        setTimeout(() => {
          zoomSelect.value = "fit";
          applyZoom();
        }, 50);
      }

      // Mark as read (persist) and update list item UI
      try {
        await API.markRead(suratId);
        // Update state and DOM class
        const found = this.state.suratList.find(
          (s) => String(s.id) === String(suratId)
        );
        if (found) found.is_read = true;
        const sel = Utils.$(".email-item.selected");
        if (sel) sel.classList.remove("unread");
        // update sidebar badge after marking read
        this.refreshUnreadBadge();
      } catch (err) {
        /* non-blocking */
      }
    } catch (error) {
      detailPane.innerHTML = Components.createErrorState(
        "Gagal Memuat Detail",
        error.message,
        () => this.selectSurat(suratId)
      );
    }
  },

  async toggleStar(suratId, value, btnEl) {
    try {
      await API.toggleStar(suratId, value);
      // Update UI instantly
      if (btnEl) btnEl.classList.toggle("starred", value);
      const found = this.state.suratList.find(
        (s) => Number(s.id) === Number(suratId)
      );
      if (found) found.starred = !!value;
    } catch (err) {
      Utils.showToast(err.message || "Gagal memperbarui bintang", "error");
    }
  },

  openDisposisiModal(surat) {
    // Simple modal
    const modal = document.createElement("div");
    modal.className = "modal";
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>Disposisi Surat</h3>
          <button class="close-btn" aria-label="Tutup">&times;</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="disp-to">Kepada (User)</label>
            <div class="typeahead">
              <input type="text" id="disp-to" class="form-input" placeholder="Cari nama user..." autocomplete="off" />
              <button class="btn" id="disp-add" type="button" style="margin-left:6px;">Tambah</button>
            </div>
            <div id="disp-panel" class="typeahead-panel hidden"></div>
            <div id="disp-chips" class="chips" style="margin-top:6px;"></div>
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
    modal.style.display = "block";

    const close = () => {
      if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
    };
    modal.querySelector(".close-btn").addEventListener("click", close);
    modal.addEventListener("click", (e) => {
      if (e.target === modal) close();
    });
    modal.querySelector("#disp-cancel").addEventListener("click", close);

    // Typeahead users (custom UI) + multi chips
    const input = modal.querySelector("#disp-to");
    const addBtn = modal.querySelector("#disp-add");
    const chips = modal.querySelector("#disp-chips");
    const panel = modal.querySelector("#disp-panel");
    let lastQ = "";
    let t = null;
    let items = [];
    let activeIndex = -1;
    const selected = new Map(); // id -> label

    input.addEventListener("focus", () => triggerFetch(""));
    input.addEventListener("input", () => {
      activeIndex = -1;
      triggerFetch(input.value.trim());
    });
    input.addEventListener("keydown", (e) => {
      if (panel.classList.contains("hidden")) return;
      if (e.key === "ArrowDown") {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        renderPanel();
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        renderPanel();
      } else if (e.key === "Enter") {
        if (activeIndex >= 0) {
          e.preventDefault();
          pick(items[activeIndex]);
        }
      } else if (e.key === "Escape") {
        hidePanel();
      }
    });
    document.addEventListener("click", (e) => {
      if (!modal.contains(e.target)) return; // only modal scope
      if (!panel.contains(e.target) && e.target !== input) hidePanel();
    });

    function triggerFetch(q) {
      if (q === lastQ) return;
      lastQ = q;
      if (t) clearTimeout(t);
      t = setTimeout(async () => {
        try {
          const base = (window.API_BASE || "/surat/backend/api").replace(
            /\/$/,
            ""
          );
          const res = await fetch(
            `${base}/recipients.php?q=` + encodeURIComponent(q),
            { credentials: "same-origin" }
          );
          const json = await res.json();
          items = (json.data || []).filter((x) => x.type === "USER");
          activeIndex = items.length ? 0 : -1;
          renderPanel();
        } catch {
          items = [];
          renderPanel();
        }
      }, 150);
    }
    function renderPanel() {
      panel.innerHTML = "";
      if (!items.length) {
        hidePanel();
        return;
      }
      const rect = input.getBoundingClientRect();
      panel.style.width = input.offsetWidth + "px";
      panel.classList.remove("hidden");
      items.forEach((x, idx) => {
        const div = document.createElement("div");
        div.className =
          "typeahead-item" + (idx === activeIndex ? " active" : "");
        div.textContent = x.label;
        div.addEventListener("mousedown", (e) => {
          e.preventDefault();
          pick(x);
        });
        panel.appendChild(div);
      });
    }
    function pick(x) {
      if (!selected.has(x.id)) {
        selected.set(x.id, x.label);
        renderChips();
      }
      input.value = "";
      hidePanel();
    }
    function hidePanel() {
      panel.classList.add("hidden");
    }

    if (addBtn) {
      addBtn.addEventListener("click", () => {
        if (activeIndex >= 0 && items[activeIndex]) {
          pick(items[activeIndex]);
          return;
        }
        // no active; ignore
      });
    }

    function renderChips() {
      chips.innerHTML = "";
      selected.forEach((label, id) => {
        const chip = document.createElement("span");
        chip.className = "chip";
        // Safer text insertion to avoid accidental HTML injection
        chip.textContent = label + " ";
        const rm = document.createElement("span");
        rm.className = "remove";
        rm.title = "Hapus";
        rm.innerHTML = "&times;";
        rm.addEventListener("click", () => {
          selected.delete(id);
          renderChips();
        });
        chip.appendChild(rm);
        chips.appendChild(chip);
      });
    }

    modal.querySelector("#disp-send").addEventListener("click", async () => {
      const ids = Array.from(selected.keys());
      if (!ids.length) {
        alert("Tambahkan minimal satu penerima.");
        return;
      }
      const note = modal.querySelector("#disp-note").value.trim();
      try {
        const base = (window.API_BASE || "/surat/backend/api").replace(
          /\/$/,
          ""
        );
        const resp = await fetch(`${base}/disposisi.php?action=request`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify({
            surat_id: surat.id,
            target_user_ids: ids,
            note,
          }),
        });
        const json = await resp.json();
        if (!resp.ok || json.success === false)
          throw new Error(json.error || json.message || "Gagal");
        close();
        // Refresh list and detail
        App.loadSurat();
        // Force clear detail
        const detailPane = Utils.$("#email-detail");
        if (detailPane)
          detailPane.innerHTML = Components.createEmptyState(
            "Berhasil",
            "Surat telah didisposisi."
          );
      } catch (err) {
        alert(err.message || "Gagal mengirim disposisi");
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

  function closeDetailOverlayIfOpen() {
    const detail = document.getElementById("email-detail");
    if (detail && detail.classList && detail.classList.contains("active")) {
      detail.classList.remove("active");
      return true;
    }
    return false;
  }

  function clearDetailPaneDesktopIfAny() {
    const detail = document.getElementById("email-detail");
    if (!detail) return false;
    // If there's meaningful content (not already placeholder), reset it
    const pageType = (document.body && document.body.dataset && document.body.dataset.page) || "";
    const placeholder = pageType === "tracking"
      ? '<div class="email-detail-placeholder"><p>Ketik kata kunci lalu pilih surat untuk melihat jejak perjalanan.</p></div>'
      : '<div class="email-detail-placeholder"><p>Pilih surat untuk dibaca</p></div>';
    // If current content already equals placeholder, no need to reset
    if (detail.innerHTML && detail.innerHTML.indexOf("email-detail-placeholder") !== -1) {
      // already placeholder
    } else {
      detail.innerHTML = placeholder;
    }
    // Clear selected state in list
    if (window.App && App.state) {
      App.state.currentSuratId = null;
      if (typeof App.updateSelectedUI === "function") App.updateSelectedUI();
    }
    return true;
  }

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
    if (e.key === "Escape") {
      // Prioritaskan menutup panel baca (overlay) di mobile
      if (closeDetailOverlayIfOpen()) {
        e.preventDefault();
        return;
      }
      // Di desktop: reset panel kanan ke placeholder
      if (clearDetailPaneDesktopIfAny()) {
        e.preventDefault();
        return;
      }
      // Jika tidak ada overlay detail yang aktif, tutup sidebar bila terbuka
      closeSidebar();
    }
  });
});
