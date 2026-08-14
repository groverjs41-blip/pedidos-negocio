const CACHE_NAME = 'pedidos-negocio-cache-v4';
const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
];

// Exclude dynamic patterns from any caching
const DYNAMIC_PATTERNS = [
    /^\/admin/i,
    /^\/livewire/i,
    /^\/api/i,
    /^\/login/i,
    /^\/logout/i,
    /^\/register/i,
    /^\/pedidos/i,
    /^\/cocina/i,
    /^\/repartos/i,
    /^\/cobranza/i,
    /^\/auth/i,
    /^\/user/i,
];

// Install event: cache static assets and offline fallback
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

// Activate event: clean old caches
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

// Fetch event
self.addEventListener('fetch', event => {
    // Only handle GET requests from the same origin
    if (event.request.method !== 'GET' || !event.request.url.startsWith(self.location.origin)) {
        return;
    }

    const url = new URL(event.request.url);

    // 1. Strict Exclusion check: skip caching for all dynamic modules and system routes
    const isDynamic = DYNAMIC_PATTERNS.some(regex => regex.test(url.pathname));
    if (isDynamic) {
        return; // Let the browser handle it purely via network
    }

    // 2. Navigation requests: Network-Only with Offline Fallback
    // We never cache operational HTML pages to prevent showing stale data.
    if (event.request.mode === 'navigate' || (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            fetch(event.request).catch(() => {
                // If offline, serve the offline fallback page
                return caches.match('/offline.html');
            })
        );
        return;
    }

    // 3. Static Assets: Cache-First strategy
    const isStaticAsset = url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|woff2|woff|ttf|ico|json)$/i) || url.pathname.startsWith('/icons/');
    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(response => {
                    if (response && response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                });
            })
        );
    }
});
