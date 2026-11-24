import { ref } from 'vue';

// State auth global (singleton), dipisah dari useAuth.js supaya bisa
// diimpor juga oleh lib/api.js tanpa membuat circular import
// (api.js -> useAuth.js -> authService.js -> api.js).
export const user = ref(JSON.parse(localStorage.getItem('spj_user') || 'null'));

export function clearAuthState() {
  localStorage.removeItem('spj_token');
  localStorage.removeItem('spj_user');
  user.value = null;
}
