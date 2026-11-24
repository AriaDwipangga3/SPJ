// VITE_API_BASE_URL biasanya "http://localhost:8000/api" — file publik
// ada di "http://localhost:8000/storage/...", jadi buang suffix "/api".
const BASE = import.meta.env.VITE_API_BASE_URL.replace(/\/api\/?$/, '');

export function storageUrl(path) {
  if (!path) return null;
  return `${BASE}/storage/${path}`;
}
