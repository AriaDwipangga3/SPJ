/**
 * Satu sumber kebenaran untuk label & warna status SPJ.
 * Dipakai di Dashboard, DaftarSpj, DetailSpj, dll — supaya kalau
 * suatu saat mau ganti wording/warna, cukup ubah di sini.
 */
export const STATUS_SPJ = {
  draft: { label: 'Draft', badgeClass: 'bg-surface-disabled text-text-secondary' },
  belum_lengkap: { label: 'Belum Lengkap', badgeClass: 'bg-error-bg text-error' },
  proses_verifikasi: { label: 'Proses Verifikasi', badgeClass: 'bg-surface-disabled text-text-secondary' },
  terverifikasi: { label: 'Terverifikasi', badgeClass: 'bg-primary text-white' },
  selesai: { label: 'Selesai', badgeClass: 'bg-primary text-white' },
};

export function statusInfo(status) {
  return STATUS_SPJ[status] ?? { label: status ?? '-', badgeClass: 'bg-surface-disabled text-text-secondary' };
}
