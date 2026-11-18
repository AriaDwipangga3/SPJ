import { Capacitor } from '@capacitor/core';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { Share } from '@capacitor/share';

/**
 * Unduh file dari Blob.
 *
 * - Browser (web laptop / HP lewat browser): pakai <a download> seperti biasa.
 * - APK (Capacitor Android): WebView TIDAK bisa menangani <a download> dengan
 *   blob URL, jadi file ditulis ke cache aplikasi lalu dibuka lewat share sheet
 *   Android. Dari sana pengguna bisa pilih "Simpan ke File/Drive", buka dengan
 *   aplikasi PDF/Excel, atau kirim lewat WhatsApp.
 *
 * Fungsi ini async: pakai `await downloadBlob(...)` supaya error bisa ditangkap.
 */
export async function downloadBlob(blob, filename) {
  const name = sanitizeFilename(filename);

  if (Capacitor.isNativePlatform()) {
    await saveAndShareNative(blob, name);
    return;
  }

  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = name;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => window.URL.revokeObjectURL(url), 100);
}

async function saveAndShareNative(blob, name) {
  const data = await blobToBase64(blob);

  const { uri } = await Filesystem.writeFile({
    path: `unduhan/${name}`,
    data,
    directory: Directory.Cache,
    recursive: true,
  });

  try {
    await Share.share({
      title: name,
      dialogTitle: 'Simpan atau bagikan file',
      url: uri,
    });
  } catch (err) {
    // Pengguna menutup share sheet tanpa memilih apa pun -> bukan error.
    if (/cancel/i.test(err?.message || '')) return;
    throw err;
  }
}

function blobToBase64(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => {
      // hasil: "data:<mime>;base64,<isi>" -> ambil bagian setelah koma
      const result = String(reader.result || '');
      resolve(result.slice(result.indexOf(',') + 1));
    };
    reader.onerror = () => reject(reader.error || new Error('Gagal membaca file.'));
    reader.readAsDataURL(blob);
  });
}

// Buang karakter yang tidak boleh ada di nama file Android/Windows.
function sanitizeFilename(name) {
  return String(name || 'file').replace(/[\\/:*?"<>|]+/g, '_');
}

/**
 * Bikin nama file export dengan timestamp.
 * @param {string} prefix - mis. 'SPJ'
 * @param {string} ext - 'xlsx' (default) | 'csv'
 * @returns {string} mis. 'SPJ_2026-09-23_1430.xlsx'
 */
export function generateExportFilename(prefix = 'SPJ', ext = 'xlsx') {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  const stamp =
    now.getFullYear() +
    '-' +
    pad(now.getMonth() + 1) +
    '-' +
    pad(now.getDate()) +
    '_' +
    pad(now.getHours()) +
    pad(now.getMinutes());
  return `${prefix}_${stamp}.${ext}`;
}

/**
 * @deprecated Pakai generateExportFilename. Dibiarkan untuk backward compatibility.
 */
export function generateCsvFilename(prefix = 'SPJ') {
  return generateExportFilename(prefix, 'csv');
}
