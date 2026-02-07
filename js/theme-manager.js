// ===================================
// THEME MANAGER
// Handles dark/light theme switching
// ===================================

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || this.getSystemTheme();
        this.init();
    }

    init() {
        // Apply theme on page load
        this.applyTheme(this.currentTheme);

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!this.getStoredTheme()) {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    getSystemTheme() {
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    getStoredTheme() {
        // Get theme from localStorage
        return localStorage.getItem('rmu-theme');
    }

    setStoredTheme(theme) {
        // Save theme to localStorage
        localStorage.setItem('rmu-theme', theme);
    }

    applyTheme(theme) {
        // Remove existing theme
        document.documentElement.classList.remove('light-theme', 'dark-theme');

        // Add new theme
        document.documentElement.classList.add(`${theme}-theme`);

        // Update data attribute for CSS targeting
        document.documentElement.setAttribute('data-theme', theme);

        // Update current theme
        this.currentTheme = theme;

        // Update toggle button icon if it exists
        this.updateToggleButton();

        // Dispatch custom event for other scripts
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme(newTheme);
        this.setStoredTheme(newTheme);
    }

    updateToggleButton() {
        const toggleBtn = document.getElementById('theme-toggle');
        if (!toggleBtn) return;

        const icon = toggleBtn.querySelector('i');
        if (!icon) return;

        // Update icon based on current theme
        if (this.currentTheme === 'dark') {
            icon.className = 'fas fa-sun';
            toggleBtn.setAttribute('title', 'Switch to Light Mode');
        } else {
            icon.className = 'fas fa-moon';
            toggleBtn.setAttribute('title', 'Switch to Dark Mode');
        }
    }

    getCurrentTheme() {
        return this.currentTheme;
    }
}

// ===================================
// INITIALIZE THEME MANAGER
// ===================================

// Create global instance
const themeManager = new ThemeManager();

// Add toggle button click handler when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('theme-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            themeManager.toggleTheme();
        });
    }
});

// ===================================
// HELPER FUNCTIONS
// ===================================

/**
 * Get current theme
 */
function getCurrentTheme() {
    return themeManager.getCurrentTheme();
}

/**
 * Set specific theme
 */
function setTheme(theme) {
    if (theme === 'light' || theme === 'dark') {
        themeManager.applyTheme(theme);
        themeManager.setStoredTheme(theme);
    }
}

/**
 * Toggle between themes
 */
function toggleTheme() {
    themeManager.toggleTheme();
}
