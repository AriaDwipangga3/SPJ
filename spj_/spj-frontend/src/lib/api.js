import axios from 'axios';
import router from '@/router';
import { clearAuthState } from '@/state/authState';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
  },
});

// Sisipkan token Sanctum ke setiap request kalau sudah login
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('spj_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Guard supaya redirect ke /login hanya dieksekusi sekali,
// walaupun ada beberapa request yang gagal 401 secara bersamaan
// (mis. NotificationBell polling + fetch dashboard di saat bersamaan).
let isRedirectingToLogin = false;

// Kalau token invalid/expired, otomatis lempar ke halaman login.
// PENTING: pakai router.push (navigasi SPA), BUKAN window.location.href.
// window.location.href memaksa hard reload seluruh halaman, dan kalau ada
// lebih dari satu request yang gagal 401 (polling notifikasi, stats, dsb),
// itu bisa memicu reload berkali-kali beruntun ("reload terus").
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    // Request unduhan memakai responseType 'blob', jadi body error dari server
    // (JSON berisi "message") ikut dibungkus jadi Blob dan tidak terbaca.
    // Ubah balik ke objek JSON supaya err.response.data.message berfungsi
    // seperti request biasa.
    const data = error.response?.data;
    if (data instanceof Blob) {
      try {
        const text = await data.text();
        error.response.data = JSON.parse(text);
      } catch {
        // Bukan JSON (mis. halaman error HTML) -> biarkan, pesan generik dipakai.
        error.response.data = {};
      }
    }

    const status = error.response?.status;
    const alreadyOnLogin = router.currentRoute.value.name === 'login';

    if (status === 401 && !alreadyOnLogin && !isRedirectingToLogin) {
      isRedirectingToLogin = true;
      clearAuthState();

      router.push({ name: 'login' }).finally(() => {
        // reset guard setelah navigasi selesai, biar redirect berikutnya
        // (mis. sesi expired lagi di lain waktu) tetap bisa jalan
        isRedirectingToLogin = false;
      });
    }
    return Promise.reject(error);
  }
);

export default api;
