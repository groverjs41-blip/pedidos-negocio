import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'pedidos-reverb-key';
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const isHttps = window.location.protocol === 'https:';
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || (isHttps ? 'https' : 'http');
const currentPort = window.location.port ? parseInt(window.location.port) : (isHttps ? 443 : 80);
const reverbPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : currentPort;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
});

window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('[Reverb WebSocket] Connected successfully to ' + reverbHost + ':' + reverbPort);
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.warn('[Reverb WebSocket] Connection error:', err);
});
