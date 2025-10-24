const Components = {
    createSuratItem(surat) {
        // ... (fungsi ini tidak berubah)
        const div = document.createElement('div');
        div.className = 'email-item';
        div.dataset.suratId = surat.id;
        div.innerHTML = `
            <div class="email-content-wrapper">
                <div class="email-avatar">${Utils.getInitials(surat.pengirim_nama)}</div>
                <div class="email-info">
                    <div class="email-header">
                        <div class="email-from">${Utils.escapeHtml(surat.pengirim_nama)}</div>
                        <div class="email-date">${Utils.formatDate(surat.tanggal_surat)}</div>
                    </div>
                    <div class="email-subject">${Utils.escapeHtml(surat.perihal)}</div>
                    <div class="email-preview">No: ${Utils.escapeHtml(surat.nomor_surat)} - Status: ${surat.status}</div>
                </div>
            </div>
        `;
        return div;
    },

    /**
     * BARU: Komponen untuk menampilkan detail surat di panel kanan.
     */
    createSuratDetail(surat) {
        const div = document.createElement('div');
        div.className = 'email-detail-content';
        div.innerHTML = `
            <div class="email-detail-header">
                <h2 class="email-detail-subject">${Utils.escapeHtml(surat.perihal)}</h2>
                <div class="email-detail-meta">
                    <div class="email-detail-from">
                        <div class="email-detail-avatar">${Utils.getInitials(surat.pengirim_nama)}</div>
                        <div class="email-detail-from-info">
                            <h4>${Utils.escapeHtml(surat.pengirim_nama)}</h4>
                            <p>Nomor Surat: ${Utils.escapeHtml(surat.nomor_surat)}</p>
                        </div>
                    </div>
                    <div class="email-detail-date">${Utils.formatDate(surat.tanggal_surat)}</div>
                </div>
                 <div class="email-detail-to">
                    <strong>Kepada:</strong> ${Utils.escapeHtml(surat.penerima_nama)}
                </div>
            </div>
            <div class="email-detail-body">
                ${surat.isi_surat.replace(/\n/g, '<br>')}
            </div>
        `;
        return div;
    },

    createLoadingState(message = 'Loading...') {
        // ... (tidak berubah)
    },
    createErrorState(title, message, retryCallback) {
        // ... (tidak berubah)
    },
    createEmptyState(title, message) {
        // ... (tidak berubah)
    },
};