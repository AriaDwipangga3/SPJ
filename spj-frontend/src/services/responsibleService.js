import api from '@/lib/api';

export default {
  index(params = {}) {
    return api.get('/responsibles', { params }).then((res) => res.data);
  },

  store(payload) {
    return api.post('/responsibles', payload).then((res) => res.data);
  },

  update(id, payload) {
    return api.put(`/responsibles/${id}`, payload).then((res) => res.data);
  },

  destroy(id) {
    return api.delete(`/responsibles/${id}`).then((res) => res.data);
  },

  ensureSelf() {
    return api.post('/responsibles/ensure-self').then((res) => res.data);
  },
};