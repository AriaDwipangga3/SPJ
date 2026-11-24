import api from '@/lib/api';

export default {
  index() {
    return api.get('/jenis-spj').then((res) => res.data);
  },
  show(id) {
    return api.get(`/jenis-spj/${id}`).then((res) => res.data);
  },
  store(payload) {
    return api.post('/jenis-spj', payload).then((res) => res.data);
  },
  update(id, payload) {
    return api.put(`/jenis-spj/${id}`, payload).then((res) => res.data);
  },
  destroy(id) {
    return api.delete(`/jenis-spj/${id}`).then((res) => res.data);
  },
};
