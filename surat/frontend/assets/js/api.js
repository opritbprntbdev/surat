// API Utilities
const API = {
    // Base configuration
    baseURL: '../backend/api',
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
            Utils.showLoading();
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            Utils.showToast(error.message || 'Request failed', 'error');
            throw error;
        } finally {
            Utils.hideLoading();
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

    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },

    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    },

    // Email specific API methods
    async getEmails(params = {}) {
        return this.get('/emails.php', params);
    },

    async getEmail(id) {
        return this.get(`/emails.php?id=${id}`);
    },

    async createEmail(emailData) {
        return this.post('/emails.php', emailData);
    },

    async updateEmail(id, emailData) {
        return this.put(`/emails.php?id=${id}`, emailData);
    },

    async deleteEmail(id) {
        return this.delete(`/emails.php?id=${id}`);
    },

    async markEmailsAsRead(emailIds) {
        return this.post('/emails.php', {
            action: 'mark_read',
            email_ids: emailIds
        });
    },

    async markEmailsAsUnread(emailIds) {
        return this.post('/emails.php', {
            action: 'mark_unread',
            email_ids: emailIds
        });
    },

    async toggleEmailStar(id) {
        return this.post('/emails.php', {
            action: 'toggle_star',
            email_id: id
        });
    },

    async moveEmails(emailIds, destination) {
        return this.post('/emails.php', {
            action: 'move',
            email_ids: emailIds,
            destination: destination
        });
    },

    async deleteEmails(emailIds) {
        return this.post('/emails.php', {
            action: 'delete',
            email_ids: emailIds
        });
    },

    async searchEmails(query, filters = {}) {
        return this.get('/emails.php', {
            action: 'search',
            q: query,
            ...filters
        });
    },

    // Authentication methods (if needed)
    async login(credentials) {
        return this.post('/auth.php', {
            action: 'login',
            ...credentials
        });
    },

    async logout() {
        return this.post('/auth.php', {
            action: 'logout'
        });
    },

    async getCurrentUser() {
        return this.get('/auth.php', {
            action: 'current_user'
        });
    },

    // User methods (if needed)
    async getUsers() {
        return this.get('/users.php');
    },

    async getUser(id) {
        return this.get(`/users.php?id=${id}`);
    },

    async createUser(userData) {
        return this.post('/users.php', userData);
    },

    async updateUser(id, userData) {
        return this.put(`/users.php?id=${id}`, userData);
    },

    async deleteUser(id) {
        return this.delete(`/users.php?id=${id}`);
    },

    // File upload methods
    async uploadFile(file, onProgress = null) {
        const formData = new FormData();
        formData.append('file', file);

        const config = {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set Content-Type for FormData
        };

        // Add progress tracking if callback provided
        if (onProgress && typeof XMLHttpRequest !== 'undefined') {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const progress = (e.loaded / e.total) * 100;
                        onProgress(progress);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            resolve(data);
                        } catch (e) {
                            reject(new Error('Invalid JSON response'));
                        }
                    } else {
                        reject(new Error(`HTTP error! status: ${xhr.status}`));
                    }
                });

                xhr.addEventListener('error', () => {
                    reject(new Error('Network error'));
                });

                xhr.open('POST', `${this.baseURL}/upload.php`);
                xhr.send(formData);
            });
        }

        return this.request('/upload.php', config);
    },

    // Bulk operations
    async bulkOperation(emailIds, operation, data = {}) {
        return this.post('/emails.php', {
            action: 'bulk_operation',
            email_ids: emailIds,
            operation: operation,
            ...data
        });
    },

    // Settings and preferences
    async getSettings() {
        return this.get('/settings.php');
    },

    async updateSettings(settings) {
        return this.post('/settings.php', settings);
    },

    // Statistics and analytics
    async getStats() {
        return this.get('/stats.php');
    },

    // Cache management
    clearCache() {
        // Clear any cached data
        Utils.removeItem('cached_emails');
        Utils.removeItem('cached_user');
        Utils.removeItem('last_sync');
    },

    // Offline support
    async syncOfflineChanges() {
        const offlineChanges = Utils.getItem('offline_changes') || [];
        
        for (const change of offlineChanges) {
            try {
                await this.request(change.endpoint, change.options);
            } catch (error) {
                console.error('Failed to sync offline change:', error);
            }
        }
        
        Utils.removeItem('offline_changes');
    },

    // Queue operation for offline mode
    queueOfflineOperation(endpoint, options) {
        const offlineChanges = Utils.getItem('offline_changes') || [];
        offlineChanges.push({
            endpoint,
            options,
            timestamp: Date.now()
        });
        Utils.setItem('offline_changes', offlineChanges);
    },

    // Retry failed requests
    async retryFailedRequests() {
        const failedRequests = Utils.getItem('failed_requests') || [];
        const remainingFailed = [];

        for (const request of failedRequests) {
            try {
                await this.request(request.endpoint, request.options);
            } catch (error) {
                remainingFailed.push(request);
            }
        }

        Utils.setItem('failed_requests', remainingFailed);
    },

    // Health check
    async healthCheck() {
        try {
            const response = await this.get('/health.php');
            return response.status === 'ok';
        } catch (error) {
            return false;
        }
    }
};

// Mock data for development (when backend is not ready)
const MockAPI = {
    mockEmails: [
        {
            id: '1',
            from: 'John Doe',
            fromEmail: 'john.doe@example.com',
            subject: 'Meeting Tomorrow at 2 PM',
            preview: 'Hi, just wanted to confirm our meeting tomorrow at 2 PM. We\'ll be discussing the Q4 roadmap...',
            content: 'Hi, just wanted to confirm our meeting tomorrow at 2 PM. We\'ll be discussing the Q4 roadmap and deliverables for next quarter. Please prepare your updates and any blockers you\'re facing.\n\nAgenda:\n1. Q3 Review\n2. Q4 Planning\n3. Resource Allocation\n4. Risk Assessment\n\nLooking forward to seeing everyone there!\n\nBest regards,\nJohn',
            date: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
            isRead: false,
            isStarred: true,
            category: 'primary',
            labels: ['Work', 'Important'],
            cc: ['team@company.com'],
            attachments: [
                { id: '1', name: 'Q4_Roadmap.pdf', size: '2.4 MB', type: 'application/pdf' },
                { id: '2', name: 'Budget_Spreadsheet.xlsx', size: '1.1 MB', type: 'application/vnd.ms-excel' }
            ]
        },
        {
            id: '2',
            from: 'LinkedIn',
            fromEmail: 'notifications@linkedin.com',
            subject: 'You have 5 new profile views',
            preview: 'Your profile is getting noticed! 5 people viewed your profile this week. See who\'s interested...',
            content: 'Your profile is getting noticed! 5 people viewed your profile this week. See who\'s interested in your professional background and connect with them to expand your network.',
            date: new Date(Date.now() - 4 * 60 * 60 * 1000).toISOString(),
            isRead: true,
            isStarred: false,
            category: 'social',
            labels: ['Social']
        },
        {
            id: '3',
            from: 'Amazon',
            fromEmail: 'ship-confirm@amazon.com',
            subject: 'Your order has been shipped',
            preview: 'Good news! Your order #123-4567890 has been shipped and is on its way. Track your package...',
            content: 'Good news! Your order #123-4567890 has been shipped and is on its way. You can track your package using the tracking number provided in your order details.',
            date: new Date(Date.now() - 24 * 60 * 60 * 1000).toISOString(),
            isRead: true,
            isStarred: false,
            category: 'updates',
            labels: ['Shopping'],
            attachments: [
                { id: '3', name: 'invoice.pdf', size: '156 KB', type: 'application/pdf' }
            ]
        },
        {
            id: '4',
            from: 'Sarah Wilson',
            fromEmail: 'sarah.w@company.com',
            subject: 'Project Update - Phase 2 Complete',
            preview: 'Great news! We\'ve successfully completed Phase 2 of the project ahead of schedule. The team did amazing...',
            content: 'Great news! We\'ve successfully completed Phase 2 of the project ahead of schedule. The team did amazing work and we\'re ready to move into Phase 3. Please review the attached deliverables and let me know if you have any questions.',
            date: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
            isRead: false,
            isStarred: true,
            category: 'primary',
            labels: ['Work', 'Project'],
            cc: ['manager@company.com', 'stakeholders@company.com']
        },
        {
            id: '5',
            from: 'GitHub',
            fromEmail: 'notifications@github.com',
            subject: '[PR] New pull request in your repository',
            preview: 'A new pull request has been opened in your repository. Review the changes and provide feedback...',
            content: 'A new pull request has been opened in your repository. Review the changes and provide feedback to help improve the code quality.',
            date: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
            isRead: true,
            isStarred: false,
            category: 'updates',
            labels: ['Development']
        },
        {
            id: '6',
            from: 'TechStore',
            fromEmail: 'deals@techstore.com',
            subject: '🔥 Flash Sale - 70% Off Electronics!',
            preview: 'Limited time offer! Get amazing deals on laptops, phones, and accessories. Don\'t miss out...',
            content: 'Limited time offer! Get amazing deals on laptops, phones, and accessories. Don\'t miss out on these incredible savings. Sale ends tonight!',
            date: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000).toISOString(),
            isRead: false,
            isStarred: false,
            category: 'promotions',
            labels: ['Deals', 'Shopping']
        }
    ],

    async getEmails(params = {}) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        
        let emails = [...this.mockEmails];
        
        // Apply filters
        if (params.category) {
            emails = emails.filter(email => {
                if (params.category === 'inbox') return !email.isRead;
                if (params.category === 'starred') return email.isStarred;
                if (params.category === 'social') return email.category === 'social';
                if (params.category === 'promotions') return email.category === 'promotions';
                if (params.category === 'updates') return email.category === 'updates';
                return true;
            });
        }
        
        if (params.search) {
            const query = params.search.toLowerCase();
            emails = emails.filter(email => 
                email.subject.toLowerCase().includes(query) ||
                email.from.toLowerCase().includes(query) ||
                email.preview.toLowerCase().includes(query)
            );
        }
        
        return {
            success: true,
            data: emails,
            total: emails.length,
            page: params.page || 1,
            limit: params.limit || 50
        };
    },

    async getEmail(id) {
        await new Promise(resolve => setTimeout(resolve, 300));
        const email = this.mockEmails.find(e => e.id === id);
        if (!email) {
            throw new Error('Email not found');
        }
        return {
            success: true,
            data: email
        };
    },

    async createEmail(emailData) {
        await new Promise(resolve => setTimeout(resolve, 500));
        const newEmail = {
            id: Utils.generateEmailId(),
            ...emailData,
            date: new Date().toISOString(),
            isRead: true,
            isStarred: false,
            category: 'primary',
            labels: ['Sent']
        };
        this.mockEmails.unshift(newEmail);
        return {
            success: true,
            data: newEmail
        };
    },

    async updateEmail(id, emailData) {
        await new Promise(resolve => setTimeout(resolve, 300));
        const index = this.mockEmails.findIndex(e => e.id === id);
        if (index === -1) {
            throw new Error('Email not found');
        }
        this.mockEmails[index] = { ...this.mockEmails[index], ...emailData };
        return {
            success: true,
            data: this.mockEmails[index]
        };
    },

    async deleteEmail(id) {
        await new Promise(resolve => setTimeout(resolve, 300));
        const index = this.mockEmails.findIndex(e => e.id === id);
        if (index === -1) {
            throw new Error('Email not found');
        }
        this.mockEmails.splice(index, 1);
        return {
            success: true,
            message: 'Email deleted successfully'
        };
    }
};

// Use mock API if backend is not available
const EmailAPI = async (...args) => {
    try {
        // Try real API first
        return await API(...args);
    } catch (error) {
        console.warn('Backend API not available, using mock data:', error.message);
        // Fall back to mock API
        const method = args[0];
        if (typeof MockAPI[method] === 'function') {
            return await MockAPI[method](...args.slice(1));
        }
        throw error;
    }
};

// Export all API methods
Object.assign(EmailAPI, API, MockAPI);

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { API, MockAPI, EmailAPI };
}