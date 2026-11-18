import { onMounted, onUnmounted } from 'vue';
import echo from '@/lib/echo';

/**
 * Dengarkan sinyal "data SPJ berubah" dari server (Reverb), lalu panggil `onChange`.
 *
 * - Dipanggil dengan jeda (debounce) karena satu aksi bisa memicu beberapa sinyal.
 * - Realtime hanya pelengkap: kalau koneksi gagal, halaman tetap jalan seperti biasa.
 * - Saat halaman ditutup hanya listener milik halaman ini yang dilepas
 *   (bukan echo.leave), supaya halaman lain yang memakai channel sama tidak ikut putus.
 */
export function useSpjRealtime(onChange, delay = 400) {
  let channel = null;
  let timer = null;

  const handler = () => {
    clearTimeout(timer);
    timer = setTimeout(() => onChange(), delay);
  };

  onMounted(() => {
    try {
      channel = echo.private('spj-updates');
      channel.listen('.SpjChanged', handler);
    } catch (e) {
      console.warn('Realtime SPJ tidak aktif:', e);
    }
  });

  onUnmounted(() => {
    clearTimeout(timer);
    try {
      channel?.stopListening('.SpjChanged', handler);
    } catch (e) {
      /* abaikan */
    }
  });
}
