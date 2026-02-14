/**
 * TOAST NOTIFICATION MANAGER
 * Beautiful, customizable toast notifications
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.init();
    }

    init() {
        // Create toast container
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    }

    /**
     * Show a toast notification
     * @param {Object} options - Toast options
     * @param {string} options.title - Toast title
     * @param {string} options.message - Toast message
     * @param {string} options.type - Toast type (success, error, warning, info)
     * @param {number} options.duration - Duration in ms (0 for persistent)
     * @param {boolean} options.closable - Show close button
     */
    show(options = {}) {
        const toast = {
            id: Date.now() + Math.random(),
            title: options.title || '',
            message: options.message || '',
            type: options.type || 'info',
            duration: options.duration !== undefined ? options.duration : 4000,
            closable: options.closable !== undefined ? options.closable : true
        };

        this.toasts.push(toast);
        this.render(toast);

        // Auto-dismiss if duration is set
        if (toast.duration > 0) {
            setTimeout(() => {
                this.dismiss(toast.id);
            }, toast.duration);
        }

        return toast.id;
    }

    render(toast) {
        const toastEl = document.createElement('div');
        toastEl.className = `toast toast-${toast.type}`;
        toastEl.dataset.toastId = toast.id;

        // Icon based on type
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const icon = icons[toast.type] || icons.info;

        toastEl.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icon}"></i>
            </div>
            <div class="toast-content">
                ${toast.title ? `<div class="toast-title">${this.escapeHtml(toast.title)}</div>` : ''}
                <div class="toast-message">${this.escapeHtml(toast.message)}</div>
            </div>
            ${toast.closable ? '<button class="toast-close"><i class="fas fa-times"></i></button>' : ''}
        `;

        // Add close button handler
        if (toast.closable) {
            toastEl.querySelector('.toast-close').addEventListener('click', () => {
                this.dismiss(toast.id);
            });
        }

        // Add to container
        this.container.appendChild(toastEl);

        // Trigger animation
        setTimeout(() => {
            toastEl.classList.add('toast-show');
        }, 10);
    }

    dismiss(toastId) {
        const toastEl = this.container.querySelector(`[data-toast-id="${toastId}"]`);
        if (!toastEl) return;

        toastEl.classList.remove('toast-show');
        toastEl.classList.add('toast-hide');

        setTimeout(() => {
            toastEl.remove();
            this.toasts = this.toasts.filter(t => t.id !== toastId);
        }, 300);
    }

    dismissAll() {
        this.toasts.forEach(toast => {
            this.dismiss(toast.id);
        });
    }

    // Helper methods
    success(message, title = 'Success') {
        return this.show({ title, message, type: 'success' });
    }

    error(message, title = 'Error') {
        return this.show({ title, message, type: 'error' });
    }

    warning(message, title = 'Warning') {
        return this.show({ title, message, type: 'warning' });
    }

    info(message, title = '') {
        return this.show({ title, message, type: 'info' });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize toast manager
window.toastManager = new ToastManager();

// Add CSS styles
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 400px;
    }
    
    .toast {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border-radius: 8px;
        background: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        border-left: 4px solid;
        min-width: 300px;
    }
    
    .toast-show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .toast-hide {
        opacity: 0;
        transform: translateX(400px);
    }
    
    .toast-success {
        border-left-color: #27ae60;
    }
    
    .toast-error {
        border-left-color: #e74c3c;
    }
    
    .toast-warning {
        border-left-color: #f39c12;
    }
    
    .toast-info {
        border-left-color: #3498db;
    }
    
    .toast-icon {
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .toast-success .toast-icon {
        color: #27ae60;
    }
    
    .toast-error .toast-icon {
        color: #e74c3c;
    }
    
    .toast-warning .toast-icon {
        color: #f39c12;
    }
    
    .toast-info .toast-icon {
        color: #3498db;
    }
    
    .toast-content {
        flex: 1;
    }
    
    .toast-title {
        font-weight: 600;
        font-size: 14px;
        color: #2c3e50;
        margin-bottom: 4px;
    }
    
    .toast-message {
        font-size: 13px;
        color: #5a6c7d;
        line-height: 1.4;
    }
    
    .toast-close {
        background: none;
        border: none;
        color: #95a5a6;
        cursor: pointer;
        font-size: 16px;
        padding: 0;
        margin-left: 8px;
        flex-shrink: 0;
        transition: color 0.2s;
    }
    
    .toast-close:hover {
        color: #2c3e50;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .toast-container {
            left: 10px;
            right: 10px;
            top: 10px;
            max-width: none;
        }
        
        .toast {
            min-width: auto;
            width: 100%;
        }
    }
`;
document.head.appendChild(toastStyles);
