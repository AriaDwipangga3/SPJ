import api from '@/lib/api';

export default {
  index(params = {}) {
    return api.get('/spj', { params }).then((res) => res.data);
  },

  show(slug) {
    return api.get(`/spj/${slug}`).then((res) => res.data);
  },

  store(payload) {
    return api.post('/spj', payload).then((res) => res.data);
  },

  update(slug, payload) {
    return api.put(`/spj/${slug}`, payload).then((res) => res.data);
  },

  destroy(slug) {
    return api.delete(`/spj/${slug}`).then((res) => res.data);
  },

  exportCsv(params = {}) {
    return api
      .get('/spj/export', {
        params,
        responseType: 'blob',
      })
      .then((res) => res.data);
  },

  exportPdf(slug) {
    return api
      .get(`/spj/${slug}/pdf`, {
        responseType: 'blob',
      })
      .then((res) => res.data);
  },

  nextNumber() {
    return api.get('/spj/next-number').then((res) => res.data);
  },

  stats() {
    return api.get('/spj/stats').then((r) => r.data);
  },

  verify(slug, catatan = null) {
    return api.post(`/spj/${slug}/verify`, { catatan }).then((res) => res.data);
  },

  reject(slug, catatan) {
    return api.post(`/spj/${slug}/reject`, { catatan }).then((res) => res.data);
  },

  uploadDokumen(spjSlug, dokumenId, file, sumber) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('sumber_file', sumber);
    return api
      .post(`/spj/${spjSlug}/dokumen/${dokumenId}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((res) => res.data);
  },

  simpanTandaTanganGambar(spjSlug, pihak, base64) {
    return api
      .post(`/spj/${spjSlug}/tanda-tangan/${pihak}`, {
        metode: 'gambar',
        signature_base64: base64,
      })
      .then((res) => res.data);
  },

  simpanTandaTanganUpload(spjSlug, pihak, file) {
    const formData = new FormData();
    formData.append('metode', 'upload');
    formData.append('file', file);
    return api
      .post(`/spj/${spjSlug}/tanda-tangan/${pihak}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((res) => res.data);
  },
};