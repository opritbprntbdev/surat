const Components = {
    // Email Item Component
    createEmailItem(email) {
        const div = document.createElement('div');
        div.className = `email-item ${email.isRead ? '' : 'unread'}`;
        div.dataset.emailId = email.id;
        
        div.innerHTML = `
            <input type="checkbox" class="email-checkbox" data-email-id="${email.id}">
            <button class="email-star ${email.isStarred ? 'starred' : ''}" data-email-id="${email.id}">
                <svg class="icon" fill="${email.isStarred ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            </button>
            <div class="email-content-wrapper">
                <div class="email-avatar">${Utils.getInitials(email.from)}</div>
                <div class="email-info">
                    <div class="email-header">
                        <div class="email-from">${Utils.escapeHtml(email.from)}</div>
                        <div class="email-date">${Utils.formatDate(email.date)}</div>
                    </div>
                    <div class="email-subject">${Utils.escapeHtml(email.subject)}</div>
                    <div class="email-preview">${Utils.escapeHtml(email.preview)}</div>
                    ${email.labels && email.labels.length > 0 ? `
                        <div class="email-labels">
                            ${email.labels.map(label => `
                                <span class="email-label ${label.toLowerCase()}">${Utils.escapeHtml(label)}</span>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        return div;
    },

    // Email Detail Component
    createEmailDetail(email) {
        const div = document.createElement('div');
        div.className = 'email-detail-content';
        
        div.innerHTML = `
            <div class="email-detail-header">
                <h2 class="email-detail-subject">${Utils.escapeHtml(email.subject)}</h2>
                <div class="email-detail-meta">
                    <div class="email-detail-from">
                        <div class="email-detail-avatar">${Utils.getInitials(email.from)}</div>
                        <div class="email-detail-from-info">
                            <h4>${Utils.escapeHtml(email.from)}</h4>
                            <p>&lt;${Utils.escapeHtml(email.fromEmail)}&gt;</p>
                        </div>
                    </div>
                    <div class="email-detail-date">${Utils.formatDate(email.date)}</div>
                </div>
                <div class="email-detail-actions">
                    <button class="email-detail-action-btn" title="Reply">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="Reply All">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6m6 6h6m-6 6v6m0-6l-6-6"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="Forward">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="Archive">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="Delete">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="More">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                        </svg>
                    </button>
                </div>
                ${email.cc ? `
                    <div class="email-detail-to">
                        <strong>cc:</strong> ${Utils.escapeHtml(email.cc.join(', '))}
                    </div>
                ` : ''}
            </div>
            <div class="email-detail-body">
                ${email.content.replace(/\n/g, '<br>')}
            </div>
            ${email.attachments && email.attachments.length > 0 ? `
                <div class="email-attachments">
                    <h3 class="email-attachments-title">Attachments (${email.attachments.length})</h3>
                    <div class="email-attachment-list">
                        ${email.attachments.map(attachment => `
                            <div class="email-attachment" data-attachment-id="${attachment.id}">
                                <svg class="email-attachment-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <div class="email-attachment-info">
                                    <div class="email-attachment-name">${Utils.escapeHtml(attachment.name)}</div>
                                    <div class="email-attachment-size">${attachment.size}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        `;
        
        return div;
    },

    // Mobile Email Detail Component
    createMobileEmailDetail(email) {
        const div = document.createElement('div');
        div.className = 'mobile-email-detail';
        div.innerHTML = `
            <div class="mobile-email-detail-header">
                <button class="mobile-back-btn">
                    <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="mobile-email-detail-actions">
                    <button class="email-detail-action-btn" title="Archive">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                    </button>
                    <button class="email-detail-action-btn" title="Delete">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="mobile-email-detail-content">
                ${this.createEmailDetail(email).innerHTML}
            </div>
        `;
        return div;
    },

    // Empty State Component
    createEmptyState(title, message, icon = 'mail') {
        const div = document.createElement('div');
        div.className = 'empty-state';
        
        const iconSvg = this.getIconSvg(icon);
        
        div.innerHTML = `
            ${iconSvg}
            <h3>${title}</h3>
            <p>${message}</p>
        `;
        
        return div;
    },

    // Loading State Component
    createLoadingState(message = 'Loading...') {
        const div = document.createElement('div');
        div.className = 'email-loading';
        div.innerHTML = `
            <div class="spinner"></div>
            <span>${message}</span>
        `;
        return div;
    },

    // Error State Component
    createErrorState(title, message, retryCallback) {
        const div = document.createElement('div');
        div.className = 'email-error';
        div.innerHTML = `
            <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3>${title}</h3>
            <p>${message}</p>
            <button class="retry-btn">Try Again</button>
        `;
        
        if (retryCallback) {
            const retryBtn = div.querySelector('.retry-btn');
            retryBtn.addEventListener('click', retryCallback);
        }
        
        return div;
    },

    // Icon SVG helper
    getIconSvg(iconName) {
        const icons = {
            mail: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>',
            search: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>',
            inbox: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>',
            send: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>',
            star: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>',
            archive: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>',
            trash: '<svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>'
        };
        
        return icons[iconName] || icons.mail;
    },

    // Update counts in UI
    updateCounts(emails) {
        const counts = {
            inbox: emails.filter(e => !e.isRead).length,
            starred: emails.filter(e => e.isStarred).length,
            social: emails.filter(e => e.category === 'social').length,
            promotions: emails.filter(e => e.category === 'promotions').length,
            updates: emails.filter(e => e.category === 'updates').length
        };

        // Update sidebar counts
        const inboxCount = Utils.$('#inbox-count');
        const starredCount = Utils.$('#starred-count');
        const socialCount = Utils.$('#social-count');
        const promotionsCount = Utils.$('#promotions-count');
        const updatesCount = Utils.$('#updates-count');

        if (inboxCount) inboxCount.textContent = counts.inbox;
        if (starredCount) starredCount.textContent = counts.starred;
        if (socialCount) socialCount.textContent = counts.social;
        if (promotionsCount) promotionsCount.textContent = counts.promotions;
        if (updatesCount) updatesCount.textContent = counts.updates;

        // Update email count in toolbar
        const emailCount = Utils.$('#email-count');
        if (emailCount) {
            const total = emails.length;
            emailCount.textContent = `1-${Math.min(50, total)} of ${total}`;
        }
    },

    // Show/hide mobile compose button
    toggleMobileCompose(show = true) {
        const existingBtn = Utils.$('.mobile-compose');
        
        if (show && !existingBtn) {
            const btn = document.createElement('button');
            btn.className = 'mobile-compose';
            btn.innerHTML = `
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            `;
            document.body.appendChild(btn);
            
            btn.addEventListener('click', () => {
                App.openComposeModal();
            });
        } else if (!show && existingBtn) {
            existingBtn.remove();
        }
    },

    // Initialize touch gestures
    initTouchGestures() {
        if (!Utils.isTouchDevice()) return;

        let touchStartX = 0;
        let touchEndX = 0;
        let touchStartY = 0;
        let touchEndY = 0;

        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        });

        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            touchEndY = e.changedTouches[0].screenY;
            this.handleSwipeGesture(touchStartX, touchEndX, touchStartY, touchEndY);
        });
    },

    // Handle swipe gestures
    handleSwipeGesture(startX, endX, startY, endY) {
        const swipeThreshold = 50;
        const diffX = startX - endX;
        const diffY = Math.abs(startY - endY);

        // Only handle horizontal swipes
        if (diffY > swipeThreshold) return;

        const sidebar = Utils.$('.sidebar');
        const sidebarOverlay = Utils.$('#sidebar-overlay');
        const isSidebarOpen = Utils.hasClass(sidebar, 'open');

        // Swipe right from left edge to open sidebar
        if (startX < 20 && diffX < -swipeThreshold && !isSidebarOpen) {
            Utils.addClass(sidebar, 'open');
            Utils.removeClass(sidebarOverlay, 'hidden');
        }
        // Swipe left to close sidebar
        else if (diffX > swipeThreshold && isSidebarOpen) {
            Utils.removeClass(sidebar, 'open');
            Utils.addClass(sidebarOverlay, 'hidden');
        }
    },

    // Initialize component
    init() {
        this.initTouchGestures();
        this.toggleMobileCompose(Utils.isMobile());
        
        // Handle window resize
        window.addEventListener('resize', Utils.debounce(() => {
            this.toggleMobileCompose(Utils.isMobile());
        }, 250));
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Components;
}