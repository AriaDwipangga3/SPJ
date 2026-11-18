/**
 * Format angka jadi ribuan dengan titik (Indonesia style).
 * Contoh: 1234567 → "1.234.567"
 */
export function formatRibuan(val) {
  if (val === null || val === undefined || val === '') return '';
  const num = String(val).replace(/[^\d]/g, '');
  if (!num) return '';
  return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

/**
 * Parse string dengan titik jadi angka murni.
 * Contoh: "1.234.567" → 1234567
 */
export function parseRibuan(val) {
  if (val === null || val === undefined || val === '') return '';
  return String(val).replace(/[^\d]/g, '');
}
/**
 * Konversi angka ke terbilang bahasa Indonesia.
 * Contoh: 1234567 → "satu juta dua ratus tiga puluh empat ribu lima ratus enam puluh tujuh"
 */
export function terbilang(angka) {
  const num = parseInt(angka, 10);
  if (!num || num === 0) return 'nol';

  const satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

  function convert(n) {
    n = Math.floor(n);
    if (n < 12) return satuan[n];
    if (n < 20) return convert(n - 10) + ' belas';
    if (n < 100) return convert(Math.floor(n / 10)) + ' puluh ' + convert(n % 10);
    if (n < 200) return 'seratus ' + convert(n - 100);
    if (n < 1000) return convert(Math.floor(n / 100)) + ' ratus ' + convert(n % 100);
    if (n < 2000) return 'seribu ' + convert(n - 1000);
    if (n < 1000000) return convert(Math.floor(n / 1000)) + ' ribu ' + convert(n % 1000);
    if (n < 1000000000) return convert(Math.floor(n / 1000000)) + ' juta ' + convert(n % 1000000);
    if (n < 1000000000000) return convert(Math.floor(n / 1000000000)) + ' miliar ' + convert(n % 1000000000);
    return convert(Math.floor(n / 1000000000000)) + ' triliun ' + convert(n % 1000000000000);
  }

  return convert(num).replace(/\s+/g, ' ').trim();
}
/**
 * Format rupiah lengkap.
 * Contoh: 1234567 → "Rp 1.234.567"
 */
export function formatRupiahLengkap(val) {
  if (val === null || val === undefined || val === '') return 'Rp 0';
  const num = parseFloat(val);
  if (isNaN(num)) return 'Rp 0';
  return 'Rp ' + num.toLocaleString('id-ID');
}