import { computed } from 'vue';
import authService from '@/services/authService';
import { user, clearAuthState } from '@/state/authState';

export function useAuth() {
  const isAdmin = computed(() => user.value?.role === 'admin');
  const isLoggedIn = computed(() => !!user.value);

  async function login(email, password) {
    const data = await authService.login(email, password);
    localStorage.setItem('spj_token', data.token);
    localStorage.setItem('spj_user', JSON.stringify(data.user));
    user.value = data.user;
    return data.user;
  }

  async function logout() {
    try {
      await authService.logout();
    } finally {
      clearAuthState();
    }
  }

  return { user, isAdmin, isLoggedIn, login, logout };
}