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
        const emailList = Utils.$('#email-list');
        if (emailList) {
            // Gunakan event delegation untuk menangani klik pada item surat
            emailList.addEventListener('click', (e) => {
                const item = e.target.closest('.email-item');
                if (item && item.dataset.suratId) {
                    this.selectSurat(item.dataset.suratId);
                }
            });
        }
        
        const refreshBtn = Utils.$('#refresh-btn');
        if(refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadSurat());
        }
    },

    async loadSurat() {
        const emailList = Utils.$('#email-list');
        emailList.innerHTML = Components.createLoadingState('Memuat surat...');
        try {
            const response = await API.getSuratList();
            this.state.suratList = response.data.data;
            this.renderSuratList();
        } catch (error) {
            emailList.innerHTML = Components.createErrorState('Gagal Memuat Surat', error.message, () => this.loadSurat());
        }
    },

    renderSuratList() {
        const emailList = Utils.$('#email-list');
        const suratToRender = this.state.suratList;

        if (!suratToRender || suratToRender.length === 0) {
            emailList.innerHTML = Components.createEmptyState('Tidak ada surat', 'Kotak masuk Anda kosong.');
            return;
        }

        emailList.innerHTML = '';
        suratToRender.forEach(surat => {
            const suratItem = Components.createSuratItem(surat);
            // Tambahkan kelas 'selected' jika ID-nya sama dengan yang sedang aktif
            if (surat.id == this.state.currentSuratId) {
                suratItem.classList.add('selected');
            }
            emailList.appendChild(suratItem);
        });
    },

    /**
     * BARU: Fungsi untuk memilih dan menampilkan detail surat.
     */
    async selectSurat(suratId) {
        if (this.state.currentSuratId === suratId) return; // Jangan fetch ulang jika sudah dipilih

        this.state.currentSuratId = suratId;
        this.updateSelectedUI(); // Update highlight di daftar surat

        const detailPane = Utils.$('#email-detail');
        detailPane.innerHTML = Components.createLoadingState('Memuat detail surat...');

        try {
            const response = await API.getSuratDetail(suratId);
            const suratData = response.data;
            detailPane.innerHTML = ''; // Kosongkan panel sebelum mengisi
            detailPane.appendChild(Components.createSuratDetail(suratData));
        } catch (error) {
            detailPane.innerHTML = Components.createErrorState('Gagal Memuat Detail', error.message, () => this.selectSurat(suratId));
        }
    },
    
    /**
     * BARU: Fungsi untuk update highlight pada item yang dipilih.
     */
    updateSelectedUI() {
        const items = Utils.$$('.email-item');
        items.forEach(item => {
            if (item.dataset.suratId == this.state.currentSuratId) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
};

// ... (kode App yang sudah ada) ...

document.addEventListener('DOMContentLoaded', () => {
    App.init();

    // LOGIKA BARU UNTUK DROPDOWN
    const avatarBtn = Utils.$('#user-avatar-btn');
    const dropdown = Utils.$('#user-dropdown');

    if (avatarBtn && dropdown) {
        avatarBtn.addEventListener('click', (event) => {
            event.stopPropagation(); // Mencegah event "klik di luar" tertrigger
            dropdown.classList.toggle('show');
            dropdown.classList.toggle('hidden', !dropdown.classList.contains('show'));
        });

        // Menutup dropdown jika user mengklik di luar area dropdown
        window.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && !avatarBtn.contains(event.target)) {
                dropdown.classList.remove('show');
                dropdown.classList.add('hidden');
            }
        });
    }
});
