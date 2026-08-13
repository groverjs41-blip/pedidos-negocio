/**
 * Operational Notification & Dual-Engine Sound System for Pedidos Negocio
 * Uses Base64 PCM WAV HTML5 Audio with Web Audio API fallback for 100% reliable sound
 */

import { KITCHEN_BELL_WAV, DELIVERY_CHIME_WAV } from './audio_sounds.js';

class SoundEngine {
    constructor() {
        this.audioCtx = null;
        this.kitchenAudio = new Audio(KITCHEN_BELL_WAV);
        this.deliveryAudio = new Audio(DELIVERY_CHIME_WAV);

        this.kitchenAudio.preload = 'auto';
        this.deliveryAudio.preload = 'auto';

        this.muted = localStorage.getItem('sound_muted') === 'true';
        this.processedEvents = new Set(
            JSON.parse(sessionStorage.getItem('processed_notif_events') || '[]')
        );
    }

    getAudioContext() {
        if (!this.audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioCtx = new AudioContext();
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
        if (ctx && ctx.state === 'suspended') {
            ctx.resume().catch(console.error);
        }
    }

    toggleMute() {
        this.muted = !this.muted;
        localStorage.setItem('sound_muted', this.muted ? 'true' : 'false');
        window.showOperationalToast({
            type: 'info',
            title: 'Configuración de Sonido',
            message: this.muted ? 'Sonidos silenciados' : 'Sonidos activados'
        });
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

    // Play Kitchen Bell (Only for NEW ORDER operational events)
    playKitchenChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.kitchenEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        this.unlockAudio();

        try {
            this.kitchenAudio.volume = vol;
            this.kitchenAudio.currentTime = 0;
            const p = this.kitchenAudio.play();
            if (p !== undefined) {
                p.catch(err => {
                    console.warn('HTML5 audio play blocked, calling Web Audio fallback', err);
                    this.playWebAudioKitchenChime(vol);
                });
            }
        } catch (e) {
            this.playWebAudioKitchenChime(vol);
        }
    }

    // Play Delivery Chime (Only for READY operational events)
    playDeliveryChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.deliveryEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        this.unlockAudio();

        try {
            this.deliveryAudio.volume = vol;
            this.deliveryAudio.currentTime = 0;
            const p = this.deliveryAudio.play();
            if (p !== undefined) {
                p.catch(err => {
                    console.warn('HTML5 audio play blocked, calling Web Audio fallback', err);
                    this.playWebAudioDeliveryChime(vol);
                });
            }
        } catch (e) {
            this.playWebAudioDeliveryChime(vol);
        }
    }

    // Web Audio Synthesizer Fallbacks with Gain Volume
    playWebAudioKitchenChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) return;
        const play = () => {
            const now = ctx.currentTime;
            const g = ctx.createGain();
            g.gain.setValueAtTime(0, now);
            g.gain.linearRampToValueAtTime(0.7 * vol, now + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, now + 0.75);
            g.connect(ctx.destination);

            const osc1 = ctx.createOscillator();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, now);
            osc1.connect(g);
            osc1.start(now);
            osc1.stop(now + 0.78);

            const osc2 = ctx.createOscillator();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1108.73, now);
            osc2.connect(g);
            osc2.start(now);
            osc2.stop(now + 0.72);
        };
        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(console.error);
        } else {
            play();
        }
    }

    playWebAudioDeliveryChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) return;
        const play = () => {
            const now = ctx.currentTime;
            const g = ctx.createGain();
            g.gain.setValueAtTime(0, now);
            g.gain.linearRampToValueAtTime(0.7 * vol, now + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, now + 0.85);
            g.connect(ctx.destination);

            const osc1 = ctx.createOscillator();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(698.46, now);
            osc1.connect(g);
            osc1.start(now);
            osc1.stop(now + 0.45);

            const osc2 = ctx.createOscillator();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(1046.50, now + 0.16);
            osc2.connect(g);
            osc2.start(now + 0.16);
            osc2.stop(now + 0.88);
        };
        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(console.error);
        } else {
            play();
        }
    }

    showBrowserNotification(title, body, url = '/') {
        if (('Notification' in window) && Notification.permission === 'granted') {
            const notif = new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                tag: title
            });

            notif.onclick = () => {
                window.focus();
                if (url) window.location.href = url;
            };
        }
    }
}

window.soundEngine = new SoundEngine();

// Toast configuration mapping
const TOAST_CONFIG = {
    success: { duration: 3500, icon: '✓', title: 'Éxito' },
    info: { duration: 4500, icon: 'ℹ', title: 'Información' },
    warning: { duration: 6000, icon: '⚠️', title: 'Advertencia' },
    error: { duration: 8000, icon: '✕', title: 'Error' },
    kitchen: { duration: 6000, icon: '👨‍🍳', title: 'Cocina' },
    delivery: { duration: 8000, icon: '🛵', title: 'Reparto' }
};

// Global Floating Toast Dispatcher
window.showOperationalToast = function ({ id, title, message, url, type = 'info', actionBtn = null }) {
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
    toast.className = `toast-card toast-${type}`;

    const toastContent = document.createElement('div');
    toastContent.className = 'toast-content';

    const header = document.createElement('div');
    header.className = 'toast-header';

    const titleWrap = document.createElement('div');
    titleWrap.style.display = 'flex';
    titleWrap.style.alignItems = 'center';
    titleWrap.style.gap = '0.5rem';

    const iconSpan = document.createElement('span');
    iconSpan.textContent = config.icon;
    iconSpan.style.fontSize = '1.1rem';

    const titleSpan = document.createElement('span');
    titleSpan.className = 'toast-title';
    titleSpan.textContent = toastTitle;

    titleWrap.appendChild(iconSpan);
    titleWrap.appendChild(titleSpan);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'toast-close-btn';
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = () => toast.remove();

    header.appendChild(titleWrap);
    header.appendChild(closeBtn);

    const msgPara = document.createElement('p');
    msgPara.className = 'toast-message';
    msgPara.textContent = message || '';

    toastContent.appendChild(header);
    toastContent.appendChild(msgPara);

    const btnTarget = actionBtn || (url ? { text: 'VER', url: url } : null);
    if (btnTarget) {
        const linkBtn = document.createElement('a');
        linkBtn.href = btnTarget.url;
        linkBtn.className = 'toast-action-btn';
        linkBtn.textContent = btnTarget.text;
        toastContent.appendChild(linkBtn);
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
            setTimeout(() => toast.remove(), 300);
        }
    }, toastDuration);
};

// Continuous Interaction Listener
const unlockHandler = () => {
    if (window.soundEngine) {
        window.soundEngine.unlockAudio();
    }
};
document.addEventListener('pointerdown', unlockHandler);
document.addEventListener('click', unlockHandler);
document.addEventListener('keydown', unlockHandler);

// Listen for Livewire Toast Events (CRUD actions ONLY show visual toast, NO sound)
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('notify-toast', (data) => {
            const toastData = Array.isArray(data) ? data[0] : data;
            window.showOperationalToast(toastData);
        });
    }
});

// Real-Time Echo Broadcast Listener (Single Subscription Guard)
function initEchoListener() {
    if (window._echoSubscribed || !window.Echo) return;
    window._echoSubscribed = true;

    window.Echo.private('orders.operations')
        .listen('OrderChanged', (event) => {
            const { orderId, orderNumber, status, action, soundType, customerName, itemsSummary, targetRoles } = event;

            if (window.soundEngine.isEventProcessed(orderId, action)) {
                return;
            }
            window.soundEngine.markEventProcessed(orderId, action);

            // Verify User Roles Intersect Target Roles BEFORE playing sound or showing notification!
            const userRoles = (window.PedidosUser && Array.isArray(window.PedidosUser.roles)) ? window.PedidosUser.roles : [];
            const isTargetUser = (targetRoles || []).some(role => userRoles.includes(role));

            if (!isTargetUser) {
                return;
            }

            const path = window.location.pathname;

            // RULE 1: NUEVO PEDIDO -> Play Kitchen Bell & Show Toast for target roles (admin, cocina)
            if (action === 'ORDER_CREATED') {
                window.soundEngine.playKitchenChime();
                window.showOperationalToast({
                    id: `order-${orderId}-created`,
                    title: 'NUEVO PEDIDO EN COCINA',
                    message: `Pedido #${orderNumber} • ${customerName || 'Venta Mostrador'} (${itemsSummary || ''})`,
                    url: '/cocina',
                    type: 'kitchen',
                    actionBtn: { text: 'VER COCINA', url: '/cocina' }
                });
                window.soundEngine.showBrowserNotification(
                    'NUEVO PEDIDO EN COCINA',
                    `Pedido #${orderNumber} (${customerName || 'Cliente'})`,
                    '/cocina'
                );
            }

            // RULE 2: PEDIDO PREPARADO (READY) -> Play Delivery Chime & Show Toast for target roles (admin, reparto)
            else if (action === 'READY') {
                // Do NOT play ready sound/toast on kitchen screen itself to avoid redundant self-noise
                if (!path.startsWith('/cocina')) {
                    window.soundEngine.playDeliveryChime();
                    window.showOperationalToast({
                        id: `order-${orderId}-ready`,
                        title: 'PEDIDO LISTO PARA ENTREGAR',
                        message: `Pedido #${orderNumber} • ${customerName || 'Cliente'} está preparado`,
                        url: '/reparto',
                        type: 'delivery',
                        actionBtn: { text: 'VER REPARTO', url: '/reparto' }
                    });
                    window.showBrowserNotification(
                        'PEDIDO LISTO PARA ENTREGAR',
                        `Pedido #${orderNumber} (${customerName || 'Cliente'})`,
                        '/reparto'
                    );
                }
            }

            else if (action === 'DELIVERING') {
                const readyToast = document.getElementById(`order-${orderId}-ready`);
                if (readyToast) readyToast.remove();
            }

            if (window.Livewire) {
                window.Livewire.dispatch('order-changed-realtime', { orderId, action, status });
            }
        });
}

document.addEventListener('DOMContentLoaded', initEchoListener);
document.addEventListener('livewire:navigated', initEchoListener);
