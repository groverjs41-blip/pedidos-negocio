const CACHE_NAME = 'pedidos-negocio-cache-v1';
const ASSETS = [
    '/',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

// Install Event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate Event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch Event
self.addEventListener('fetch', event => {
    // Only handle GET requests from the same origin
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Bypass caching for admin panel or Livewire updates to avoid conflicts
    const url = new URL(event.request.url);
    if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/livewire')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(cachedResponse => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(event.request)
                .then(response => {
                    // Check if we received a valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }

                    // Clone the response because it can only be consumed once
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });

                    return response;
                })
                .catch(() => {
                    // If network fails, try to return offline fallback for HTML requests
                    if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
                        return caches.match('/');
                    }
                });
        })
    );
});
