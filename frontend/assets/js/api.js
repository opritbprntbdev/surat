// API Utilities
const API = {
    // Base configuration
    // Gunakan base absolut agar tetap benar dari halaman manapun
    baseURL: (window.API_BASE && typeof window.API_BASE === 'string') ? window.API_BASE : '/surat/backend/api',
    defaultHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },

    // Generic request method
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok || data.success === false) {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            Utils.showToast(error.message || 'Request failed', 'error');
            throw error;
        }
    },

    // HTTP methods
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url);
    },

    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    // Surat specific API methods
    async getSuratList(params = {}) {
        return this.get('/surat.php', params);
    },

    async getSuratDetail(id) {
        return this.get(`/surat.php?id=${id}`);
    },
    
    async toggleStar(id, starred) {
        return this.post('/star.php', { surat_id: Number(id), starred: !!starred });
    },
    
    async markRead(id) {
        return this.post('/read.php', { surat_id: Number(id) });
    },
    
    async getUnreadCount() {
        return this.get('/surat.php', { unread_count: 1 });
    },
    
    // ... (method lain bisa ditambahkan nanti)
};

// Export for use in other modules
window.API = API;