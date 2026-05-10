import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;

if (key) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    document.addEventListener('DOMContentLoaded', () => {
        const uid = window.__auditUserId;
        if (!uid || !window.Echo) {
            return;
        }

        window.Echo.private(`App.Models.User.${uid}`).listen('.mission.workflow', () => {
            if (typeof window.refreshAuditNotifications === 'function') {
                window.refreshAuditNotifications();
            }
        });
    });
}
