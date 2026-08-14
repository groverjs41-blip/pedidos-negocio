/**
 * Operational Notification & Dual-Engine Sound System for Pedidos Negocio
 * Uses Web Audio API with HTML5 Audio fallback for 100% reliable sound
 */

import { KITCHEN_BELL_WAV, DELIVERY_CHIME_WAV } from './audio_sounds.js';

class SoundEngine {
    constructor() {
        this.audioCtx = null;
        this.unlocked = false;
        this.pendingOperationalSound = null;

        this.kitchenAudio = new Audio(KITCHEN_BELL_WAV);
        this.deliveryAudio = new Audio(DELIVERY_CHIME_WAV);

        this.kitchenAudio.preload = 'auto';
        this.deliveryAudio.preload = 'auto';

        this.muted = localStorage.getItem('sound_muted') === 'true';
        this.processedEvents = new Set(
            JSON.parse(sessionStorage.getItem('processed_notif_events') || '[]')
        );

        document.addEventListener('DOMContentLoaded', () => this.updateSoundIconUI());
    }

    getAudioContext() {
        if (!this.audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                this.audioCtx = new AudioContextClass();
            }
        }
        return this.audioCtx;
    }

    getEffectiveVolume() {
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false) return 0;
        const vol = typeof settings.volume === 'number' ? settings.volume : 1.0;
        return Math.max(0, Math.min(1, vol));
    }

    unlockAudio() {
        try {
            this.kitchenAudio.load();
            this.deliveryAudio.load();
        } catch (e) {}

        const ctx = this.getAudioContext();
        if (ctx) {
            if (ctx.state === 'suspended') {
                ctx.resume().catch(console.error);
            }
            try {
                const buffer = ctx.createBuffer(1, 1, 22050);
                const source = ctx.createBufferSource();
                source.buffer = buffer;
                source.connect(ctx.destination);
                source.start(0);
                this.unlocked = true;
            } catch (e) {}
        }

        if (this.pendingOperationalSound) {
            const sound = this.pendingOperationalSound;
            this.pendingOperationalSound = null;
            const promptToast = document.getElementById('pending-sound-unlock-toast');
            if (promptToast) promptToast.remove();

            if (sound.type === 'kitchen') {
                this.playKitchenChime();
            } else if (sound.type === 'delivery') {
                this.playDeliveryChime();
            }
        }
    }

    toggleMute() {
        this.muted = !this.muted;
        localStorage.setItem('sound_muted', this.muted ? 'true' : 'false');
        this.updateSoundIconUI();
        window.showOperationalToast({
            type: 'info',
            title: 'Configuración de Sonido',
            message: this.muted ? 'Sonidos silenciados' : 'Sonidos activados'
        });
    }

    updateSoundIconUI() {
        const btn = document.getElementById('soundToggleBtn');
        if (!btn) return;
        if (this.muted) {
            btn.classList.add('sound-muted-active');
            btn.title = 'Sonido Silenciado (Clic para activar)';
            btn.style.opacity = '0.5';
        } else {
            btn.classList.remove('sound-muted-active');
            btn.title = 'Sonido Activo (Clic para silenciar)';
            btn.style.opacity = '1.0';
        }
    }

    isEventProcessed(orderId, action) {
        const key = `${orderId}:${action}`;
        return this.processedEvents.has(key);
    }

    markEventProcessed(orderId, action) {
        const key = `${orderId}:${action}`;
        this.processedEvents.add(key);
        const arr = Array.from(this.processedEvents).slice(-100);
        sessionStorage.setItem('processed_notif_events', JSON.stringify(arr));
    }

    showAudioBlockedPrompt(soundType) {
        this.pendingOperationalSound = { type: soundType, time: Date.now() };

        if (document.getElementById('pending-sound-unlock-toast')) return;

        window.showOperationalToast({
            id: 'pending-sound-unlock-toast',
            title: 'NUEVO PEDIDO RECIBIDO',
            message: 'Hay un pedido nuevo. Toca para activar los sonidos.',
            type: 'warning',
            actionBtn: {
                text: 'ACTIVAR SONIDO',
                url: '#',
                onClick: (e) => {
                    if (e) e.preventDefault();
                    this.unlockAudio();
                }
            }
        });
    }

    playKitchenChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.kitchenEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        this.unlockAudio();
        this.playWebAudioKitchenChime(vol);
    }

    playDeliveryChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.deliveryEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        this.unlockAudio();
        this.playWebAudioDeliveryChime(vol);
    }

    playHtml5KitchenAudio(vol = 1.0) {
        try {
            this.kitchenAudio.volume = vol;
            this.kitchenAudio.currentTime = 0;
            const p = this.kitchenAudio.play();
            if (p !== undefined) {
                p.catch(() => this.showAudioBlockedPrompt('kitchen'));
            }
        } catch (e) {
            this.showAudioBlockedPrompt('kitchen');
        }
    }

    playHtml5DeliveryAudio(vol = 1.0) {
        try {
            this.deliveryAudio.volume = vol;
            this.deliveryAudio.currentTime = 0;
            const p = this.deliveryAudio.play();
            if (p !== undefined) {
                p.catch(() => this.showAudioBlockedPrompt('delivery'));
            }
        } catch (e) {
            this.showAudioBlockedPrompt('delivery');
        }
    }

    playWebAudioKitchenChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) {
            this.playHtml5KitchenAudio(vol);
            return;
        }

        const play = () => {
            try {
                const now = ctx.currentTime;
                const g = ctx.createGain();
                g.gain.setValueAtTime(0, now);
                g.gain.linearRampToValueAtTime(0.65 * vol, now + 0.015);
                g.gain.exponentialRampToValueAtTime(0.0001, now + 0.72);
                g.connect(ctx.destination);

                const osc1 = ctx.createOscillator();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(880, now);
                osc1.connect(g);
                osc1.start(now);
                osc1.stop(now + 0.75);

                const osc2 = ctx.createOscillator();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(1318.5, now);
                osc2.connect(g);
                osc2.start(now);
                osc2.stop(now + 0.45);
            } catch (err) {
                this.playHtml5KitchenAudio(vol);
            }
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => this.playHtml5KitchenAudio(vol));
        } else {
            play();
        }
    }

    playWebAudioDeliveryChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) {
            this.playHtml5DeliveryAudio(vol);
            return;
        }

        const play = () => {
            try {
                const now = ctx.currentTime;

                const g1 = ctx.createGain();
                g1.gain.setValueAtTime(0, now);
                g1.gain.linearRampToValueAtTime(0.45 * vol, now + 0.015);
                g1.gain.exponentialRampToValueAtTime(0.0001, now + 0.45);
                g1.connect(ctx.destination);

                const osc1 = ctx.createOscillator();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, now);
                osc1.connect(g1);
                osc1.start(now);
                osc1.stop(now + 0.48);

                const g2 = ctx.createGain();
                g2.gain.setValueAtTime(0, now + 0.18);
                g2.gain.linearRampToValueAtTime(0.55 * vol, now + 0.20);
                g2.gain.exponentialRampToValueAtTime(0.0001, now + 0.76);
                g2.connect(ctx.destination);

                const osc2 = ctx.createOscillator();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(659.25, now + 0.18);
                osc2.connect(g2);
                osc2.start(now + 0.18);
                osc2.stop(now + 0.78);
            } catch (err) {
                this.playHtml5DeliveryAudio(vol);
            }
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => this.playHtml5DeliveryAudio(vol));
        } else {
            play();
        }
    }

    showBrowserNotification(title, body, url = '/') {
        if (('Notification' in window) && Notification.permission === 'granted') {
            try {
                const notif = new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                    tag: title
                });

                notif.onclick = () => {
                    window.focus();
                    if (url) window.location.href = url;
                };
            } catch (e) {}
        }
    }

    /**
     * UNIFIED OPERATIONAL EVENT HANDLER (Reverb & Polling Fallback)
     * Enforces per-user notification preferences as sole authority:
     * 1. Normalize IDs as Numbers.
     * 2. Check if current user ID is in targetUserIds.
     * 3. Check deduplication key (orderId:action).
     * 4. Check if current user ID is in soundUserIds.
     * 5. Check if current user ID is in browserUserIds.
     * 6. Render floating toast (always if in targets), play sound if in sounds, send browser if in browsers.
     * 7. Mark event as processed for target user.
     */
    handleOperationalEvent(event, source = 'reverb') {
        if (!event || !event.orderId || !event.action) return;

        const {
            orderId,
            orderNumber,
            action,
            customerName,
            itemsSummary,
            targetRoles,
            targetUserIds,
            soundUserIds,
            browserUserIds
        } = event;

        const currentUserId = window.PedidosUser ? Number(window.PedidosUser.id) : null;
        const userRoles = (window.PedidosUser && Array.isArray(window.PedidosUser.roles)) ? window.PedidosUser.roles : [];

        if (!currentUserId) return;

        // Section 6: Normalize IDs as numbers
        const targets = Array.isArray(targetUserIds)
            ? targetUserIds.map(Number)
            : (Array.isArray(targetRoles) && targetRoles.some(role => userRoles.includes(role)) ? [currentUserId] : []);

        const sounds = Array.isArray(soundUserIds)
            ? soundUserIds.map(Number)
            : [];

        const browsers = Array.isArray(browserUserIds)
            ? browserUserIds.map(Number)
            : [];

        // Section 7: If current user is not in targets, exit without processing
        if (!targets.includes(currentUserId)) {
            return;
        }

        // Section 8: Deduplication check
        if (this.isEventProcessed(orderId, action)) {
            return;
        }

        const cleanNumber = ltrim(String(orderNumber), '#');

        // Section 5 & 13: Toast ALWAYS displays if user is in targets. Sound & Browser evaluate independently.
        const canPlaySound = sounds.includes(currentUserId);
        const canBrowser = browsers.includes(currentUserId);

        // ORDER_CREATED: Kitchen sound & toast
        if (action === 'ORDER_CREATED') {
            if (canPlaySound) {
                this.playKitchenChime();
            }

            window.showOperationalToast({
                id: `order-${orderId}-created`,
                title: 'NUEVO PEDIDO',
                orderNumber: `#${cleanNumber}`,
                customerName: customerName || 'Venta Mostrador',
                message: itemsSummary ? `${itemsSummary}` : 'Comanda enviada a cocina',
                url: '/cocina',
                type: 'kitchen',
                actionBtn: { text: 'VER', url: '/cocina' }
            });

            if (canBrowser) {
                this.showBrowserNotification(
                    'NUEVO PEDIDO',
                    `Pedido #${cleanNumber} (${customerName || 'Venta Mostrador'})`,
                    '/cocina'
                );
            }
        }
        
        // READY: Delivery sound & toast (NO screen path restriction, NO origin user restriction)
        else if (action === 'READY') {
            if (canPlaySound) {
                this.playDeliveryChime();
            }

            let readyUrl = '/reparto';
            if (userRoles.includes('pedidos') || userRoles.includes('caja')) {
                if (!userRoles.includes('admin') && !userRoles.includes('reparto')) {
                    readyUrl = '/pedidos';
                }
            }

            window.showOperationalToast({
                id: `order-${orderId}-ready`,
                title: 'PEDIDO LISTO',
                orderNumber: `#${cleanNumber}`,
                customerName: customerName || 'Cliente',
                message: 'Listo para recoger y entregar',
                url: readyUrl,
                type: 'delivery',
                actionBtn: { text: 'VER PEDIDO', url: readyUrl },
                glow: true
            });

            if (canBrowser) {
                this.showBrowserNotification(
                    'PEDIDO LISTO',
                    `Pedido #${cleanNumber} (${customerName || 'Cliente'})`,
                    readyUrl
                );
            }
        }

        // DELIVERED: Toast & optional sound
        else if (action === 'DELIVERED') {
            if (canPlaySound) {
                this.playDeliveryChime();
            }

            window.showOperationalToast({
                id: `order-${orderId}-delivered`,
                title: 'PEDIDO ENTREGADO',
                orderNumber: `#${cleanNumber}`,
                customerName: customerName || 'Cliente',
                message: 'Pedido entregado correctamente',
                url: '/caja',
                type: 'success',
                actionBtn: { text: 'VER EN CAJA', url: '/caja' }
            });

            if (canBrowser) {
                this.showBrowserNotification(
                    'PEDIDO ENTREGADO',
                    `Pedido #${cleanNumber} entregado`,
                    '/caja'
                );
            }
        }

        // CANCELLED: Toast
        else if (action === 'CANCELLED') {
            if (canPlaySound) {
                this.playDeliveryChime();
            }

            window.showOperationalToast({
                id: `order-${orderId}-cancelled`,
                title: 'PEDIDO CANCELADO',
                orderNumber: `#${cleanNumber}`,
                customerName: customerName || 'Cliente',
                message: 'El pedido ha sido cancelado',
                url: '/pedidos',
                type: 'error',
                actionBtn: { text: 'VER PEDIDOS', url: '/pedidos' }
            });

            if (canBrowser) {
                this.showBrowserNotification(
                    'PEDIDO CANCELADO',
                    `Pedido #${cleanNumber} cancelado`,
                    '/pedidos'
                );
            }
        }

        // DELIVERING: Driver claimed order -> auto-remove READY toast if active
        else if (action === 'DELIVERING') {
            const readyToast = document.getElementById(`order-${orderId}-ready`);
            if (readyToast) readyToast.remove();
        }

        if (window.Livewire) {
            window.Livewire.dispatch('order-changed-realtime', { orderId, action });
        }

        this.markEventProcessed(orderId, action);
    }
}

function ltrim(str, charlist) {
    if (!str) return '';
    charlist = !charlist ? ' \\s\u00A0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
    const re = new RegExp('^[' + charlist + ']+', 'g');
    return String(str).replace(re, '');
}

function createToastSvgIcon(type) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', '18');
    svg.setAttribute('height', '18');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.style.flexShrink = '0';

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    if (type === 'kitchen') {
        path.setAttribute('d', 'M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 10.58 0A4 4 0 0 1 18 13.87V21H6z M6 17h12');
    } else if (type === 'delivery') {
        path.setAttribute('d', 'M1 3h15v13H1z M16 8h4l3 3v5h-7V8z M5.5 18.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z M18.5 18.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z');
    } else if (type === 'success') {
        path.setAttribute('d', 'M22 11.08V12a10 10 0 1 1-5.93-9.14 M22 4L12 14.01l-3-3');
    } else if (type === 'error') {
        path.setAttribute('d', 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z M12 9v4 M12 17h.01');
    } else if (type === 'warning') {
        path.setAttribute('d', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z');
    } else {
        path.setAttribute('d', 'M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-1-11v6h2v-6h-2zm0-4v2h2V7h-2z');
    }

    svg.appendChild(path);
    return svg;
}

window.soundEngine = new SoundEngine();

// Section 14: Test operational notification helper
window.testOperationalNotification = function(action = 'READY') {
    const currentUserId = window.PedidosUser ? Number(window.PedidosUser.id) : 1;
    const testEvent = {
        orderId: 'test-' + Date.now(),
        orderNumber: '999',
        action: action,
        soundType: action === 'ORDER_CREATED' ? 'kitchen' : 'delivery',
        customerName: 'Cliente Prueba',
        itemsSummary: '1x Producto Prueba',
        targetUserIds: [currentUserId],
        soundUserIds: [currentUserId],
        browserUserIds: [],
        originUserId: null
    };
    if (window.soundEngine) {
        window.soundEngine.handleOperationalEvent(testEvent, 'test');
    }
};

const TOAST_CONFIG = {
    success: { duration: 3500, title: 'Éxito' },
    info: { duration: 4500, title: 'Información' },
    warning: { duration: 6000, title: 'Advertencia' },
    error: { duration: 8000, title: 'Error' },
    kitchen: { duration: 6000, title: 'NUEVO PEDIDO' },
    delivery: { duration: 8000, title: 'PEDIDO LISTO' }
};

window.showOperationalToast = function ({ id, title, orderNumber, customerName, message, url, type = 'info', actionBtn = null, glow = false }) {
    const container = document.getElementById('toastNotificationContainer');
    if (!container) return;

    const config = TOAST_CONFIG[type] || TOAST_CONFIG.info;
    const toastDuration = config.duration;
    const toastTitle = title || config.title;

    const currentToasts = container.querySelectorAll('.toast-card');
    if (currentToasts.length >= 3) {
        currentToasts[0].remove();
    }

    const toastId = id || 'toast-' + Math.random().toString(36).substring(2, 9);
    const existing = document.getElementById(toastId);
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast-card toast-${type}${glow ? ' toast-ready-glow' : ''}`;

    const toastContent = document.createElement('div');
    toastContent.className = 'toast-content';

    const header = document.createElement('div');
    header.className = 'toast-header';

    const titleWrap = document.createElement('div');
    titleWrap.style.display = 'flex';
    titleWrap.style.alignItems = 'center';
    titleWrap.style.gap = '0.5rem';

    const svgIcon = createToastSvgIcon(type);
    const titleSpan = document.createElement('span');
    titleSpan.className = 'toast-title';
    titleSpan.textContent = toastTitle;

    titleWrap.appendChild(svgIcon);
    titleWrap.appendChild(titleSpan);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'toast-close-btn';
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = () => toast.remove();

    header.appendChild(titleWrap);
    header.appendChild(closeBtn);

    toastContent.appendChild(header);

    if (orderNumber) {
        const numDiv = document.createElement('div');
        numDiv.style.fontSize = '0.85rem';
        numDiv.style.fontWeight = '800';
        numDiv.style.color = 'var(--text-main)';
        numDiv.style.marginTop = '0.15rem';
        numDiv.textContent = orderNumber;
        toastContent.appendChild(numDiv);
    }

    if (customerName) {
        const custDiv = document.createElement('div');
        custDiv.style.fontSize = '0.775rem';
        custDiv.style.fontWeight = '600';
        custDiv.style.color = 'var(--text-muted)';
        custDiv.textContent = customerName;
        toastContent.appendChild(custDiv);
    }

    const msgPara = document.createElement('p');
    msgPara.className = 'toast-message';
    msgPara.textContent = message || '';
    toastContent.appendChild(msgPara);

    const btnTarget = actionBtn || (url ? { text: 'VER PEDIDO', url: url } : null);
    if (btnTarget) {
        if (btnTarget.onClick) {
            const actBtn = document.createElement('button');
            actBtn.type = 'button';
            actBtn.className = 'toast-action-btn';
            actBtn.textContent = btnTarget.text;
            actBtn.onclick = (e) => {
                btnTarget.onClick(e);
                toast.remove();
            };
            toastContent.appendChild(actBtn);
        } else {
            const linkBtn = document.createElement('a');
            linkBtn.href = btnTarget.url;
            linkBtn.className = 'toast-action-btn';
            linkBtn.textContent = btnTarget.text;
            toastContent.appendChild(linkBtn);
        }
    }

    const progressTrack = document.createElement('div');
    progressTrack.className = 'toast-progress-track';

    const progressBar = document.createElement('div');
    progressBar.className = 'toast-progress-bar';
    progressBar.style.animationDuration = `${toastDuration}ms`;
    progressTrack.appendChild(progressBar);

    toast.appendChild(toastContent);
    toast.appendChild(progressTrack);

    container.appendChild(toast);

    setTimeout(() => {
        if (document.getElementById(toastId)) {
            toast.classList.add('toast-fade-out');
            setTimeout(() => toast.remove(), 200);
        }
    }, toastDuration);
};

const unlockHandler = () => {
    if (window.soundEngine) {
        window.soundEngine.unlockAudio();
    }
};
document.addEventListener('pointerdown', unlockHandler, { passive: true });
document.addEventListener('touchstart', unlockHandler, { passive: true });
document.addEventListener('keydown', unlockHandler, { passive: true });

document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('notify-toast', (data) => {
            const toastData = Array.isArray(data) ? data[0] : data;
            window.showOperationalToast(toastData);
        });

        window.Livewire.on('operational-fallback-event', (data) => {
            const eventData = Array.isArray(data) ? data[0] : data;
            if (window.soundEngine) {
                window.soundEngine.handleOperationalEvent(eventData, 'poll');
            }
        });
    }
});

function initEchoListener() {
    if (window._echoSubscribed || !window.Echo) return;
    window._echoSubscribed = true;

    if (window.Echo.connector && window.Echo.connector.pusher) {
        const pusher = window.Echo.connector.pusher;
        pusher.connection.bind('state_change', (states) => {
            const dot = document.querySelector('#connectionStatusIndicator .dot');
            const text = document.querySelector('#connectionStatusIndicator .status-text');
            if (!dot) return;
            if (states.current === 'connected') {
                dot.className = 'dot online';
                if (text) text.textContent = 'En línea';
                dot.title = 'Realtime conectado';
            } else if (states.current === 'connecting') {
                dot.className = 'dot connecting';
                if (text) text.textContent = 'Reconectando';
                dot.title = 'Realtime reconectando';
            } else {
                dot.className = 'dot offline';
                if (text) text.textContent = 'Desconectado';
                dot.title = 'Realtime desconectado';
            }
        });
    }

    window.Echo.private('orders.operations')
        .listen('OrderChanged', (event) => {
            if (window.soundEngine) {
                window.soundEngine.handleOperationalEvent(event, 'reverb');
            }
        });
}

document.addEventListener('DOMContentLoaded', initEchoListener);
document.addEventListener('livewire:navigated', initEchoListener);
