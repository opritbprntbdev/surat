const Components = {
  createSuratItem(surat) {
    // ... (fungsi ini tidak berubah)
    const div = document.createElement("div");
    const isRead = !!surat.is_read;
    div.className = "email-item" + (isRead ? "" : " unread");
    div.dataset.suratId = surat.id;
    const role =
      document.body && document.body.dataset && document.body.dataset.role
        ? document.body.dataset.role.toUpperCase()
        : "CABANG";
    div.innerHTML = `
            <div class="email-content-wrapper">
                <div class="email-avatar">${Utils.getInitials(
                  surat.pengirim_nama
                )}</div>
                <div class="email-info">
                    <div class="email-header">
                        <div class="email-from">${Utils.escapeHtml(
                          surat.pengirim_nama
                        )}</div>
                        <div class="email-date">${Utils.formatDate(
                          surat.tanggal_surat
                        )}</div>
                    </div>
                    <div class="email-subject">${Utils.escapeHtml(
                      surat.perihal
                    )}</div>
                    <div class="email-preview">No: ${Utils.escapeHtml(
                      surat.nomor_surat
                    )} - Status: ${surat.status} ${(() => {
      const pt = Math.max(0, Number(surat.progress_total || 0));
      const pd = Math.max(0, Math.min(Number(surat.progress_done || 0), pt));
      if (pt <= 0) return "";
      const cls =
        pd >= pt
          ? "progress-label--done"
          : pd > 0
          ? "progress-label--partial"
          : "progress-label--none";
      const tip =
        pd >= pt
          ? `Selesai: ${pd}/${pt} target menindak`
          : pd > 0
          ? `Progres: ${pd}/${pt} target menindak`
          : `Belum ada progres: 0/${pt} target menindak`;
      return `<span class=\"progress-label ${cls}\" title=\"${tip}\">(${pd}/${pt})</span>`;
    })()}
                      ${(() => {
                        const mine = Number(surat.my_dispo_count || 0);
                        const total = Number(surat.dispo_count || 0);
                        if (mine > 0) {
                          return ' <span class="badge badge-primary" title="Anda sudah mengisi disposisi">Dijawab</span>';
                        }
                        if (total > 0) {
                          const lastRole = String(
                            surat.last_dispo_user_role || ""
                          ).toUpperCase();
                          if (role === "UMUM" && lastRole.includes("DIREKSI")) {
                            return ' <span class="badge badge-secondary" title="Ada jawaban disposisi Direksi">Disposisi Direksi</span>';
                          }
                          return ' <span class="badge badge-secondary" title="Ada jawaban disposisi">Ada Disposisi</span>';
                        }
                        return "";
                      })()}
                      ${(() => {
                        const text = (surat.last_dispo_text || "").trim();
                        if (!text) return "";
                        return (
                          " • " + Utils.escapeHtml(Utils.truncateText(text, 60))
                        );
                      })()}
                    </div>
                </div>
                <button class="email-star${
                  surat.starred ? " starred" : ""
                }" data-surat-id="${surat.id}" title="Bintang">
                  <svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                  </svg>
                </button>
            </div>
        `;
    return div;
  },

  /**
   * BARU: Komponen untuk menampilkan detail surat di panel kanan.
   */
  createSuratDetail(surat) {
    const div = document.createElement("div");
    div.className = "email-detail-content";
    const role =
      document.body && document.body.dataset && document.body.dataset.role
        ? document.body.dataset.role.toUpperCase()
        : "CABANG";
    let actionsHTML = "";
    if (role === "UMUM") {
      actionsHTML = `<div class="email-detail-actions" style="margin-top:8px;">
            <button class="btn btn-primary dispose-btn" data-surat-id="${surat.id}">Disposisi</button>
         </div>`;
    } else if (surat.active_for_user) {
      actionsHTML = `<div class="email-detail-actions" style="margin-top:12px;">
          <div class="form-group">
            <label for="disp-text">Tulis Disposisi</label>
            <textarea id="disp-text" rows="4" class="form-textarea" placeholder="Arahan/keputusan..."></textarea>
          </div>
          <button id="btn-submit-disp" class="btn btn-primary">Kirim Kembali ke UMUM</button>
        </div>`;
    }
    div.innerHTML = `
            <div class="email-detail-header">
                <h2 class="email-detail-subject">${Utils.escapeHtml(
                  surat.perihal
                )}</h2>
                <div class="email-detail-meta">
                    <div class="email-detail-from">
                        <div class="email-detail-avatar">${Utils.getInitials(
                          surat.pengirim_nama
                        )}</div>
                        <div class="email-detail-from-info">
                            <h4>${Utils.escapeHtml(surat.pengirim_nama)}</h4>
                            <p>Nomor Surat: ${Utils.escapeHtml(
                              surat.nomor_surat
                            )}</p>
                        </div>
                    </div>
                    <div class="email-detail-date">${Utils.formatDate(
                      surat.tanggal_surat
                    )}</div>
                </div>
                <div class="email-detail-to">
                    ${(() => {
                      // Tampilkan penerima aktif dari routing jika ada
                      const active = Array.isArray(surat.routing)
                        ? surat.routing
                            .filter((r) => r.tipe_penerima === "AKTIF")
                            .map((r) => r.user_nama)
                        : [];
                      const label = active.length
                        ? active.join(", ")
                        : surat.penerima_nama || "-";
                      return `<strong>Kepada (aktif):</strong> ${Utils.escapeHtml(
                        label
                      )}`;
                    })()}
                </div>
                ${actionsHTML}
            </div>
      <div class="email-detail-body">
        <div class="preview-controls">
            <button class="btn btn-secondary pdf-btn" data-surat-id="${
              surat.id
            }">PDF</button>
            <div class="zoom-controls">
                <label for="zoom-select">Zoom:</label>
                <select id="zoom-select" class="form-select">
                    <option value="fit">Fit</option>
                    <option value="1.0" selected>100%</option>
                    <option value="0.9">90%</option>
                    <option value="0.8">80%</option>
                    <option value="0.7">70%</option>
                    <option value="0.6">60%</option>
                    <option value="0.5">50%</option>
                    <option value="0.4">40%</option>
                    <option value="0.3">30%</option>
                    <option value="0.2">20%</option>
                    <option value="0.1">10%</option>
                </select>
            </div>
        </div>
        <div class="letter-page-wrapper">
            <div class="letter-page">
              <div class="letter-content">${surat.isi_surat || ""}</div>
            </div>
        </div>
      </div>
      <div class="email-detail-history" style="margin-top:16px;">
        <h3 style="margin:0 0 8px;">Riwayat Disposisi</h3>
        ${(() => {
          const list = Array.isArray(surat.dispositions)
            ? surat.dispositions
            : [];
          if (!list.length) return "<p><i>Belum ada disposisi.</i></p>";
          const items = list
            .map(
              (d) => `
            <li><strong>${Utils.escapeHtml(d.user_nama || "Pengguna")}</strong>
              (${Utils.formatDate(d.created_at)})<br/>${Utils.escapeHtml(
                d.disposition_text || ""
              )}
            </li>`
            )
            .join("");
          return `<ul style="margin:0; padding-left:18px;">${items}</ul>`;
        })()}
        ${(() => {
          const p = surat.progress;
          if (!p || typeof p.total !== "number") return "";
          const hdr = `<div class="card" style="margin-top:12px;"><div class="card-header"><strong>Progres Disposisi: ${p.done}/${p.total}</strong></div>`;
          const body = `<div class=\"card-body\"><ul class=\"progress-targets\" style=\"margin:0; padding-left:18px;\">${(
            p.targets || []
          )
            .map((t) => {
              const done = String(t.status || "") === "SELESAI";
              const cls = done ? "target-chip--done" : "target-chip--pending";
              const tip = done
                ? t.ditindak_at
                  ? `Ditindak: ${Utils.formatDate(t.ditindak_at)}`
                  : "Ditindak"
                : t.diterima_at
                ? `Diterima: ${Utils.formatDate(t.diterima_at)}`
                : "Menunggu";
              const label = done ? "Selesai" : "Menunggu";
              return `<li><span class=\"target-chip ${cls}\" title=\"${Utils.escapeHtml(
                tip
              )}\"></span> <strong>${Utils.escapeHtml(
                t.user_nama || ""
              )}</strong> <span class=\"target-status ${cls}\" title=\"${Utils.escapeHtml(
                tip
              )}\">${label}</span></li>`;
            })
            .join("")}</ul></div></div>`;
          return hdr + body;
        })()}
        <h3 style="margin:16px 0 8px;">Jejak Perjalanan</h3>
        ${(() => {
          const rt = Array.isArray(surat.routing) ? surat.routing : [];
          if (!rt.length) return "<p><i>Data rute belum tersedia.</i></p>";
          const items = rt
            .map(
              (r) => `
            <li><strong>${Utils.escapeHtml(
              r.user_nama || ""
            )}</strong> - ${Utils.escapeHtml(r.tipe_penerima || "")}
              (diterima: ${Utils.formatDate(r.diterima_at)}${
                r.ditindak_at
                  ? ", ditindak: " + Utils.formatDate(r.ditindak_at)
                  : ""
              })
            </li>`
            )
            .join("");
          return `<ul style="margin:0; padding-left:18px;">${items}</ul>`;
        })()}
      </div>
        `;
    return div;
  },

  createLoadingState(message = "Loading...") {
    const msg = (message == null) ? 'Loading...' : String(message);
    return `
      <div class="page-content">
        <div style="display:flex;align-items:center;gap:8px;color:#5f6368;">
          <div class="spinner" aria-hidden="true"></div>
          <span>${Utils.escapeHtml(msg)}</span>
        </div>
      </div>`;
  },
  createErrorState(title, message, retryCallback) {
    const t = (title == null) ? 'Terjadi Kesalahan' : String(title);
    const m = (message == null) ? 'Gagal memuat data.' : String(message);
    const id = 'retry-'+Math.random().toString(36).slice(2);
    const html = `
      <div class="page-content">
        <div class="card">
          <div class="card-header"><strong>${Utils.escapeHtml(t)}</strong></div>
          <div class="card-body">
            <p style="margin:0 0 8px;color:#5f6368;">${Utils.escapeHtml(m)}</p>
            ${retryCallback ? `<button id="${id}" class="btn btn-secondary">Coba Lagi</button>` : ''}
          </div>
        </div>
      </div>`;
    // Kembalikan HTML; caller boleh set innerHTML, lalu optional wire tombol
    setTimeout(()=>{
      if (retryCallback) {
        const btn = document.getElementById(id);
        if (btn) btn.addEventListener('click', retryCallback);
      }
    },0);
    return html;
  },
  createEmptyState(title, message) {
    const t = (title == null) ? 'Kosong' : String(title);
    const m = (message == null) ? '' : String(message);
    return `
      <div class="page-content">
        <div style="color:#5f6368;">
          <div style="font-weight:600;margin-bottom:4px;">${Utils.escapeHtml(t)}</div>
          ${m ? `<div>${Utils.escapeHtml(m)}</div>` : ''}
        </div>
      </div>`;
  },
};
