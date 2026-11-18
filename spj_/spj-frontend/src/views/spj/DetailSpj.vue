<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import spjService from '@/services/spjService';
import StatusBadge from '@/components/StatusBadge.vue';
import BaseModal from '@/components/BaseModal.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { useAuth } from '@/composables/useAuth';
import { downloadBlob } from '@/helpers/download';
import { formatTanggal, formatRupiah, formatUkuranFile } from '@/helpers/format';

const props = defineProps({
  slug: { type: String, required: true },
});

const router = useRouter();
const { isAdmin } = useAuth();

const spj = ref(null);
const loading = ref(true);
const errorMsg = ref('');

const showApprove = ref(false);
const showReject = ref(false);
const catatanApprove = ref('');
const catatanReject = ref('');
const processing = ref(false);
const printing = ref(false);

async function cetakPdf() {
  if (printing.value) return;
  printing.value = true;
  try {
    const blob = await spjService.exportPdf(spj.value.slug);
    const safeNomor = (spj.value.nomor_spj || 'SPJ').replace(/\//g, '_');
    const stamp = new Date().toISOString().slice(0, 16).replace(/[-:T]/g, '');
    await downloadBlob(blob, `${safeNomor}_${stamp}.pdf`);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mencetak PDF.');
  } finally {
    printing.value = false;
  }
}

async function muatData() {
  loading.value = true;
  errorMsg.value = '';
  try {
    spj.value = await spjService.show(props.slug);
  } catch (err) {
    errorMsg.value = 'Gagal memuat data SPJ. Mungkin tidak ada akses atau data sudah dihapus.';
  } finally {
    loading.value = false;
  }
}

async function submitApprove() {
  processing.value = true;
  try {
    spj.value = await spjService.verify(spj.value.slug, catatanApprove.value || null);
    showApprove.value = false;
    catatanApprove.value = '';
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyetujui SPJ.');
  } finally {
    processing.value = false;
  }
}

async function submitReject() {
  processing.value = true;
  try {
    spj.value = await spjService.reject(spj.value.slug, catatanReject.value);
    showReject.value = false;
    catatanReject.value = '';
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menolak SPJ.');
  } finally {
    processing.value = false;
  }
}

onMounted(muatData);
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto">
    <p v-if="loading" class="text-sm text-text-subtle py-8 text-center">Memuat data...</p>
    <p v-else-if="errorMsg" class="text-sm text-error py-8 text-center">{{ errorMsg }}</p>

    <template v-else-if="spj">
      <Breadcrumb :items="['Sistem SPJ', 'Daftar SPJ', spj.nomor_spj]" />

      <!-- Header -->
      <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-3 mb-2 flex-wrap">
            <h1 class="font-display font-bold text-xl sm:text-2xl font-mono-id break-all">{{ spj.nomor_spj }}</h1>
            <StatusBadge :status="spj.status" />
          </div>
          <div class="flex items-center flex-wrap gap-3 text-sm text-text-secondary">
            <span>{{ spj.jenis_spj?.nama ?? spj.jenisSpj?.nama ?? '-' }}</span>
            <span class="text-text-subtle">·</span>
            <span>{{ formatTanggal(spj.tanggal) }}</span>
            <span class="text-text-subtle">·</span>
            <span class="inline-flex items-center gap-1.5">
              <span class="material-symbols-outlined !text-sm">person</span>
              {{ spj.pembuat?.name ?? '-' }}
            </span>
          </div>
        </div>

        <div class="flex gap-2 flex-wrap">
          <button
            @click="cetakPdf"
            :disabled="printing"
            class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1.5 disabled:opacity-60"
          >
            <span :class="['material-symbols-outlined !text-base', printing ? 'animate-spin' : '']">
              {{ printing ? 'progress_activity' : 'print' }}
            </span>
            <span class="hidden sm:inline">{{ printing ? 'Menyiapkan...' : 'Cetak PDF' }}</span>
          </button>

          <button
            @click="router.back()"
            class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT border border-border hover:bg-surface-disabled inline-flex items-center gap-1.5"
          >
            <span class="material-symbols-outlined !text-base">arrow_back</span>
            Kembali
          </button>
        </div>
      </div>

      <!-- Panel Verifikasi (admin) -->
      <div
        v-if="isAdmin && spj.status === 'proses_verifikasi'"
        class="rounded-DEFAULT border-2 border-primary/20 bg-primary/5 p-5 mb-6"
      >
        <div class="flex items-start gap-4 flex-wrap">
          <span class="w-11 h-11 rounded-pill bg-primary text-white flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined">verified</span>
          </span>
          <div class="flex-1 min-w-0">
            <h2 class="font-display font-semibold text-lg mb-1">Verifikasi SPJ</h2>
            <p class="text-sm text-text-secondary mb-4">
              Periksa seluruh dokumen & tanda tangan sebelum menyetujui.
              Keputusan bersifat final dan tercatat dalam audit log.
            </p>
            <div class="flex gap-2 flex-wrap">
              <button
                type="button"
                @click="showReject = true"
                class="px-4 py-2 rounded-DEFAULT border border-border bg-surface text-sm font-medium hover:bg-error-bg hover:border-error/30 hover:text-error inline-flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined !text-base">cancel</span>
                Tolak / Minta Revisi
              </button>
              <button
                type="button"
                @click="showApprove = true"
                class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover inline-flex items-center gap-1.5"
              >
                <span class="material-symbols-outlined !text-base">check_circle</span>
                Setujui SPJ
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Info terverifikasi -->
      <div
        v-if="spj.status === 'terverifikasi' && spj.verified_at"
        class="flex items-start gap-3 rounded-DEFAULT border border-success-border bg-success-bg p-4 mb-6"
      >
        <span class="material-symbols-outlined !text-lg text-success-text shrink-0 mt-0.5">verified</span>
        <div>
          <p class="font-medium text-success-text text-sm">
            Terverifikasi oleh {{ spj.verifier?.name ?? 'Admin' }}
          </p>
          <p class="text-xs text-success-text/80 mt-0.5">
            {{ formatTanggal(spj.verified_at) }}
            <span v-if="spj.catatan_verifikasi"> · "{{ spj.catatan_verifikasi }}"</span>
          </p>
        </div>
      </div>

      <!-- Catatan reject -->
      <div
        v-if="spj.status === 'belum_lengkap' && spj.catatan_verifikasi"
        class="flex items-start gap-3 rounded-DEFAULT border border-warning-border bg-warning-bg p-4 mb-6"
      >
        <span class="material-symbols-outlined !text-lg text-warning-text shrink-0 mt-0.5">error_outline</span>
        <div>
          <p class="font-medium text-warning-text text-sm">Catatan Verifikator</p>
          <p class="text-xs text-warning-text/80 mt-0.5 leading-relaxed">
            {{ spj.catatan_verifikasi }}
          </p>
        </div>
      </div>

      <!-- Informasi Dasar -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 mb-4">
        <div class="px-5 py-4 border-b border-border-soft flex items-center gap-2">
          <span class="material-symbols-outlined !text-lg text-text-subtle">info</span>
          <h2 class="font-display font-semibold text-base">Informasi Dasar</h2>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <dt class="text-xs font-medium text-text-subtle uppercase tracking-wider mb-1">Perihal</dt>
            <dd class="text-sm font-medium">{{ spj.perihal || '-' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-text-subtle uppercase tracking-wider mb-1">Jumlah Anggaran</dt>
            <dd class="text-sm font-medium">{{ formatRupiah(spj.jumlah_anggaran) }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-xs font-medium text-text-subtle uppercase tracking-wider mb-1">Uraian</dt>
            <dd class="text-sm whitespace-pre-wrap">{{ spj.uraian || '-' }}</dd>
          </div>
        </div>
      </div>

      <!-- Para Pihak -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 mb-4">
        <div class="px-5 py-4 border-b border-border-soft flex items-center gap-2">
          <span class="material-symbols-outlined !text-lg text-text-subtle">group</span>
          <h2 class="font-display font-semibold text-base">Para Pihak</h2>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="rounded-DEFAULT border border-border p-4">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-[10px] font-semibold px-2 py-0.5 rounded-pill bg-info-bg text-info-text uppercase tracking-wider">
                Pihak I
              </span>
            </div>
            <div v-if="spj.pihak1" class="flex items-start gap-3">
              <span class="w-10 h-10 rounded-pill bg-primary text-white text-sm font-semibold flex items-center justify-center shrink-0">
                {{ spj.pihak1.nama?.charAt(0)?.toUpperCase() }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-sm">{{ spj.pihak1.nama }}</p>
                <p class="text-xs text-text-secondary">{{ spj.pihak1.jabatan || '-' }}</p>
                <p v-if="spj.pihak1.nip_nik" class="text-xs text-text-subtle font-mono-id mt-0.5">
                  NIP: {{ spj.pihak1.nip_nik }}
                </p>
              </div>
            </div>
            <p v-else class="text-sm text-text-subtle">Belum dipilih</p>

            <div v-if="spj.ttd_pihak1_path" class="mt-4 pt-4 border-t border-border-soft">
              <p class="text-xs text-text-subtle mb-1.5">Tanda tangan:</p>
              <img :src="spj.ttd_pihak1_url"
                class="h-16 rounded-inner border border-border-soft bg-white" alt="TTD Pihak I" />
            </div>
            <div v-else class="mt-4 pt-4 border-t border-border-soft">
              <p class="text-xs text-error flex items-center gap-1">
                <span class="material-symbols-outlined !text-sm">error</span>
                Belum ada tanda tangan
              </p>
            </div>
          </div>

          <div class="rounded-DEFAULT border border-border p-4">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-[10px] font-semibold px-2 py-0.5 rounded-pill bg-warning-bg text-warning-text uppercase tracking-wider">
                Pihak II
              </span>
            </div>
            <div v-if="spj.pihak2" class="flex items-start gap-3">
              <span class="w-10 h-10 rounded-pill bg-surface-disabled text-text-secondary text-sm font-semibold flex items-center justify-center shrink-0">
                {{ spj.pihak2.nama?.charAt(0)?.toUpperCase() }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-sm">{{ spj.pihak2.nama }}</p>
                <p class="text-xs text-text-secondary">{{ spj.pihak2.instansi || '-' }}</p>
                <p v-if="spj.pihak2.nip_nik" class="text-xs text-text-subtle font-mono-id mt-0.5">
                  NIP: {{ spj.pihak2.nip_nik }}
                </p>
              </div>
            </div>
            <p v-else class="text-sm text-text-subtle">Belum dipilih</p>

            <div v-if="spj.ttd_pihak2_path" class="mt-4 pt-4 border-t border-border-soft">
              <p class="text-xs text-text-subtle mb-1.5">Tanda tangan:</p>
              <img :src="spj.ttd_pihak2_url"
                class="h-16 rounded-inner border border-border-soft bg-white" alt="TTD Pihak II" />
            </div>
            <div v-else class="mt-4 pt-4 border-t border-border-soft">
              <p class="text-xs text-error flex items-center gap-1">
                <span class="material-symbols-outlined !text-sm">error</span>
                Belum ada tanda tangan
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Dokumen -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
        <div class="px-5 py-4 border-b border-border-soft flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="material-symbols-outlined !text-lg text-text-subtle">folder</span>
            <h2 class="font-display font-semibold text-base">Dokumen Terlampir</h2>
          </div>
          <span class="text-xs text-text-subtle">
            {{ (spj.dokumen ?? []).filter(d => d.status === 'sudah').length }}
            / {{ (spj.dokumen ?? []).length }} berkas
          </span>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
          <div v-for="d in (spj.dokumen ?? [])" :key="d.id"
            class="flex items-start gap-3 p-3 rounded-DEFAULT border transition-colors"
            :class="d.status === 'sudah' ? 'border-border-soft bg-surface' : 'border-dashed border-border-soft bg-surface-disabled/30'">
            <span class="w-9 h-9 rounded-inner flex items-center justify-center shrink-0"
              :class="d.status === 'sudah' ? 'bg-success-bg text-success-text' : 'bg-surface-disabled text-text-subtle'">
              <span class="material-symbols-outlined !text-base">
                {{ d.status === 'sudah' ? 'check_circle' : 'upload_file' }}
              </span>
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium truncate">{{ d.dokumen_wajib?.nama_dokumen }}</p>
              <p class="text-xs text-text-subtle truncate mt-0.5">
                {{ d.status === 'sudah' ? `${d.file_nama_asli} · ${formatUkuranFile(d.file_size_kb)}` : 'Belum ada berkas' }}
              </p>
            </div>
            <a v-if="d.status === 'sudah'" :href="d.file_url" target="_blank" rel="noopener"
              class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled shrink-0 inline-flex items-center gap-1">
              <span class="material-symbols-outlined !text-sm">visibility</span>
              Lihat
            </a>
          </div>
        </div>
      </div>

      <!-- Modal Approve -->
      <BaseModal v-if="showApprove" title="Setujui SPJ" @close="showApprove = false">
        <div class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-success-bg border border-success-border mb-4">
          <span class="material-symbols-outlined !text-base text-success-text shrink-0 mt-0.5">check_circle</span>
          <p class="text-xs text-success-text">
            SPJ akan berstatus <strong>Terverifikasi</strong> dan tidak bisa diedit lagi.
            Keputusan ini tercatat dalam audit log.
          </p>
        </div>
        <label class="block text-sm font-medium mb-1.5">Catatan (opsional)</label>
        <textarea v-model="catatanApprove" rows="3" placeholder="Catatan verifikasi..."
          class="w-full px-3 py-2 rounded-DEFAULT border border-border text-sm resize-none"></textarea>

        <template #footer>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showApprove = false"
              class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled">
              Batal
            </button>
            <button type="button" @click="submitApprove" :disabled="processing"
              class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5">
              <span v-if="processing" class="material-symbols-outlined !text-base animate-spin">progress_activity</span>
              {{ processing ? 'Memproses...' : 'Setujui' }}
            </button>
          </div>
        </template>
      </BaseModal>

      <!-- Modal Reject -->
      <BaseModal v-if="showReject" title="Tolak / Minta Revisi" @close="showReject = false">
        <div class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-warning-bg border border-warning-border mb-4">
          <span class="material-symbols-outlined !text-base text-warning-text shrink-0 mt-0.5">error_outline</span>
          <p class="text-xs text-warning-text">
            SPJ akan dikembalikan ke pembuat dengan status
            <strong>Belum Lengkap</strong>. Catatan wajib diisi.
          </p>
        </div>
        <label class="block text-sm font-medium mb-1.5">
          Alasan / Catatan Revisi <span class="text-error">*</span>
        </label>
        <textarea v-model="catatanReject" rows="4" required placeholder="Jelaskan apa yang harus diperbaiki..."
          class="w-full px-3 py-2 rounded-DEFAULT border border-border text-sm resize-none"></textarea>

        <template #footer>
          <div class="flex justify-end gap-2">
            <button type="button" @click="showReject = false"
              class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled">
              Batal
            </button>
            <button type="button" @click="submitReject" :disabled="processing || !catatanReject.trim()"
              class="px-4 py-2 rounded-DEFAULT bg-error text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5">
              <span v-if="processing" class="material-symbols-outlined !text-base animate-spin">progress_activity</span>
              {{ processing ? 'Memproses...' : 'Tolak' }}
            </button>
          </div>
        </template>
      </BaseModal>
    </template>
  </div>
</template>