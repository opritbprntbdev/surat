const App = {
    // State
    state: {
        emails: [],
        selectedEmails: [],
        currentEmail: null,
        activeCategory: 'inbox',
        searchQuery: '',
        isSidebarOpen: false,
        isComposeOpen: false,
        isLoading: false,
        isMobile: false
    },

    // Initialize application
    init() {
        this.detectMobile();
        this.bindEvents();
        this.loadEmails();
        Components.init();
        
        // Initialize from URL params
        const category = Utils.getQueryParam('category');
        if (category) {
            this.setActiveCategory(category);
        }
        
        console.log('Cek Log untuk error');
    },

    // Detect mobile device
    detectMobile() {
        this.state.isMobile = Utils.isMobile();
        if (this.state.isMobile) {
            Utils.addClass(document.body, 'mobile');
        } else {
            Utils.removeClass(document.body, 'mobile');
        }
    },

    // Bind event listeners
    bindEvents() {
        // Mobile menu
        const mobileMenuBtn = Utils.$('#mobile-menu-btn');
        const sidebar = Utils.$('.sidebar');
        const sidebarOverlay = Utils.$('#sidebar-overlay');

        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                this.closeSidebar();
            });
        }

        // Sidebar navigation
        const navItems = Utils.$$('.nav-item[data-category]');
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const category = item.dataset.category;
                this.setActiveCategory(category);
                if (this.state.isMobile) {
                    this.closeSidebar();
                }
            });
        });

        // Compose button
        const composeBtn = Utils.$('#compose-btn');
        if (composeBtn) {
            composeBtn.addEventListener('click', () => {
                this.openComposeModal();
            });
        }

        // Search
        const searchInput = Utils.$('#search-input');
        const searchBtn = Utils.$('#search-btn');
        
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce((e) => {
                this.state.searchQuery = e.target.value;
                this.filterEmails();
            }, 300));

            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.searchEmails();
                }
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                this.searchEmails();
            });
        }

        // Refresh button
        const refreshBtn = Utils.$('#refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.refreshEmails();
            });
        }

        // Select all checkbox
        const selectAllCheckbox = Utils.$('#select-all-checkbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.selectAllEmails(e.target.checked);
            });
        }

        // Compose modal
        const closeComposeBtn = Utils.$('#close-compose');
        const sendEmailBtn = Utils.$('#send-email');
        const composeModal = Utils.$('#compose-modal');

        if (closeComposeBtn) {
            closeComposeBtn.addEventListener('click', () => {
                this.closeComposeModal();
            });
        }

        if (sendEmailBtn) {
            sendEmailBtn.addEventListener('click', () => {
                this.sendEmail();
            });
        }

        if (composeModal) {
            composeModal.addEventListener('click', (e) => {
                if (e.target === composeModal) {
                    this.closeComposeModal();
                }
            });
        }

        // Email list event delegation
        const emailList = Utils.$('#email-list');
        if (emailList) {
            emailList.addEventListener('click', (e) => {
                this.handleEmailListClick(e);
            });
        }

        // Window resize
        window.addEventListener('resize', Utils.debounce(() => {
            this.detectMobile();
        }, 250));

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    },

    // Toggle sidebar
    toggleSidebar() {
        const sidebar = Utils.$('.sidebar');
        const sidebarOverlay = Utils.$('#sidebar-overlay');
        
        this.state.isSidebarOpen = !this.state.isSidebarOpen;
        
        if (this.state.isSidebarOpen) {
            Utils.addClass(sidebar, 'open');
            Utils.removeClass(sidebarOverlay, 'hidden');
        } else {
            Utils.removeClass(sidebar, 'open');
            Utils.addClass(sidebarOverlay, 'hidden');
        }
    },

    // Close sidebar
    closeSidebar() {
        const sidebar = Utils.$('.sidebar');
        const sidebarOverlay = Utils.$('#sidebar-overlay');
        
        this.state.isSidebarOpen = false;
        Utils.removeClass(sidebar, 'open');
        Utils.addClass(sidebarOverlay, 'hidden');
    },

    // Set active category
    setActiveCategory(category) {
        this.state.activeCategory = category;
        
        // Update navigation
        const navItems = Utils.$$('.nav-item[data-category]');
        navItems.forEach(item => {
            if (item.dataset.category === category) {
                Utils.addClass(item, 'active');
            } else {
                Utils.removeClass(item, 'active');
            }
        });

        // Update URL
        Utils.setQueryParam('category', category);
        
        // Filter emails
        this.filterEmails();
    },

    // Load emails
    async loadEmails() {
        try {
            this.state.isLoading = true;
            this.showLoadingState();
            
            const response = await EmailAPI.getEmails({
                category: this.state.activeCategory,
                search: this.state.searchQuery
            });
            
            this.state.emails = response.data;
            this.renderEmailList();
            Components.updateCounts(this.state.emails);
            
        } catch (error) {
            console.error('Failed to load emails:', error);
            this.showErrorState('Failed to load emails', error.message);
        } finally {
            this.state.isLoading = false;
        }
    },

    // Filter emails
    filterEmails() {
        let filteredEmails = [...this.state.emails];
        
        // Filter by category
        if (this.state.activeCategory !== 'inbox') {
            filteredEmails = filteredEmails.filter(email => {
                switch (this.state.activeCategory) {
                    case 'starred':
                        return email.isStarred;
                    case 'social':
                        return email.category === 'social';
                    case 'promotions':
                        return email.category === 'promotions';
                    case 'updates':
                        return email.category === 'updates';
                    case 'sent':
                        return email.labels.includes('Sent');
                    case 'drafts':
                        return email.labels.includes('Draft');
                    case 'spam':
                        return email.category === 'spam';
                    case 'trash':
                        return email.labels.includes('Trash');
                    default:
                        return true;
                }
            });
        }
        
        // Filter by search query
        if (this.state.searchQuery) {
            const query = this.state.searchQuery.toLowerCase();
            filteredEmails = filteredEmails.filter(email =>
                email.subject.toLowerCase().includes(query) ||
                email.from.toLowerCase().includes(query) ||
                email.preview.toLowerCase().includes(query)
            );
        }
        
        this.renderEmailList(filteredEmails);
    },

    // Search emails
    async searchEmails() {
        if (!this.state.searchQuery.trim()) {
            this.filterEmails();
            return;
        }

        try {
            this.state.isLoading = true;
            this.showLoadingState();
            
            const response = await EmailAPI.searchEmails(this.state.searchQuery);
            this.state.emails = response.data;
            this.renderEmailList();
            
        } catch (error) {
            console.error('Search failed:', error);
            this.showErrorState('Search failed', error.message);
        } finally {
            this.state.isLoading = false;
        }
    },

    // Refresh emails
    async refreshEmails() {
        await this.loadEmails();
        Utils.showToast('Emails refreshed', 'success');
    },

    // Render email list
    renderEmailList(emails = null) {
        const emailList = Utils.$('#email-list');
        if (!emailList) return;

        const emailsToRender = emails || this.state.emails;
        
        if (emailsToRender.length === 0) {
            emailList.innerHTML = '';
            emailList.appendChild(Components.createEmptyState(
                'No emails found',
                this.state.searchQuery ? 'Try adjusting your search terms' : 'Your inbox is empty'
            ));
            return;
        }

        emailList.innerHTML = '';
        emailsToRender.forEach(email => {
            const emailItem = Components.createEmailItem(email);
            emailList.appendChild(emailItem);
        });
    },

    // Handle email list clicks
    handleEmailListClick(e) {
        const emailItem = e.target.closest('.email-item');
        if (!emailItem) return;

        const emailId = emailItem.dataset.emailId;
        const email = this.state.emails.find(e => e.id === emailId);
        if (!email) return;

        // Handle star click
        if (e.target.closest('.email-star')) {
            e.stopPropagation();
            this.toggleEmailStar(emailId);
            return;
        }

        // Handle checkbox click
        if (e.target.closest('.email-checkbox')) {
            e.stopPropagation();
            this.toggleEmailSelection(emailId);
            return;
        }

        // Handle email click
        this.selectEmail(email);
    },

    // Select email
    async selectEmail(email) {
        this.state.currentEmail = email;
        
        // Mark as read if unread
        if (!email.isRead) {
            await this.markEmailAsRead(email.id);
        }

        if (this.state.isMobile) {
            this.showMobileEmailDetail(email);
        } else {
            this.showEmailDetail(email);
        }
    },

    // Show email detail (desktop)
    showEmailDetail(email) {
        const emailDetail = Utils.$('#email-detail');
        if (!emailDetail) return;

        emailDetail.innerHTML = '';
        emailDetail.appendChild(Components.createEmailDetail(email));
    },

    // Show mobile email detail
    showMobileEmailDetail(email) {
        const mobileDetail = Components.createMobileEmailDetail(email);
        document.body.appendChild(mobileDetail);
        
        // Animate in
        Utils.fadeIn(mobileDetail);
        
        // Handle back button
        const backBtn = mobileDetail.querySelector('.mobile-back-btn');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                this.closeMobileEmailDetail(mobileDetail);
            });
        }
    },

    // Close mobile email detail
    closeMobileEmailDetail(element) {
        Utils.fadeOut(element, 300);
        setTimeout(() => {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    },

    // Toggle email star
    async toggleEmailStar(emailId) {
        try {
            const email = this.state.emails.find(e => e.id === emailId);
            if (!email) return;

            const newStarState = !email.isStarred;
            
            // Update local state
            email.isStarred = newStarState;
            
            // Update UI
            const starBtn = Utils.$(`.email-star[data-email-id="${emailId}"]`);
            if (starBtn) {
                if (newStarState) {
                    Utils.addClass(starBtn, 'starred');
                    starBtn.querySelector('svg').setAttribute('fill', 'currentColor');
                } else {
                    Utils.removeClass(starBtn, 'starred');
                    starBtn.querySelector('svg').setAttribute('fill', 'none');
                }
            }
            
            // Update counts
            Components.updateCounts(this.state.emails);
            
            // Sync with backend
            await EmailAPI.toggleEmailStar(emailId);
            
        } catch (error) {
            console.error('Failed to toggle star:', error);
            Utils.showToast('Failed to update star', 'error');
        }
    },

    // Toggle email selection
    toggleEmailSelection(emailId) {
        const index = this.state.selectedEmails.indexOf(emailId);
        if (index > -1) {
            this.state.selectedEmails.splice(index, 1);
        } else {
            this.state.selectedEmails.push(emailId);
        }
        
        this.updateSelectionUI();
    },

    // Select all emails
    selectAllEmails(select) {
        if (select) {
            this.state.selectedEmails = this.state.emails.map(e => e.id);
        } else {
            this.state.selectedEmails = [];
        }
        
        this.updateSelectionUI();
    },

    // Update selection UI
    updateSelectionUI() {
        const checkboxes = Utils.$$('.email-checkbox');
        const selectAllCheckbox = Utils.$('#select-all-checkbox');
        
        checkboxes.forEach(checkbox => {
            const emailId = checkbox.dataset.emailId;
            checkbox.checked = this.state.selectedEmails.includes(emailId);
        });
        
        if (selectAllCheckbox) {
            const totalEmails = this.state.emails.length;
            const selectedCount = this.state.selectedEmails.length;
            
            if (selectedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (selectedCount === totalEmails) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
    },

    // Mark email as read
    async markEmailAsRead(emailId) {
        try {
            const email = this.state.emails.find(e => e.id === emailId);
            if (!email) return;

            email.isRead = true;
            
            // Update UI
            const emailItem = Utils.$(`.email-item[data-email-id="${emailId}"]`);
            if (emailItem) {
                Utils.removeClass(emailItem, 'unread');
            }
            
            // Update counts
            Components.updateCounts(this.state.emails);
            
            // Sync with backend
            await EmailAPI.markEmailsAsRead([emailId]);
            
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    },

    // Open compose modal
    openComposeModal() {
        const modal = Utils.$('#compose-modal');
        if (!modal) return;

        Utils.removeClass(modal, 'hidden');
        Utils.fadeIn(modal);
        
        // Focus on recipient field
        setTimeout(() => {
            const toInput = Utils.$('#compose-to');
            if (toInput) {
                toInput.focus();
            }
        }, 300);
        
        this.state.isComposeOpen = true;
    },

    // Close compose modal
    closeComposeModal() {
        const modal = Utils.$('#compose-modal');
        if (!modal) return;

        Utils.fadeOut(modal, 300);
        setTimeout(() => {
            Utils.addClass(modal, 'hidden');
            this.clearComposeForm();
        }, 300);
        
        this.state.isComposeOpen = false;
    },

    // Clear compose form
    clearComposeForm() {
        const toInput = Utils.$('#compose-to');
        const subjectInput = Utils.$('#compose-subject');
        const bodyTextarea = Utils.$('#compose-body');
        
        if (toInput) toInput.value = '';
        if (subjectInput) subjectInput.value = '';
        if (bodyTextarea) bodyTextarea.value = '';
    },

    // Send email
    async sendEmail() {
        const toInput = Utils.$('#compose-to');
        const subjectInput = Utils.$('#compose-subject');
        const bodyTextarea = Utils.$('#compose-body');
        
        if (!toInput || !subjectInput || !bodyTextarea) return;
        
        const to = toInput.value.trim();
        const subject = subjectInput.value.trim();
        const body = bodyTextarea.value.trim();
        
        // Validation
        if (!to) {
            Utils.showToast('Please enter recipient', 'error');
            toInput.focus();
            return;
        }
        
        if (!Utils.validateEmail(to)) {
            Utils.showToast('Please enter a valid email address', 'error');
            toInput.focus();
            return;
        }
        
        if (!subject) {
            Utils.showToast('Please enter subject', 'error');
            subjectInput.focus();
            return;
        }
        
        if (!body) {
            Utils.showToast('Please enter message', 'error');
            bodyTextarea.focus();
            return;
        }
        
        try {
            const emailData = {
                to: to,
                subject: subject,
                content: body,
                from: 'Me',
                fromEmail: 'me@example.com'
            };
            
            await EmailAPI.createEmail(emailData);
            
            this.closeComposeModal();
            this.loadEmails(); // Refresh email list
            
            Utils.showToast('Email sent successfully', 'success');
            
        } catch (error) {
            console.error('Failed to send email:', error);
            Utils.showToast('Failed to send email', 'error');
        }
    },

    // Handle keyboard shortcuts
    handleKeyboardShortcuts(e) {
        // Ignore if user is typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        const key = e.key.toLowerCase();
        const ctrl = e.ctrlKey || e.metaKey;
        
        // Compose (C)
        if (key === 'c' && !ctrl) {
            e.preventDefault();
            this.openComposeModal();
        }
        
        // Search (/)
        if (key === '/' && !ctrl) {
            e.preventDefault();
            const searchInput = Utils.$('#search-input');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Refresh (R)
        if (key === 'r' && !ctrl) {
            e.preventDefault();
            this.refreshEmails();
        }
        
        // Select all (Ctrl+A)
        if (ctrl && key === 'a') {
            e.preventDefault();
            this.selectAllEmails(true);
        }
        
        // Escape (close modals)
        if (key === 'escape') {
            if (this.state.isComposeOpen) {
                this.closeComposeModal();
            }
            if (this.state.isSidebarOpen && this.state.isMobile) {
                this.closeSidebar();
            }
        }
    },

    // Show loading state
    showLoadingState() {
        const emailList = Utils.$('#email-list');
        if (!emailList) return;

        emailList.innerHTML = '';
        emailList.appendChild(Components.createLoadingState('Loading emails...'));
    },

    // Show error state
    showErrorState(title, message) {
        const emailList = Utils.$('#email-list');
        if (!emailList) return;

        emailList.innerHTML = '';
        emailList.appendChild(Components.createErrorState(
            title,
            message,
            () => this.loadEmails()
        ));
    }
};

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Export for global access
window.App = App;
window.Utils = Utils;
window.Components = Components;
window.EmailAPI = EmailAPI;