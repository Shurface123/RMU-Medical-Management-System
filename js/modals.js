// ===================================
// MODAL SYSTEM
// ===================================

class ModalSystem {
    constructor() {
        this.modals = new Map();
        this.init();
    }

    init() {
        // Find all modals on the page
        document.querySelectorAll('.modal').forEach(modal => {
            const modalId = modal.id;
            if (modalId) {
                this.modals.set(modalId, modal);
                this.attachModalListeners(modal);
            }
        });

        // Attach listeners to modal triggers
        document.querySelectorAll('[data-modal-target]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = trigger.dataset.modalTarget;
                this.openModal(targetId);
            });
        });
    }

    attachModalListeners(modal) {
        // Close button
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.closeModal(modal.id);
            });
        }

        // Click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modal.id);
            }
        });

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                this.closeModal(modal.id);
            }
        });
    }

    openModal(modalId) {
        const modal = this.modals.get(modalId) || document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
    }

    closeModal(modalId) {
        const modal = this.modals.get(modalId) || document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
    }

    createModal(options) {
        const {
            id,
            title,
            content,
            footer,
            size = 'medium'
        } = options;

        const modalHTML = `
            <div class="modal" id="${id}">
                <div class="modal-content modal-${size}">
                    <div class="modal-header">
                        <h3 class="modal-title">${title}</h3>
                        <button class="modal-close" aria-label="Close modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById(id);
        this.modals.set(id, modal);
        this.attachModalListeners(modal);

        return modal;
    }

    showConfirmation(options) {
        const {
            title = 'Confirm Action',
            message,
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            onConfirm,
            onCancel
        } = options;

        const modalId = 'confirmModal_' + Date.now();

        const footer = `
            <button class="btn btn-outline" id="${modalId}_cancel">${cancelText}</button>
            <button class="btn btn-primary" id="${modalId}_confirm">${confirmText}</button>
        `;

        const modal = this.createModal({
            id: modalId,
            title: title,
            content: `<p>${message}</p>`,
            footer: footer,
            size: 'small'
        });

        this.openModal(modalId);

        // Attach button listeners
        document.getElementById(`${modalId}_confirm`).addEventListener('click', () => {
            if (onConfirm) onConfirm();
            this.closeModal(modalId);
            setTimeout(() => modal.remove(), 300);
        });

        document.getElementById(`${modalId}_cancel`).addEventListener('click', () => {
            if (onCancel) onCancel();
            this.closeModal(modalId);
            setTimeout(() => modal.remove(), 300);
        });
    }

    showAlert(options) {
        const {
            title = 'Alert',
            message,
            type = 'info', // success, error, warning, info
            buttonText = 'OK'
        } = options;

        const modalId = 'alertModal_' + Date.now();

        const icons = {
            success: '<i class="fas fa-check-circle" style="color: #16a085; font-size: 4rem;"></i>',
            error: '<i class="fas fa-exclamation-circle" style="color: #e74c3c; font-size: 4rem;"></i>',
            warning: '<i class="fas fa-exclamation-triangle" style="color: #f39c12; font-size: 4rem;"></i>',
            info: '<i class="fas fa-info-circle" style="color: #3498db; font-size: 4rem;"></i>'
        };

        const content = `
            <div style="text-align: center;">
                ${icons[type]}
                <p style="margin-top: 2rem; font-size: 1.6rem;">${message}</p>
            </div>
        `;

        const footer = `
            <button class="btn btn-primary" id="${modalId}_ok" style="width: 100%;">${buttonText}</button>
        `;

        const modal = this.createModal({
            id: modalId,
            title: title,
            content: content,
            footer: footer,
            size: 'small'
        });

        this.openModal(modalId);

        document.getElementById(`${modalId}_ok`).addEventListener('click', () => {
            this.closeModal(modalId);
            setTimeout(() => modal.remove(), 300);
        });
    }
}

// Initialize modal system
document.addEventListener('DOMContentLoaded', () => {
    window.modalSystem = new ModalSystem();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalSystem;
}
