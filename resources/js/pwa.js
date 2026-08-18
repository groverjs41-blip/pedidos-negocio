/**
 * Centralized PWA & Service Worker Manager
 */

(function () {
    window.deferredPwaPrompt = null;

    /**
     * Determine current PWA State:
     * 'INSTALLED' | 'READY' | 'WAITING' | 'IOS' | 'UNSUPPORTED'
     */
    window.detectPwaState = function () {
        const isStandalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
        if (isStandalone) {
            return 'INSTALLED';
        }

        const isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
        if (isIos) {
            return 'IOS';
        }

        if (window.deferredPwaPrompt) {
            return 'READY';
        }

        const isSupported = ('serviceWorker' in navigator);
        if (!isSupported) {
            return 'UNSUPPORTED';
        }

        return 'WAITING';
    };

    window.updatePwaStatus = function () {
        const state = window.detectPwaState();
        window.pwaState = state;
        window.dispatchEvent(new CustomEvent('pwa-state-changed', { detail: { state } }));
    };

    // Capture beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.deferredPwaPrompt = e;
        window.updatePwaStatus();
    });

    // Capture appinstalled event
    window.addEventListener('appinstalled', () => {
        window.deferredPwaPrompt = null;
        window.updatePwaStatus();
    });

    // Handle online/offline network indicator
    function updateOnlineStatus() {
        const banner = document.getElementById('pwaOfflineBanner');
        if (!navigator.onLine) {
            if (banner) banner.style.display = 'block';
        } else {
            if (banner && banner.style.display === 'block') {
                banner.style.display = 'none';
                if (window.showOperationalToast) {
                    window.showOperationalToast({ type: 'success', title: 'Conexión', message: '✓ CONEXIÓN RESTABLECIDA' });
                }
            }
        }
        window.updatePwaStatus();
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    document.addEventListener('DOMContentLoaded', () => {
        updateOnlineStatus();
        window.updatePwaStatus();

        // Single Centralized Service Worker Registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(reg => {
                reg.onupdatefound = () => {
                    const installingWorker = reg.installing;
                    if (installingWorker) {
                        installingWorker.onstatechange = () => {
                            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New worker taking control cleanly
                            }
                        };
                    }
                };
            }).catch(err => console.error('SW Registration Error:', err));
        }
    });

    // Listen to display-mode changes dynamically
    if (window.matchMedia) {
        try {
            window.matchMedia('(display-mode: standalone)').addEventListener('change', () => {
                window.updatePwaStatus();
            });
        } catch (e) {}
    }
})();
