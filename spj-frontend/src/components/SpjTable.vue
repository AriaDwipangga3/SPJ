<script setup>
import { RouterLink } from 'vue-router';
import StatusBadge from '@/components/StatusBadge.vue';
import { formatTanggal } from '@/helpers/format';

const props = defineProps({
  items: { type: Array, required: true },
  isAdmin: { type: Boolean, default: false },
  noWrapper: { type: Boolean, default: false },
});

const emit = defineEmits(['hapus']);

const STATUS_FINAL = ['terverifikasi', 'selesai'];

function bisaDiedit(spj) {
  return !STATUS_FINAL.includes(spj.status);
}

function butuhVerifikasi(spj) {
  return spj.status === 'proses_verifikasi';
}

function inisial(nama) {
  if (!nama) return '?';
  return nama.trim().charAt(0).toUpperCase();
}

function namaJenisSpj(spj) {
  return spj?.jenis_spj?.nama ?? spj?.jenisSpj?.nama ?? '-';
}

function konfirmasiHapus(spj) {
  if (confirm(`Hapus SPJ ${spj.nomor_spj}? Tindakan ini tidak bisa dibatalkan.`)) {
    emit('hapus', spj);
  }
}

function konfirmasiHapusAdmin(spj) {
  const pesan = STATUS_FINAL.includes(spj.status)
    ? `SPJ ${spj.nomor_spj} sudah berstatus FINAL (${spj.status}). Hapus paksa sebagai admin? Tindakan ini tidak bisa dibatalkan dan menghilangkan jejak dokumen resmi.`
    : `Hapus SPJ ${spj.nomor_spj} sebagai admin? Tindakan ini tidak bisa dibatalkan.`;
  if (confirm(pesan)) {
    emit('hapus', spj);
  }
}
</script>

<template>
  <div
    :class="
      noWrapper
        ? ''
        : 'border border-border rounded-DEFAULT shadow-elevation-1 bg-surface overflow-hidden'
    "
  >
    <!-- Empty state -->
    <div v-if="items.length === 0" class="px-4 py-12 text-center">
      <div class="flex flex-col items-center gap-2">
        <span class="material-symbols-outlined !text-4xl text-text-subtle">inbox</span>
        <p class="text-sm font-medium text-text-secondary">Belum ada data SPJ</p>
        <p class="text-xs text-text-subtle max-w-xs">
          {{
            isAdmin
              ? 'Belum ada berkas SPJ yang masuk dari user.'
              : 'Klik "Buat SPJ Baru" untuk memulai pengajuan.'
          }}
        </p>
      </div>
    </div>

    <template v-else>
      <!-- ============ DESKTOP: TABLE ============ -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-border text-left text-text-secondary bg-surface-disabled/50">
              <th class="px-4 py-3 font-medium">Nomor SPJ</th>
              <th class="px-4 py-3 font-medium">Tanggal</th>
              <th class="px-4 py-3 font-medium">Jenis SPJ</th>
              <th class="px-4 py-3 font-medium">Pihak I</th>
              <th class="px-4 py-3 font-medium">Pihak II</th>
              <th class="px-4 py-3 font-medium">Status</th>
              <th class="px-4 py-3 font-medium text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="spj in items"
              :key="spj.id"
              class="border-b border-border-soft last:border-0 hover:bg-surface-disabled/30 transition-colors"
            >
              <td class="px-4 py-3">
                <RouterLink
                  :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
                  class="font-medium hover:underline font-mono-id text-xs"
                >
                  {{ spj.nomor_spj }}
                </RouterLink>
              </td>

              <td class="px-4 py-3 text-text-secondary whitespace-nowrap">
                {{ formatTanggal(spj.tanggal) }}
              </td>

              <td class="px-4 py-3 text-text-secondary">
                {{ namaJenisSpj(spj) }}
              </td>

              <td class="px-4 py-3">
                <div v-if="spj.pihak1" class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-pill bg-primary text-white text-[10px] font-semibold flex items-center justify-center shrink-0">
                    {{ inisial(spj.pihak1.nama) }}
                  </span>
                  <div class="min-w-0 max-w-[160px]">
                    <p class="truncate font-medium">{{ spj.pihak1.nama }}</p>
                    <p class="text-xs text-text-subtle truncate">{{ spj.pihak1.jabatan || '-' }}</p>
                  </div>
                </div>
                <span v-else class="text-text-subtle">—</span>
              </td>

              <td class="px-4 py-3">
                <div v-if="spj.pihak2" class="flex items-center gap-2 min-w-0">
                  <span class="w-7 h-7 rounded-pill bg-surface-disabled text-text-secondary text-[10px] font-semibold flex items-center justify-center shrink-0">
                    {{ inisial(spj.pihak2.nama) }}
                  </span>
                  <div class="min-w-0 max-w-[160px]">
                    <p class="truncate font-medium">{{ spj.pihak2.nama }}</p>
                    <p class="text-xs text-text-subtle truncate">{{ spj.pihak2.instansi || '-' }}</p>
                  </div>
                </div>
                <span v-else class="text-text-subtle">—</span>
              </td>

              <td class="px-4 py-3">
                <StatusBadge :status="spj?.status || 'draft'" />
              </td>

              <td class="px-4 py-3">
                <div class="flex justify-end gap-1.5">
                  <RouterLink
                    :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
                    class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled whitespace-nowrap inline-flex items-center gap-1"
                  >
                    <span class="material-symbols-outlined !text-sm">visibility</span>
                    Detail
                  </RouterLink>

                  <template v-if="isAdmin">
                    <RouterLink
                      v-if="butuhVerifikasi(spj)"
                      :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
                      class="text-xs font-medium px-2.5 py-1.5 rounded-inner bg-primary text-white hover:bg-primary-hover whitespace-nowrap inline-flex items-center gap-1"
                    >
                      <span class="material-symbols-outlined !text-sm">check_circle</span>
                      Verifikasi
                    </RouterLink>
                    <button
                      type="button"
                      @click="konfirmasiHapusAdmin(spj)"
                      class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border text-error hover:bg-error-bg whitespace-nowrap inline-flex items-center gap-1"
                    >
                      <span class="material-symbols-outlined !text-sm">delete</span>
                    </button>
                  </template>

                  <template v-else>
                    <RouterLink
                      v-if="bisaDiedit(spj)"
                      :to="{ name: 'spj.edit', params: { slug: spj.slug } }"
                      class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled whitespace-nowrap inline-flex items-center gap-1"
                    >
                      <span class="material-symbols-outlined !text-sm">edit</span>
                      Edit
                    </RouterLink>
                    <button
                      v-if="bisaDiedit(spj)"
                      type="button"
                      @click="konfirmasiHapus(spj)"
                      class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border text-error hover:bg-error-bg whitespace-nowrap inline-flex items-center gap-1"
                    >
                      <span class="material-symbols-outlined !text-sm">delete</span>
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ============ MOBILE: CARD LIST ============ -->
      <div class="md:hidden divide-y divide-border-soft">
        <div v-for="spj in items" :key="`card-${spj.id}`" class="p-4 space-y-3">
          <div class="flex items-start justify-between gap-2">
            <RouterLink
              :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
              class="font-mono-id text-xs font-medium hover:underline break-all"
            >
              {{ spj.nomor_spj }}
            </RouterLink>
            <StatusBadge :status="spj?.status || 'draft'" />
          </div>

          <div class="flex items-center flex-wrap gap-x-2 gap-y-0.5 text-xs text-text-secondary">
            <span class="inline-flex items-center gap-1">
              <span class="material-symbols-outlined !text-sm text-text-subtle">calendar_today</span>
              {{ formatTanggal(spj.tanggal) }}
            </span>
            <span class="text-text-subtle">·</span>
            <span class="truncate">{{ namaJenisSpj(spj) }}</span>
          </div>

          <div class="grid grid-cols-1 gap-2 pt-1">
            <div v-if="spj.pihak1" class="flex items-center gap-2 min-w-0">
              <span class="text-[10px] font-semibold text-text-subtle uppercase tracking-wider shrink-0 w-12">
                Pihak I
              </span>
              <span class="w-6 h-6 rounded-pill bg-primary text-white text-[10px] font-semibold flex items-center justify-center shrink-0">
                {{ inisial(spj.pihak1.nama) }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-medium truncate">{{ spj.pihak1.nama }}</p>
                <p class="text-[10px] text-text-subtle truncate">{{ spj.pihak1.jabatan || '-' }}</p>
              </div>
            </div>

            <div v-if="spj.pihak2" class="flex items-center gap-2 min-w-0">
              <span class="text-[10px] font-semibold text-text-subtle uppercase tracking-wider shrink-0 w-12">
                Pihak II
              </span>
              <span class="w-6 h-6 rounded-pill bg-surface-disabled text-text-secondary text-[10px] font-semibold flex items-center justify-center shrink-0">
                {{ inisial(spj.pihak2.nama) }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-medium truncate">{{ spj.pihak2.nama }}</p>
                <p class="text-[10px] text-text-subtle truncate">{{ spj.pihak2.instansi || '-' }}</p>
              </div>
            </div>
          </div>

          <div class="flex gap-2 pt-1">
            <RouterLink
              :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
              class="flex-1 text-xs font-medium px-3 py-2 rounded-inner border border-border hover:bg-surface-disabled text-center inline-flex items-center justify-center gap-1"
            >
              <span class="material-symbols-outlined !text-sm">visibility</span>
              Detail
            </RouterLink>

            <template v-if="isAdmin">
              <RouterLink
                v-if="butuhVerifikasi(spj)"
                :to="{ name: 'spj.detail', params: { slug: spj.slug } }"
                class="flex-1 text-xs font-medium px-3 py-2 rounded-inner bg-primary text-white hover:bg-primary-hover text-center inline-flex items-center justify-center gap-1"
              >
                <span class="material-symbols-outlined !text-sm">check_circle</span>
                Verifikasi
              </RouterLink>
              <button
                type="button"
                @click="konfirmasiHapusAdmin(spj)"
                class="text-xs font-medium px-3 py-2 rounded-inner border border-border text-error hover:bg-error-bg inline-flex items-center justify-center gap-1 shrink-0"
                aria-label="Hapus"
              >
                <span class="material-symbols-outlined !text-sm">delete</span>
              </button>
            </template>

            <template v-else>
              <RouterLink
                v-if="bisaDiedit(spj)"
                :to="{ name: 'spj.edit', params: { slug: spj.slug } }"
                class="flex-1 text-xs font-medium px-3 py-2 rounded-inner border border-border hover:bg-surface-disabled text-center inline-flex items-center justify-center gap-1"
              >
                <span class="material-symbols-outlined !text-sm">edit</span>
                Edit
              </RouterLink>
              <button
                v-if="bisaDiedit(spj)"
                type="button"
                @click="konfirmasiHapus(spj)"
                class="text-xs font-medium px-3 py-2 rounded-inner border border-border text-error hover:bg-error-bg inline-flex items-center justify-center gap-1 shrink-0"
                aria-label="Hapus"
              >
                <span class="material-symbols-outlined !text-sm">delete</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>