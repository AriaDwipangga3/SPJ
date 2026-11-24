import api from '@/lib/api';

export default {
  /**
   * List notifikasi + unread count.
   * @param {Object} params - { limit, unread_only }
   */
  index(params = {}) {
    return api.get('/notifications', { params }).then((r) => r.data);
  },

  /**
   * Ringan — cuma unread count (untuk polling).
   */
  unreadCount() {
    return api.get('/notifications/unread-count').then((r) => r.data);
  },

  markAsRead(id) {
    return api.post(`/notifications/${id}/read`).then((r) => r.data);
  },

  markAllAsRead() {
    return api.post('/notifications/mark-all-read').then((r) => r.data);
  },

  destroy(id) {
    return api.delete(`/notifications/${id}`).then((r) => r.data);
  },

  clearRead() {
    return api.delete('/notifications/clear-read').then((r) => r.data);
  },
};