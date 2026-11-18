export function formatTanggal(tanggal) {
  if (!tanggal) return '-';
  return new Date(tanggal).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

export function formatRupiah(angka) {
  if (angka === null || angka === undefined) return '-';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(angka);
}

export function formatUkuranFile(kb) {
  if (!kb) return '-';
  if (kb < 1024) return `${kb} KB`;
  return `${(kb / 1024).toFixed(1)} MB`;
}

/**
 * Format angka rupiah jadi bentuk singkat — untuk stat card & chart axis.
 * Rp 100.000 → "Rp 100 rb"
 * Rp 1.500.000 → "Rp 1,5 jt"
 * Rp 100.000.000 → "Rp 100 jt"
 * Rp 1.500.000.000 → "Rp 1,5 M"
 * Rp 1.500.000.000.000 → "Rp 1,5 T"
 */
export function formatRupiahSingkat(val) {
  if (val === null || val === undefined || val === '') return 'Rp 0';
  const num = parseFloat(val);
  if (isNaN(num)) return 'Rp 0';
  if (num === 0) return 'Rp 0';

  const abs = Math.abs(num);
  const sign = num < 0 ? '-' : '';

  const fmt = (div, suffix) => {
    const v = abs / div;
    // Kalau pas (100, 1.5), tampilkan 1 desimal kalau perlu
    const display = v % 1 === 0 ? v.toString() : v.toFixed(1).replace('.', ',');
    return `${sign}Rp ${display} ${suffix}`;
  };

  if (abs >= 1_000_000_000_000) return fmt(1_000_000_000_000, 'T');
  if (abs >= 1_000_000_000) return fmt(1_000_000_000, 'M');
  if (abs >= 1_000_000) return fmt(1_000_000, 'jt');
  if (abs >= 1_000) return fmt(1_000, 'rb');
  return `${sign}Rp ${abs}`;
}

/**
 * Format angka untuk chart Y-axis — tanpa "Rp" biar hemat ruang.
 */
export function formatAngkaSingkat(val) {
  if (val === null || val === undefined) return '0';
  const num = parseFloat(val);
  if (isNaN(num) || num === 0) return '0';

  const abs = Math.abs(num);
  const sign = num < 0 ? '-' : '';

  const fmt = (div, suffix) => {
    const v = abs / div;
    const display = v % 1 === 0 ? v.toString() : v.toFixed(1).replace('.', ',');
    return `${sign}${display}${suffix}`;
  };

  if (abs >= 1_000_000_000_000) return fmt(1_000_000_000_000, 'T');
  if (abs >= 1_000_000_000) return fmt(1_000_000_000, 'M');
  if (abs >= 1_000_000) return fmt(1_000_000, 'jt');
  if (abs >= 1_000) return fmt(1_000, 'rb');
  return `${sign}${abs}`;
}