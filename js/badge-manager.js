/**
 * BADGE COUNTER MANAGER
 * Manages badge counters for notifications, messages, etc.
 */

class BadgeManager {
    constructor() {
        this.badges = new Map();
        this.init();
    }

    init() {
        // Load saved badge counts from localStorage
        const saved = localStorage.getItem('badgeCounts');
        if (saved) {
            try {
                const counts = JSON.parse(saved);
                Object.entries(counts).forEach(([key, value]) => {
                    this.badges.set(key, value);
                });
            } catch (e) {
                // Ignore parse errors
            }
        }
    }

    /**
     * Update badge count
     * @param {string} badgeId - Badge identifier
     * @param {number} count - New count
     */
    update(badgeId, count) {
        const oldCount = this.badges.get(badgeId) || 0;
        this.badges.set(badgeId, count);

        // Save to localStorage
        this.save();

        // Update all badge elements with this ID
        const elements = document.querySelectorAll(`[data-badge="${badgeId}"]`);
        elements.forEach(el => {
            this.renderBadge(el, count);
        });

        // Trigger animation if count increased
        if (count > oldCount) {
            elements.forEach(el => {
                const badge = el.querySelector('.badge-counter');
                if (badge) {
                    badge.classList.add('badge-pulse');
                    setTimeout(() => {
                        badge.classList.remove('badge-pulse');
                    }, 600);
                }
            });
        }

        return count;
    }

    /**
     * Increment badge count
     * @param {string} badgeId - Badge identifier
     * @param {number} amount - Amount to increment (default: 1)
     */
    increment(badgeId, amount = 1) {
        const currentCount = this.badges.get(badgeId) || 0;
        return this.update(badgeId, currentCount + amount);
    }

    /**
     * Decrement badge count
     * @param {string} badgeId - Badge identifier
     * @param {number} amount - Amount to decrement (default: 1)
     */
    decrement(badgeId, amount = 1) {
        const currentCount = this.badges.get(badgeId) || 0;
        return this.update(badgeId, Math.max(0, currentCount - amount));
    }

    /**
     * Reset badge count to zero
     * @param {string} badgeId - Badge identifier
     */
    reset(badgeId) {
        return this.update(badgeId, 0);
    }

    /**
     * Get current badge count
     * @param {string} badgeId - Badge identifier
     */
    get(badgeId) {
        return this.badges.get(badgeId) || 0;
    }

    renderBadge(element, count) {
        // Remove existing badge
        const existingBadge = element.querySelector('.badge-counter');
        if (existingBadge) {
            existingBadge.remove();
        }

        // Add new badge if count > 0
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'badge-counter';
            badge.textContent = count > 99 ? '99+' : count;
            element.style.position = 'relative';
            element.appendChild(badge);
        }
    }

    save() {
        const counts = {};
        this.badges.forEach((value, key) => {
            counts[key] = value;
        });
        localStorage.setItem('badgeCounts', JSON.stringify(counts));
    }

    /**
     * Initialize all badge elements on the page
     */
    initializeAll() {
        document.querySelectorAll('[data-badge]').forEach(el => {
            const badgeId = el.dataset.badge;
            const count = this.get(badgeId);
            this.renderBadge(el, count);
        });
    }
}

// Initialize badge manager
window.badgeManager = new BadgeManager();

// Auto-initialize badges on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.badgeManager.initializeAll();
});

// Add CSS styles
const badgeStyles = document.createElement('style');
badgeStyles.textContent = `
    .badge-counter {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #e74c3c;
        color: white;
        font-size: 10px;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 10;
        line-height: 1;
    }
    
    @keyframes badgePulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
        }
    }
    
    .badge-pulse {
        animation: badgePulse 0.6s ease-in-out;
    }
    
    /* Different badge colors for different contexts */
    .badge-counter.badge-success {
        background: #27ae60;
    }
    
    .badge-counter.badge-warning {
        background: #f39c12;
    }
    
    .badge-counter.badge-info {
        background: #3498db;
    }
    
    .badge-counter.badge-primary {
        background: #9b59b6;
    }
`;
document.head.appendChild(badgeStyles);
