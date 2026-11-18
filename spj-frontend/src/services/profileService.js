import api from '@/lib/api';

export default {
  /**
   * Update nama lengkap user.
   */
  updateProfile(payload) {
    return api.put('/me', payload).then((res) => res.data);
  },

  /**
   * Ganti password (butuh password lama).
   * @param {Object} payload - { current_password, password, password_confirmation }
   */
  changePassword(payload) {
    return api.post('/me/change-password', payload).then((res) => res.data);
  },
};