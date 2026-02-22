import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Use the custom auth endpoint for our API structure
    authEndpoint: (import.meta.env.VITE_API_BASE_URL || '/api/v1') + '/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
            Accept: 'application/json',
        },
    },
});

// Interceptor to ensure token is fresh for private channels
echo.connector.pusher.connection.bind('state_change', (states) => {
    if (states.current === 'connecting') {
        echo.options.auth.headers.Authorization = `Bearer ${localStorage.getItem('token')}`;
    }
});

export default echo;
