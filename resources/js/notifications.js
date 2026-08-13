/**
 * Operational Notification & Sound Engine for Pedidos Negocio
 * Synthesizes audio using Web Audio API (No external CDN dependencies)
 */

class SoundEngine {
    constructor() {
        this.audioCtx = null;
        this.isUnlocked = false;
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

    unlockAudio() {
        const ctx = this.getAudioContext();
        if (!ctx) return;

        if (ctx.state === 'suspended') {
            ctx.resume().then(() => {
                this.isUnlocked = true;
                this.updateUnlockUI(true);
            }).catch(console.error);
        } else {
            this.isUnlocked = true;
            this.updateUnlockUI(true);
        }
    }

    updateUnlockUI(unlocked) {
        const banner = document.getElementById('audioUnlockBanner');
        if (banner) {
            banner.style.display = unlocked ? 'none' : 'flex';
        }
    }

    toggleMute() {
        this.muted = !this.muted;
        localStorage.setItem('sound_muted', this.muted ? 'true' : 'false');

        const btn = document.getElementById('soundToggleBtn');
        if (btn) {
            btn.setAttribute('data-muted', this.muted ? 'true' : 'false');
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

    // Synthesize Kitchen Chime (Short double tone 880Hz -> 1046.5Hz)
    playKitchenChime(volume = 0.8) {
        if (this.muted) return;
        const ctx = this.getAudioContext();
        if (!ctx || ctx.state !== 'running') return;

        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, now); // A5
        osc.frequency.setValueAtTime(1046.5, now + 0.15); // C6

        gain.gain.setValueAtTime(0, now);
        gain.gain.linearRampToValueAtTime(volume, now + 0.05);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start(now);
        osc.stop(now + 0.85);
    }

    // Synthesize Delivery Chime (Double Ding: 523.25Hz -> 659.25Hz)
    playDeliveryChime(volume = 0.8) {
        if (this.muted) return;
        const ctx = this.getAudioContext();
        if (!ctx || ctx.state !== 'running') return;

        const now = ctx.currentTime;

        // First Ding
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'triangle';
        osc1.frequency.setValueAtTime(523.25, now); // C5
        gain1.gain.setValueAtTime(0, now);
        gain1.gain.linearRampToValueAtTime(volume, now + 0.03);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.45);

        // Second Ding (after 0.25s)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(659.25, now + 0.25); // E5
        gain2.gain.setValueAtTime(0, now + 0.25);
        gain2.gain.linearRampToValueAtTime(volume, now + 0.28);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.25);
        osc2.stop(now + 0.85);
    }

    requestBrowserNotificationPermission() {
        if (!('Notification' in window)) {
            alert('Su navegador no soporta notificaciones de escritorio.');
            return;
        }

        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                new Notification('Pedidos Negocio', {
                    body: '¡Notificaciones del navegador activadas con éxito!',
                    icon: '/favicon.ico'
                });
            }
        });
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

// Global Floating Toast Dispatcher (Max 3, safe DOM creation)
window.showOperationalToast = function ({ id, title, message, url, type = 'info', actionBtn = null }) {
    const container = document.getElementById('toastNotificationContainer');
    if (!container) return;

    const config = TOAST_CONFIG[type] || TOAST_CONFIG.info;
    const toastDuration = config.duration;
    const toastTitle = title || config.title;

    // Enforce Max 3 toasts
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

    // Progress bar element
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

// Global User Interaction Listener to Unlock Audio Context
document.addEventListener('click', () => window.soundEngine.unlockAudio(), { once: true });
document.addEventListener('keydown', () => window.soundEngine.unlockAudio(), { once: true });

// Listen for Livewire Toast Events
document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        window.Livewire.on('notify-toast', (data) => {
            window.showOperationalToast(Array.isArray(data) ? data[0] : data);
        });
    }
});

// Listen for Echo Reverb Broadcast Events
document.addEventListener('DOMContentLoaded', () => {
    if (window.Echo) {
        window.Echo.private('orders.operations')
            .listen('OrderChanged', (event) => {
                console.log('OrderChanged broadcast received:', event);

                const { orderId, orderNumber, status, action, soundType, customerName, itemsSummary } = event;

                if (window.soundEngine.isEventProcessed(orderId, action)) {
                    return;
                }
                window.soundEngine.markEventProcessed(orderId, action);

                if (soundType === 'kitchen') {
                    window.soundEngine.playKitchenChime();
                } else if (soundType === 'delivery') {
                    window.soundEngine.playDeliveryChime();
                }

                if (action === 'ORDER_CREATED') {
                    window.showOperationalToast({
                        id: `order-${orderId}-created`,
                        title: 'NUEVO PEDIDO',
                        message: `Pedido #${orderNumber} • ${customerName || 'Venta Mostrador'} (${itemsSummary || ''})`,
                        url: '/cocina',
                        type: 'kitchen',
                        actionBtn: { text: 'VER COCINA', url: '/cocina' }
                    });
                } else if (action === 'READY') {
                    window.showOperationalToast({
                        id: `order-${orderId}-ready`,
                        title: 'PEDIDO LISTO PARA RECOGER',
                        message: `Pedido #${orderNumber} • ${customerName || 'Cliente'}`,
                        url: '/reparto',
                        type: 'delivery',
                        actionBtn: { text: 'VER REPARTO', url: '/reparto' }
                    });
                } else if (action === 'DELIVERING') {
                    const readyToast = document.getElementById(`order-${orderId}-ready`);
                    if (readyToast) readyToast.remove();
                }

                window.soundEngine.showBrowserNotification(
                    action === 'ORDER_CREATED' ? 'NUEVO PEDIDO' : (action === 'READY' ? 'PEDIDO LISTO' : 'Actualización de Pedido'),
                    `Pedido #${orderNumber} (${customerName || ''})`,
                    action === 'ORDER_CREATED' ? '/cocina' : '/reparto'
                );

                if (window.Livewire) {
                    window.Livewire.dispatch('order-changed-realtime', { orderId, action, status });
                }
            });
    }
});
