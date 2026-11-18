import api from '@/lib/api';

export default {
  index() {
    return api.get('/backups').then((r) => r.data);
  },

  run() {
    return api.post('/backups/run').then((r) => r.data);
  },

  download(filename) {
    return api
      .get(`/backups/${encodeURIComponent(filename)}/download`, {
        responseType: 'blob',
      })
      .then((r) => r.data);
  },

  destroy(filename) {
    return api
      .delete(`/backups/${encodeURIComponent(filename)}`)
      .then((r) => r.data);
  },

  getSettings() {
    return api.get('/settings/backup').then((r) => r.data);
  },

  updateSettings(payload) {
    return api.put('/settings/backup', payload).then((r) => r.data);
  },
};