import api from '@/lib/api';

export default {
  /**
   * Kirim email reset password.
   * @param {Object} payload - { email }
   */
  forgotPassword(payload) {
    return api.post('/forgot-password', payload).then((r) => r.data);
  },

  /**
   * Submit password baru dengan token dari email.
   * @param {Object} payload - { token, email, password, password_confirmation }
   */
  resetPassword(payload) {
    return api.post('/reset-password', payload).then((r) => r.data);
  },
};