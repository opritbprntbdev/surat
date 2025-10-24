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

    // Date utilities
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            // Today - show time
            return date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
        } else if (diffDays === 1) {
            // Yesterday
            return 'Yesterday';
        } else if (diffDays < 7) {
            // This week
            return date.toLocaleDateString('en-US', { 
                weekday: 'short' 
            });
        } else if (diffDays < 365) {
            // This year
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            });
        } else {
            // Older
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            });
        }
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

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Utils;
}