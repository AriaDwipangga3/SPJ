import api from '@/lib/api';

export default {
  /**
   * List user dengan pagination + search.
   * @param {Object} params - { page, per_page, search, role }
   */
  index(params = {}) {
    return api.get('/users', { params }).then((res) => res.data);
  },

  store(payload) {
    return api.post('/users', payload).then((res) => res.data);
  },

  update(id, payload) {
    return api.put(`/users/${id}`, payload).then((res) => res.data);
  },

  destroy(id) {
    return api.delete(`/users/${id}`).then((res) => res.data);
  },
};