import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import api from '@/lib/api';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
  wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
  forceTLS: false,
  enabledTransports: ['ws'],
  disableStats: true,
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      api.post('/broadcasting/auth', {
        socket_id: socketId,
        channel_name: channel.name,
      })
        .then((response) => callback(false, response.data))
        .catch((error) => callback(true, error));
    },
  }),
});

export default echo;