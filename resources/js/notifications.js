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
        // Keep last 100 events in session storage
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

// Floating Toast Notification Dispatcher
window.showOperationalToast = function ({ id, title, message, url, type = 'info', persistent = false, actionBtn = null }) {
    const container = document.getElementById('toastNotificationContainer');
    if (!container) return;

    const toastId = id || 'toast-' + Math.random().toString(36).substring(2, 9);

    // Remove existing toast with same ID if any
    const existing = document.getElementById(toastId);
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast-card toast-${type} page-fade-in`;
    
    let actionBtnHtml = '';
    if (actionBtn) {
        actionBtnHtml = `<a href="${actionBtn.url}" class="toast-action-btn">${actionBtn.text}</a>`;
    } else if (url) {
        actionBtnHtml = `<a href="${url}" class="toast-action-btn">VER</a>`;
    }

    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-header">
                <span class="toast-title">${title}</span>
                <button type="button" onclick="this.closest('.toast-card').remove()" class="toast-close-btn">&times;</button>
            </div>
            <p class="toast-message">${message}</p>
            ${actionBtnHtml}
        </div>
    `;

    container.appendChild(toast);

    if (!persistent) {
        setTimeout(() => {
            if (document.getElementById(toastId)) {
                toast.classList.add('toast-fade-out');
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
};

// Global User Interaction Listener to Unlock Audio Context
document.addEventListener('click', () => window.soundEngine.unlockAudio(), { once: true });
document.addEventListener('keydown', () => window.soundEngine.unlockAudio(), { once: true });

// Listen for Echo Reverb Broadcast Events
document.addEventListener('DOMContentLoaded', () => {
    if (window.Echo) {
        window.Echo.private('orders.operations')
            .listen('OrderChanged', (event) => {
                console.log('OrderChanged realtime broadcast event received:', event);

                const { orderId, orderNumber, status, action, soundType, customerName, itemsSummary } = event;

                // Check deduplication
                if (window.soundEngine.isEventProcessed(orderId, action)) {
                    return;
                }
                window.soundEngine.markEventProcessed(orderId, action);

                // Play Audio based on event soundType
                if (soundType === 'kitchen') {
                    window.soundEngine.playKitchenChime();
                } else if (soundType === 'delivery') {
                    window.soundEngine.playDeliveryChime();
                }

                // Show Floating Toast
                if (action === 'ORDER_CREATED') {
                    window.showOperationalToast({
                        id: `order-${orderId}-created`,
                        title: 'NUEVO PEDIDO',
                        message: `Pedido #${orderNumber} • ${customerName}<br><small>${itemsSummary}</small>`,
                        url: '/cocina',
                        type: 'kitchen',
                        persistent: false
                    });
                } else if (action === 'READY') {
                    window.showOperationalToast({
                        id: `order-${orderId}-ready`,
                        title: 'PEDIDO LISTO PARA RECOGER',
                        message: `Pedido #${orderNumber} • ${customerName}`,
                        url: '/reparto',
                        type: 'delivery',
                        persistent: true,
                        actionBtn: { text: 'VER REPARTO', url: '/reparto' }
                    });
                } else if (action === 'DELIVERING') {
                    // Remove persistent READY toast for this order if taken
                    const readyToast = document.getElementById(`order-${orderId}-ready`);
                    if (readyToast) readyToast.remove();
                }

                // Show Browser Notification if enabled
                window.soundEngine.showBrowserNotification(
                    action === 'ORDER_CREATED' ? 'NUEVO PEDIDO' : (action === 'READY' ? 'PEDIDO LISTO' : 'Actualización de Pedido'),
                    `Pedido #${orderNumber} (${customerName})`,
                    action === 'ORDER_CREATED' ? '/cocina' : '/reparto'
                );

                // Dispatch Livewire event to refresh KDS or Delivery components
                if (window.Livewire) {
                    window.Livewire.dispatch('order-changed-realtime', { orderId, action, status });
                }
            });
    }
});
