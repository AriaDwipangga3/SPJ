import api from '@/lib/api';

export default {
  index(jenisSpjId) {
    return api.get(`/jenis-spj/${jenisSpjId}/dokumen-wajib`).then((res) => res.data);
  },
  store(jenisSpjId, payload) {
    return api.post(`/jenis-spj/${jenisSpjId}/dokumen-wajib`, payload).then((res) => res.data);
  },
  update(id, payload) {
    return api.put(`/dokumen-wajib/${id}`, payload).then((res) => res.data);
  },
  destroy(id) {
    return api.delete(`/dokumen-wajib/${id}`).then((res) => res.data);
  },
};
