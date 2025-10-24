const App = {
    // State
    state: {
        suratList: [],
        selectedSurat: [],
        currentSurat: null,
        activeCategory: 'inbox', // Nanti bisa disesuaikan
    },

    // Initialize application
    init() {
        this.bindEvents();
        this.loadSurat(); // Ganti dari loadEmails ke loadSurat
    },

    // Bind event listeners
    bindEvents() {
        // Event untuk refresh
        const refreshBtn = Utils.$('#refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.loadSurat());
        }

        // Event klik pada daftar surat
        const emailList = Utils.$('#email-list');
        if (emailList) {
            emailList.addEventListener('click', (e) => {
                const item = e.target.closest('.email-item');
                if (item) {
                    console.log(`Surat dengan ID ${item.dataset.suratId} diklik.`);
                    // Logika untuk menampilkan detail surat bisa ditambahkan di sini
                }
            });
        }
    },

    // Load surat dari backend
    async loadSurat() {
        const emailList = Utils.$('#email-list');
        try {
            emailList.innerHTML = Components.createLoadingState('Memuat surat...');
            
            const response = await API.getSuratList();
            
            this.state.suratList = response.data.data; // Sesuaikan dengan struktur response
            this.renderSuratList();
            
        } catch (error) {
            console.error('Gagal memuat surat:', error);
            emailList.innerHTML = Components.createErrorState('Gagal Memuat Surat', error.message, () => this.loadSurat());
        }
    },

    // Render daftar surat
    renderSuratList() {
        const emailList = Utils.$('#email-list');
        if (!emailList) return;

        const suratToRender = this.state.suratList;
        
        if (!suratToRender || suratToRender.length === 0) {
            emailList.innerHTML = Components.createEmptyState('Tidak ada surat', 'Kotak masuk Anda kosong.');
            return;
        }

        emailList.innerHTML = '';
        suratToRender.forEach(surat => {
            const suratItem = Components.createSuratItem(surat); // Pakai component baru
            emailList.appendChild(suratItem);
        });
    },
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});