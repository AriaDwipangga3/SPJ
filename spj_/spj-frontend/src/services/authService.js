import api from '@/lib/api';

export default {
  login(email, password) {
    return api.post('/login', { email, password }).then((res) => res.data);
  },
  logout() {
    return api.post('/logout').then((res) => res.data);
  },
  me() {
    return api.get('/me').then((res) => res.data);
  },
};
