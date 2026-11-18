/**
 * Deteksi apakah device saat ini kemungkinan besar mobile (Android/iOS).
 *
 * Kenapa perlu ini: atribut HTML `capture="environment"` pada
 * <input type="file"> cuma benar-benar membuka kamera di browser mobile.
 * Di desktop browser, atribut ini diabaikan dan cuma membuka file picker
 * biasa — jadi tombol "Ambil Foto" terasa tidak berfungsi/membingungkan
 * kalau ditampilkan di web desktop. Solusinya: sembunyikan tombol itu
 * kalau device-nya bukan mobile, cukup sisakan "Pilih File".
 */
export function isMobileDevice() {
  if (typeof navigator === 'undefined') return false;
  return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}
