const Components = {
  createSuratItem(surat) {
    // ... (fungsi ini tidak berubah)
    const div = document.createElement("div");
    div.className = "email-item";
    div.dataset.suratId = surat.id;
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
                    )} - Status: ${surat.status}</div>
                </div>
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
    const role = (document.body && document.body.dataset && document.body.dataset.role)
      ? document.body.dataset.role.toUpperCase()
      : 'CABANG';
    const actionsHTML = role === 'UMUM'
      ? `<div class="email-detail-actions" style="margin-top:8px;">
            <button class="btn btn-primary dispose-btn" data-surat-id="${surat.id}">Disposisi</button>
         </div>`
      : '';
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
                    <strong>Kepada:</strong> ${Utils.escapeHtml(
                      surat.penerima_nama
                    )}
                </div>
                ${actionsHTML}
            </div>
      <div class="email-detail-body">
        <div class="preview-controls">
            <button class="btn btn-secondary pdf-btn" data-surat-id="${surat.id}">PDF</button>
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
              <div class="letter-content">${surat.isi_surat || ''}</div>
            </div>
        </div>
      </div>
        `;
    return div;
  },

  createLoadingState(message = "Loading...") {
    // ... (tidak berubah)
  },
  createErrorState(title, message, retryCallback) {
    // ... (tidak berubah)
  },
  createEmptyState(title, message) {
    // ... (tidak berubah)
  },
};
