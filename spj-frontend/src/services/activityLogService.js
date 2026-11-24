import api from '@/lib/api';

export default {
  index(params = {}) {
    return api.get('/activity-logs', { params }).then((r) => r.data);
  },

  show(id) {
    return api.get(`/activity-logs/${id}`).then((r) => r.data);
  },

  summary(params = {}) {
    return api.get('/activity-logs/summary', { params }).then((r) => r.data);
  },

  cleanup(days = 90) {
    return api.delete('/activity-logs/cleanup', { data: { days } }).then((r) => r.data);
  },
};