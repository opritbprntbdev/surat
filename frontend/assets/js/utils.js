const Utils = {
    // DOM Utilities
    $(selector) {
        return document.querySelector(selector);
    },

    $$(selector) {
        return document.querySelectorAll(selector);
    },

    // Event delegation
    on(element, event, selector, handler) {
        element.addEventListener(event, (e) => {
            if (e.target.matches(selector)) {
                handler(e);
            }
        });
    },

    // Class utilities
    addClass(element, className) {
        element.classList.add(className);
    },

    removeClass(element, className) {
        element.classList.remove(className);
    },

    toggleClass(element, className) {
        element.classList.toggle(className);
    },

    hasClass(element, className) {
        return element.classList.contains(className);
    },

    // Storage utilities
    setItem(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Error saving to localStorage:', e);
        }
    },

    getItem(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return null;
        }
    },

    removeItem(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('Error removing from localStorage:', e);
        }
    },

    // Date utilities (Indonesia, WITA/GMT+8 Makassar)
    formatDate(dateString) {
        if (!dateString) return '';
        const LOCALE = 'id-ID';
        const TZ = 'Asia/Makassar'; // WITA (GMT+8)

        // Robust date parsing for MySQL formats ('YYYY-MM-DD' or 'YYYY-MM-DD HH:MM:SS')
        let date = new Date(dateString);
        if (isNaN(date.getTime())) {
            // Try replace space with 'T' for better parsing
            const isoLike = String(dateString).replace(' ', 'T');
            date = new Date(isoLike);
        }
        if (isNaN(date.getTime())) return '';

        // Helper: get Y-M-D string in target timezone
        const ymdInTZ = (d) => {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: TZ,
                year: 'numeric', month: '2-digit', day: '2-digit'
            }).formatToParts(d);
            const map = Object.fromEntries(parts.map(p => [p.type, p.value]));
            return `${map.year}-${map.month}-${map.day}`; // YYYY-MM-DD
        };
        const todayYMD = ymdInTZ(new Date());
        const dateYMD = ymdInTZ(date);

        // Convert YMD (in TZ) to a UTC date for safe day-diff calc
        const ymdToUTCDate = (ymd) => {
            const [y, m, d] = ymd.split('-').map(Number);
            return new Date(Date.UTC(y, m - 1, d));
        };
        const diffDays = Math.floor((ymdToUTCDate(todayYMD) - ymdToUTCDate(dateYMD)) / 86400000);

        // Today: show time HH:mm (24h)
        if (diffDays === 0) {
            return new Intl.DateTimeFormat(LOCALE, {
                timeZone: TZ,
                hour: '2-digit', minute: '2-digit', hour12: false
            }).format(date);
        }
        // Yesterday
        if (diffDays === 1) {
            return 'Kemarin';
        }
        // Same week (within last 7 days): show weekday in Indonesian
        if (diffDays > 1 && diffDays < 7) {
            return new Intl.DateTimeFormat(LOCALE, {
                timeZone: TZ,
                weekday: 'long'
            }).format(date);
        }

        // Same year: d MMM (e.g., 29 Okt)
        const nowYear = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric' }).format(new Date());
        const dateYear = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric' }).format(date);
        if (nowYear === dateYear) {
            return new Intl.DateTimeFormat(LOCALE, {
                timeZone: TZ,
                day: 'numeric', month: 'short'
            }).format(date);
        }

        // Older: d MMM yyyy
        return new Intl.DateTimeFormat(LOCALE, {
            timeZone: TZ,
            day: 'numeric', month: 'short', year: 'numeric'
        }).format(date);
    },

    // Full date-time label with Indonesian weekday and exact time (WITA)
    // e.g., "Rabu, 29 Okt 2025 14.35" or "Kemarin, 28 Okt 2025 09.10" or "Hari ini, 08.25"
    formatDateTimeLabel(dateString) {
        if (!dateString) return '';
        const LOCALE = 'id-ID';
        const TZ = 'Asia/Makassar';

        let date = new Date(dateString);
        if (isNaN(date.getTime())) {
            const isoLike = String(dateString).replace(' ', 'T');
            date = new Date(isoLike);
        }
        if (isNaN(date.getTime())) return '';

        // Day diff in target timezone
        const ymdInTZ = (d) => {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit'
            }).formatToParts(d);
            const map = Object.fromEntries(parts.map(p => [p.type, p.value]));
            return `${map.year}-${map.month}-${map.day}`;
        };
        const todayYMD = ymdInTZ(new Date());
        const dateYMD = ymdInTZ(date);
        const ymdToUTCDate = (ymd) => {
            const [y, m, d] = ymd.split('-').map(Number);
            return new Date(Date.UTC(y, m - 1, d));
        };
        const diffDays = Math.floor((ymdToUTCDate(todayYMD) - ymdToUTCDate(dateYMD)) / 86400000);

        const fullDate = new Intl.DateTimeFormat(LOCALE, {
            timeZone: TZ, weekday: 'long', day: 'numeric', month: 'short', year: 'numeric'
        }).format(date);
        const time = new Intl.DateTimeFormat(LOCALE, {
            timeZone: TZ, hour: '2-digit', minute: '2-digit', hour12: false
        }).format(date).replace(':', '.'); // Use dot separator style (HH.mm)

        if (diffDays === 0) {
            // Today: "Hari ini, HH.mm" or include date if preferred
            return `Hari ini, ${time}`;
        }
        if (diffDays === 1) {
            // Yesterday: "Kemarin, d MMM yyyy HH.mm"
            // Reformat date without weekday for brevity
            const dateNoWeek = new Intl.DateTimeFormat(LOCALE, {
                timeZone: TZ, day: 'numeric', month: 'short', year: 'numeric'
            }).format(date);
            return `Kemarin, ${dateNoWeek} ${time}`;
        }

        // Default: "Rabu, 29 Okt 2025 14.35"
        return `${fullDate} ${time}`;
    },

    // Email utilities
    generateEmailId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    },

    getInitials(name) {
        return name
            .split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .join('')
            .substring(0, 2);
    },

    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    },

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // Mobile detection
    isMobile() {
        return window.innerWidth < 768;
    },

    // Touch detection
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    },

    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    // Animation utilities
    fadeIn(element, duration = 300) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        const start = performance.now();
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            element.style.opacity = progress;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        
        requestAnimationFrame(animate);
    },

    fadeOut(element, duration = 300) {
        const start = performance.now();
        const initialOpacity = parseFloat(window.getComputedStyle(element).opacity);
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            element.style.opacity = initialOpacity * (1 - progress);
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.display = 'none';
            }
        }
        
        requestAnimationFrame(animate);
    },

    slideIn(element, direction = 'left', duration = 300) {
        const start = performance.now();
        const initialTransform = direction === 'left' ? '-100%' : '100%';
        
        element.style.transform = `translateX(${initialTransform})`;
        element.style.display = 'block';
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentTransform = direction === 'left' 
                ? -100 + (100 * progress) 
                : 100 - (100 * progress);
            
            element.style.transform = `translateX(${currentTransform}%)`;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.transform = 'translateX(0)';
            }
        }
        
        requestAnimationFrame(animate);
    },

    slideOut(element, direction = 'left', duration = 300) {
        const start = performance.now();
        const finalTransform = direction === 'left' ? '-100%' : '100%';
        
        function animate(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentTransform = direction === 'left' 
                ? 0 - (100 * progress) 
                : 0 + (100 * progress);
            
            element.style.transform = `translateX(${currentTransform}%)`;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.display = 'none';
            }
        }
        
        requestAnimationFrame(animate);
    },

    // Loading utilities
    showLoading() {
        const spinner = Utils.$('#loading-spinner');
        if (spinner) {
            Utils.removeClass(spinner, 'hidden');
        }
    },

    hideLoading() {
        const spinner = Utils.$('#loading-spinner');
        if (spinner) {
            Utils.addClass(spinner, 'hidden');
        }
    },

    // Toast notification
    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        // Style the toast
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '20px',
            left: '50%',
            transform: 'translateX(-50%)',
            backgroundColor: type === 'error' ? '#d93025' : type === 'success' ? '#1e8e3e' : '#1a73e8',
            color: 'white',
            padding: '12px 24px',
            borderRadius: '4px',
            fontSize: '14px',
            fontWeight: '500',
            zIndex: '10000',
            boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
            transition: 'opacity 0.3s ease',
            opacity: '0'
        });
        
        document.body.appendChild(toast);
        
        // Fade in
        setTimeout(() => {
            toast.style.opacity = '1';
        }, 10);
        
        // Remove after duration
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    },

    // Validation utilities
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    validateRequired(value) {
        return value && value.trim().length > 0;
    },

    // URL utilities
    getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },

    setQueryParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.replaceState({}, '', url);
    },

    removeQueryParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.replaceState({}, '', url);
    },

    // Copy to clipboard
    copyToClipboard(text) {
        if (navigator.clipboard) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            return new Promise((resolve, reject) => {
                try {
                    document.execCommand('copy');
                    resolve();
                } catch (err) {
                    reject(err);
                } finally {
                    document.body.removeChild(textArea);
                }
            });
        }
    },

    // File utilities
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    getFileExtension(filename) {
        return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2);
    },

    // Color utilities
    stringToColor(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            hash = str.charCodeAt(i) + ((hash << 5) - hash);
        }
        
        const hue = hash % 360;
        return `hsl(${hue}, 70%, 60%)`;
    },

    // Performance utilities
    measurePerformance(name, fn) {
        const start = performance.now();
        const result = fn();
        const end = performance.now();
        console.log(`${name} took ${end - start} milliseconds`);
        return result;
    }
};

// Notification System
function showNotification(message, type = 'info', duration = 3000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Trigger animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove
    if (duration > 0) {
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
}

// Loading Overlay Functions
function showLoadingOverlay() {
    let overlay = document.getElementById('loadingOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

function hideLoadingOverlay() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Add notification styles to head if not exists
if (!document.querySelector('#notification-styles')) {
    const notificationStyles = document.createElement('style');
    notificationStyles.id = 'notification-styles';
    notificationStyles.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(60, 64, 67, 0.3);
            transform: translateX(400px);
            transition: transform 0.3s ease-out;
            opacity: 0;
        }
        
        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .notification-content {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        
        .notification-message {
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #5f6368;
            padding: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        
        .notification-close:hover {
            background-color: #f1f3f4;
        }
        
        .notification-success {
            border-left: 4px solid #34a853;
        }
        
        .notification-error {
            border-left: 4px solid #ea4335;
        }
        
        .notification-warning {
            border-left: 4px solid #fbbc04;
        }
        
        .notification-info {
            border-left: 4px solid #1a73e8;
        }
        
        @media (max-width: 768px) {
            .notification {
                left: 20px;
                right: 20px;
                max-width: none;
                transform: translateY(-100px);
            }
            
            .notification.show {
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(notificationStyles);
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Utils;
}