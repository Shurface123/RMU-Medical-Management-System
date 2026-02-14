/**
 * REAL-TIME NOTIFICATION SYSTEM
 * Polls server for new notifications and displays them
 */

class NotificationSystem {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 30000; // 30 seconds
        this.userId = options.userId;
        this.onNewNotification = options.onNewNotification || null;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastNotificationId = 0;
        this.unreadCount = 0;

        this.init();
    }

    init() {
        // Load last notification ID from localStorage
        const stored = localStorage.getItem('lastNotificationId');
        if (stored) {
            this.lastNotificationId = parseInt(stored);
        }

        // Start polling
        this.startPolling();

        // Set up visibility change handler
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.startPolling();
            }
        });
    }

    startPolling() {
        if (this.isPolling) return;

        this.isPolling = true;
        this.checkNotifications(); // Check immediately

        this.pollTimer = setInterval(() => {
            this.checkNotifications();
        }, this.pollInterval);
    }

    stopPolling() {
        if (!this.isPolling) return;

        this.isPolling = false;
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    async checkNotifications() {
        try {
            const response = await fetch('php/api/get_notifications.php?since=' + this.lastNotificationId);
            const data = await response.json();

            if (data.success && data.notifications.length > 0) {
                // Process new notifications
                data.notifications.forEach(notification => {
                    this.handleNewNotification(notification);
                });

                // Update last notification ID
                const maxId = Math.max(...data.notifications.map(n => n.id));
                this.lastNotificationId = maxId;
                localStorage.setItem('lastNotificationId', maxId);
            }

            // Update unread count
            if (data.unread_count !== undefined) {
                this.updateUnreadCount(data.unread_count);
            }
        } catch (error) {
            console.error('Failed to check notifications:', error);
        }
    }

    handleNewNotification(notification) {
        // Show toast notification
        if (window.toastManager) {
            window.toastManager.show({
                title: notification.title,
                message: notification.message,
                type: notification.type || 'info',
                duration: 5000
            });
        }

        // Show desktop notification if permitted
        this.showDesktopNotification(notification);

        // Call custom callback
        if (this.onNewNotification) {
            this.onNewNotification(notification);
        }

        // Play notification sound (optional)
        this.playNotificationSound();
    }

    showDesktopNotification(notification) {
        if (!('Notification' in window)) return;

        if (Notification.permission === 'granted') {
            new Notification(notification.title, {
                body: notification.message,
                icon: '/RMU-Medical-Management-System/images/logo.png',
                badge: '/RMU-Medical-Management-System/images/badge.png',
                tag: 'notification-' + notification.id,
                requireInteraction: false
            });
        }
    }

    playNotificationSound() {
        // Optional: play a subtle notification sound
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZSA0PVqzn77BdGAg+ltryxnwmBSh+zPLaizsIGGS56eaeSxELTKXh8bllHAU2jdXzzn4qBSZ7yvLekjsHGWm98t+YRwwQV63o8LJfGgdAm9vwwXkjBi6Cz/PTfy0GI3fJ8N6URQ0RXLLp6KlXFgtIouDwtGIcBziP1vPNfCsEKH/N8tuLOwgZZ7rp5ZxLEAxLpOHxuWUcBTaN1fPPgCsGJnvK8t+UOwgZaLzx3plIDhBXrOnwsmAbBz+b2/HBeSQGLoDP89GALAYjdsrw34tBDhFbsunoqVkWCUmi4PG1ZRsGOI/W88p7KwUof8324YtCDRdmsOjnm0kPDVCm4/K4bCgNRqPi8r5uJAlBndzx0n0pDSJ1xe7bfzQKGV+z6OWkUhYLSaLh8LVkGwY4jtWz0n4rBSh+zPLflUQOEV2y6eipVxYKSKPh8LRiHAc5jtXzz38rBSh/zvLdlEMNEmGz6OaoWRULSqPh8LNhGwU2jdXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy6OepWBYLSKPh8LNhGwU2jNXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy6OepWBYLSKPh8LNhGwU2jNXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy6OepWBYLSKPh8LNhGwU2jNXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy6OepWBYLSKPh8LNhGwU2jNXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy6OepWBYLSKPh8LNhGwU2jNXzzn4qBSZ7yvLfmEcOEV+z6eapWRULSaLh8LRhHAU4jtXz0H8rBSd+zPLdlUMNEmGy');
            audio.volume = 0.3;
            audio.play().catch(() => { }); // Ignore errors
        } catch (e) {
            // Ignore
        }
    }

    updateUnreadCount(count) {
        this.unreadCount = count;

        // Update badge counter
        if (window.badgeManager) {
            window.badgeManager.update('notifications', count);
        }

        // Update page title
        if (count > 0) {
            document.title = `(${count}) RMU Medical Sickbay`;
        } else {
            document.title = 'RMU Medical Sickbay';
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('php/api/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notification_id: notificationId })
            });

            const data = await response.json();
            if (data.success) {
                this.checkNotifications(); // Refresh count
            }
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('php/api/mark_all_notifications_read.php', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                this.updateUnreadCount(0);
            }
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    }

    requestDesktopPermission() {
        if (!('Notification' in window)) {
            console.log('Desktop notifications not supported');
            return;
        }

        if (Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    console.log('Desktop notifications enabled');
                }
            });
        }
    }
}

// Auto-initialize if user is logged in
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in (you can customize this check)
    if (document.body.dataset.userId) {
        window.notificationSystem = new NotificationSystem({
            userId: document.body.dataset.userId,
            pollInterval: 30000 // 30 seconds
        });
    }
});
