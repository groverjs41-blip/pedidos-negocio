import 'overlayscrollbars/styles/overlayscrollbars.css';
import { OverlayScrollbars } from 'overlayscrollbars';

const initializedElements = new WeakSet();

const compactSelectors = [
    '.cart-items-list',
    '.notification-dropdown-panel',
    '.operational-dropdown-panel',
    '.modal-content',
    '.table-responsive',
];

const mainSelectors = [
    '.sidebar-nav',
    ...compactSelectors,
];

/**
 * Initialize OverlayScrollbars on a single element if not already initialized.
 */
export function initScrollbar(el, isCompact = false) {
    if (!el || !(el instanceof HTMLElement) || initializedElements.has(el)) {
        return;
    }

    const tag = el.tagName.toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
        return;
    }

    initializedElements.add(el);

    const themeName = isCompact ? 'os-theme-pedidos os-scrollbar-compact' : 'os-theme-pedidos';

    try {
        OverlayScrollbars(el, {
            scrollbars: {
                theme: themeName,
                autoHide: 'leave',
                autoHideDelay: 400,
                clickScroll: true,
            },
        });
    } catch (e) {
        console.warn('OverlayScrollbars element init warning:', e);
    }
}

/**
 * Initialize OverlayScrollbars on document.body.
 */
export function initBodyScrollbar() {
    if (document.body && !initializedElements.has(document.body)) {
        initializedElements.add(document.body);
        try {
            OverlayScrollbars(document.body, {
                scrollbars: {
                    theme: 'os-theme-pedidos',
                    autoHide: 'leave',
                    autoHideDelay: 500,
                    clickScroll: true,
                },
            });
        } catch (e) {
            console.warn('OverlayScrollbars body init warning:', e);
        }
    }
}

/**
 * Scan DOM and initialize OverlayScrollbars on all target containers.
 */
export function initAllScrollbars() {
    initBodyScrollbar();

    mainSelectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            const isCompact = compactSelectors.includes(selector);
            initScrollbar(el, isCompact);
        });
    });
}

// Auto-run on DOMReady & Livewire SPA Navigation
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllScrollbars);
} else {
    initAllScrollbars();
}

document.addEventListener('livewire:navigated', initAllScrollbars);

// Controlled MutationObserver for dynamic nodes (modals, dropdowns, cart items)
const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.ELEMENT_NODE) {
                const el = /** @type {HTMLElement} */ (node);

                mainSelectors.forEach(selector => {
                    if (el.matches && el.matches(selector)) {
                        initScrollbar(el, compactSelectors.includes(selector));
                    }
                    if (el.querySelectorAll) {
                        el.querySelectorAll(selector).forEach(child => {
                            initScrollbar(child, compactSelectors.includes(selector));
                        });
                    }
                });
            }
        }
    }
});

if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
} else {
    document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { childList: true, subtree: true });
    });
}
