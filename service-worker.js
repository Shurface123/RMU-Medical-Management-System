/**
 * SERVICE WORKER FOR PWA
 * Handles caching and offline functionality
 */

const CACHE_NAME = 'rmu-medical-v1';
const urlsToCache = [
    '/RMU-Medical-Management-System/',
    '/RMU-Medical-Management-System/index.php',
    '/RMU-Medical-Management-System/css/style.css',
    '/RMU-Medical-Management-System/js/toast-manager.js',
    '/RMU-Medical-Management-System/js/badge-manager.js',
    '/RMU-Medical-Management-System/js/notification-system.js',
    '/RMU-Medical-Management-System/images/logo.png'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                // Clone the request
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest).then(response => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone the response
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });

                    return response;
                });
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Push notification event
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'RMU Medical Sickbay';
    const options = {
        body: data.message || 'You have a new notification',
        icon: '/RMU-Medical-Management-System/images/icon-192x192.png',
        badge: '/RMU-Medical-Management-System/images/badge.png',
        vibrate: [200, 100, 200],
        data: data,
        actions: [
            {
                action: 'view',
                title: 'View'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/RMU-Medical-Management-System/')
        );
    }
});
