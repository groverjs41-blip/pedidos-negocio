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

        // If there was a pending sound blocked before user interaction, play it once now
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

    // Play Kitchen Bell (For NEW ORDER operational events)
    playKitchenChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.kitchenEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        const ctx = this.getAudioContext();
        if (ctx && ctx.state === 'suspended' && !this.unlocked) {
            this.showAudioBlockedPrompt('kitchen');
            return;
        }

        this.playWebAudioKitchenChime(vol);
    }

    // Play Delivery Chime (For READY operational events)
    playDeliveryChime() {
        if (this.muted) return;
        const settings = window.PedidosSoundSettings || {};
        if (settings.soundEnabled === false || settings.deliveryEnabled === false) return;

        const vol = this.getEffectiveVolume();
        if (vol <= 0) return;

        const ctx = this.getAudioContext();
        if (ctx && ctx.state === 'suspended' && !this.unlocked) {
            this.showAudioBlockedPrompt('delivery');
            return;
        }

        this.playWebAudioDeliveryChime(vol);
    }

    // Web Audio Synthesizer Engine
    playWebAudioKitchenChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) return;
        const play = () => {
            try {
                const now = ctx.currentTime;
                const g = ctx.createGain();
                g.gain.setValueAtTime(0, now);
                g.gain.linearRampToValueAtTime(0.75 * vol, now + 0.02);
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
            } catch (err) {
                // Fallback to HTML5 audio if Web Audio throws
                try {
                    this.kitchenAudio.volume = vol;
                    this.kitchenAudio.currentTime = 0;
                    this.kitchenAudio.play().catch(console.warn);
                } catch (e) {}
            }
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => this.showAudioBlockedPrompt('kitchen'));
        } else {
            play();
        }
    }

    playWebAudioDeliveryChime(vol = 1.0) {
        const ctx = this.getAudioContext();
        if (!ctx) return;
        const play = () => {
            try {
                const now = ctx.currentTime;
                const g = ctx.createGain();
                g.gain.setValueAtTime(0, now);
                g.gain.linearRampToValueAtTime(0.75 * vol, now + 0.02);
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
            } catch (err) {
                try {
                    this.deliveryAudio.volume = vol;
                    this.deliveryAudio.currentTime = 0;
                    this.deliveryAudio.play().catch(console.warn);
                } catch (e) {}
            }
        };

        if (ctx.state === 'suspended') {
            ctx.resume().then(play).catch(() => this.showAudioBlockedPrompt('delivery'));
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
     * Enforces strict processing order:
     * 1. Receive event
     * 2. Verify structure
     * 3. Check role authorization (if not target role -> return, do NOT mark processed!)
     * 4. Check deduplication key (orderId:action)
     * 5. Execute toast / sound / browser notification
     * 6. Mark event as processed
     */
    handleOperationalEvent(event, source = 'reverb') {
        if (!event || !event.orderId || !event.action) return;

        const { orderId, orderNumber, action, soundType, customerName, itemsSummary, targetRoles } = event;

        // 3. Verify User Roles Intersect Target Roles BEFORE checking or marking as processed!
        const userRoles = (window.PedidosUser && Array.isArray(window.PedidosUser.roles)) ? window.PedidosUser.roles : [];
        const isTargetUser = (targetRoles || []).some(role => userRoles.includes(role));

        if (!isTargetUser) {
            return; // User does not have target role -> exit without marking as processed!
        }

        // 4. Check deduplication key
        if (this.isEventProcessed(orderId, action)) {
            return;
        }

        // 5. Execute toast and sound
        const path = window.location.pathname;
        const cleanNumber = ltrim(String(orderNumber), '#');

        if (action === 'ORDER_CREATED') {
            this.playKitchenChime();
            window.showOperationalToast({
                id: `order-${orderId}-created`,
                title: 'NUEVO PEDIDO EN COCINA',
                message: `Pedido #${cleanNumber} • ${customerName || 'Venta Mostrador'}${itemsSummary ? ' (' + itemsSummary + ')' : ''}`,
                url: '/cocina',
                type: 'kitchen',
                actionBtn: { text: 'VER COCINA', url: '/cocina' }
            });
            this.showBrowserNotification(
                'NUEVO PEDIDO EN COCINA',
                `Pedido #${cleanNumber} (${customerName || 'Cliente'})`,
                '/cocina'
            );
        } else if (action === 'READY') {
            if (!path.startsWith('/cocina')) {
                this.playDeliveryChime();
                window.showOperationalToast({
                    id: `order-${orderId}-ready`,
                    title: 'PEDIDO LISTO PARA ENTREGAR',
                    message: `Pedido #${cleanNumber} • ${customerName || 'Cliente'} está preparado`,
                    url: '/reparto',
                    type: 'delivery',
                    actionBtn: { text: 'VER REPARTO', url: '/reparto' }
                });
                this.showBrowserNotification(
                    'PEDIDO LISTO PARA ENTREGAR',
                    `Pedido #${cleanNumber} (${customerName || 'Cliente'})`,
                    '/reparto'
                );
            }
        } else if (action === 'DELIVERING') {
            const readyToast = document.getElementById(`order-${orderId}-ready`);
            if (readyToast) readyToast.remove();
        }

        if (window.Livewire) {
            window.Livewire.dispatch('order-changed-realtime', { orderId, action });
        }

        // 6. Mark event as processed for this target user
        this.markEventProcessed(orderId, action);
    }
}

function ltrim(str, charlist) {
    if (!str) return '';
    charlist = !charlist ? ' \\s\u00A0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
    const re = new RegExp('^[' + charlist + ']+', 'g');
    return String(str).replace(re, '');
}

window.soundEngine = new SoundEngine();

// Toast configuration mapping with duration specs (kitchen: 6s, delivery: 8s)
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
            setTimeout(() => toast.remove(), 300);
        }
    }, toastDuration);
};

// Continuous Interaction Listener for Audio Context Unlock
const unlockHandler = () => {
    if (window.soundEngine) {
        window.soundEngine.unlockAudio();
    }
};
document.addEventListener('pointerdown', unlockHandler, { passive: true });
document.addEventListener('touchstart', unlockHandler, { passive: true });
document.addEventListener('keydown', unlockHandler, { passive: true });

// Listen for Livewire Toast Events & Polling Fallback Events
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

// Real-Time Echo Broadcast Listener & Reverb Connection Status Indicator
function initEchoListener() {
    if (window._echoSubscribed || !window.Echo) return;
    window._echoSubscribed = true;

    // Listen to Reverb connection states
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

    // Subscribe to operations channel
    window.Echo.private('orders.operations')
        .listen('OrderChanged', (event) => {
            if (window.soundEngine) {
                window.soundEngine.handleOperationalEvent(event, 'reverb');
            }
        });
}

document.addEventListener('DOMContentLoaded', initEchoListener);
document.addEventListener('livewire:navigated', initEchoListener);
