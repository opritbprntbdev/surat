const Components = {
    // Komponen untuk item surat
    createSuratItem(surat) {
        const div = document.createElement('div');
        div.className = 'email-item'; // Tetap pakai class lama agar style tidak rusak
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

    // Loading State Component
    createLoadingState(message = 'Loading...') {
        const div = document.createElement('div');
        div.className = 'email-loading';
        div.innerHTML = `<div class="spinner"></div><span>${message}</span>`;
        return div;
    },

    // Error State Component
    createErrorState(title, message, retryCallback) {
        const div = document.createElement('div');
        div.className = 'email-error';
        div.innerHTML = `
            <h3>${title}</h3>
            <p>${message}</p>
            <button class="retry-btn">Coba Lagi</button>
        `;
        if (retryCallback) {
            div.querySelector('.retry-btn').addEventListener('click', retryCallback);
        }
        return div;
    },
    
    // Empty State Component
    createEmptyState(title, message) {
        const div = document.createElement('div');
        div.className = 'empty-state';
        div.innerHTML = `<h3>${title}</h3><p>${message}</p>`;
        return div;
    },
};