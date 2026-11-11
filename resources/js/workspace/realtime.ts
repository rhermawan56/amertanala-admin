import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
let shared: Echo<'reverb'> | undefined;
export function realtime(guest = false): Echo<'reverb'> | null {
    if (!import.meta.env.VITE_REVERB_APP_KEY) return null;
    if (shared) return shared;
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('AMERTANALA-XSRF='))
        ?.split('=')
        .slice(1)
        .join('=');
    shared = new Echo({
        broadcaster: 'reverb',
        client: undefined,
        Pusher,
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8081),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: guest ? '/chat/broadcasting/auth' : '/broadcasting/auth',
        auth: { headers: token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {} },
    });
    return shared;
}
