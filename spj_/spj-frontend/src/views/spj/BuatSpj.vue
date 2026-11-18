<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import spjService from '@/services/spjService';
import jenisSpjService from '@/services/jenisSpjService';
import StepIndicator from '@/components/StepIndicator.vue';
import Step2Dokumen from '@/components/spj-form/Step2Dokumen.vue';
import Step3PihakTtd from '@/components/spj-form/Step3PihakTtd.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { formatRibuan, parseRibuan, terbilang } from '@/helpers/number';

const formatTerbilang = (val) => {
  const num = parseInt(val, 10);
  if (!num) return '';
  return terbilang(num);
};

const STEP_LABELS = ['Info Dasar', 'Dokumen', 'Pihak & TTD', 'Review'];
const router = useRouter();

const step = ref(1);
const saving = ref(false);
const errorMsg = ref('');
const spj = ref(null);

const step2Ref = ref(null);
const step3Ref = ref(null);

const jenisSpjOptions = ref([]);
const form = ref({
  tanggal: new Date().toISOString().slice(0, 10),
  jenis_spj_id: '',
  perihal: '',
  uraian: '',
  jumlah_anggaran: '',
});

const nomorPreview = ref('');
const nomorPreviewGagal = ref(false);

async function muatNomorOtomatis() {
  nomorPreviewGagal.value = false;
  try {
    const preview = await spjService.nextNumber();
    nomorPreview.value = preview?.nomor_spj ?? '';
    if (!nomorPreview.value) nomorPreviewGagal.value = true;
  } catch (err) {
    nomorPreviewGagal.value = true;
  }
}

const jumlahAnggaranDisplay = computed({
  get() {
    return formatRibuan(form.value.jumlah_anggaran);
  },
  set(val) {
    form.value.jumlah_anggaran = parseRibuan(val);
  },
});

async function submitStep1() {
  errorMsg.value = '';
  saving.value = true;
  try {
    spj.value = await spjService.store({ ...form.value });
    step.value = 2;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Gagal menyimpan info dasar. Cek kembali data yang diisi.';
  } finally {
    saving.value = false;
  }
}

function lanjutKe(n) {
  step.value = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function handleUpdated(spjBaru) {
  if (spj.value && spjBaru) {
    spj.value = { ...spj.value, ...spjBaru };
  } else {
    spj.value = spjBaru;
  }
  if (spjBaru?.slug) {
    try {
      spj.value = await spjService.show(spjBaru.slug);
    } catch (err) {
      console.warn('Gagal refetch SPJ setelah update:', err);
    }
  }
}

function handleError(msg) {
  errorMsg.value = msg;
}

const dokumenSudahUpload = computed(
  () => (spj.value?.dokumen ?? []).filter((d) => d.status === 'sudah').length
);
const dokumenWajibTotal = computed(
  () => (spj.value?.dokumen ?? []).filter((d) => d.dokumen_wajib?.wajib).length
);
const ttdLengkapLabel = computed(() => {
  const s = spj.value;
  return s?.ttd_pihak1_path && s?.ttd_pihak2_path ? 'Lengkap (2/2)' : 'Belum lengkap';
});

function formatTanggalIndo(str) {
  if (!str) return '-';
  try {
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch {
    return str;
  }
}

function formatRupiah(val) {
  if (!val && val !== 0) return '-';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
}

async function selesai() {
  try {
    if (spj.value?.slug) {
      spj.value = await spjService.show(spj.value.slug);
    }
  } catch (err) {
    console.warn('Refetch sebelum selesai gagal:', err);
  }
  router.push({ name: 'spj.index' });
}

onMounted(() => {
  muatNomorOtomatis();
  jenisSpjService
    .index()
    .then((data) => {
      jenisSpjOptions.value = Array.isArray(data) ? data : (data?.data ?? []);
    })
    .catch(() => {
      jenisSpjOptions.value = [];
    });
});
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Daftar SPJ', 'Buat SPJ Baru']" />

    <div class="mb-6">
      <h1 class="font-display font-bold text-2xl mb-1">Buat Pengajuan SPJ Baru</h1>
      <p class="text-text-secondary text-sm">
        Lengkapi parameter data, berkas dokumen wajib, serta tanda tangan digital para pihak.
      </p>
    </div>

    <StepIndicator :steps="STEP_LABELS" :current="step" />

    <div v-if="errorMsg"
      class="flex items-start gap-2 p-3 rounded-DEFAULT border border-error/20 bg-error-bg text-error text-sm mb-4">
      <span class="material-symbols-outlined !text-base shrink-0 mt-0.5">error</span>
      <p>{{ errorMsg }}</p>
    </div>

    <form v-if="step === 1" @submit.prevent="submitStep1"
      class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
      <div class="p-4 sm:p-6 border-b border-border-soft">
        <h2 class="font-display font-semibold text-lg">1. Info Dasar</h2>
        <p class="text-sm text-text-secondary mt-0.5">
          Isi identitas kegiatan dan klasifikasi anggaran.
        </p>
      </div>

      <div class="p-4 sm:p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1.5">Nomor SPJ</label>
            <input :value="nomorPreviewGagal
                ? 'Akan dibuat otomatis saat disimpan'
                : nomorPreview || 'Memuat...'
              " disabled
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface-disabled text-sm text-text-subtle font-mono-id cursor-not-allowed" />
            <p class="text-xs text-text-subtle mt-1.5 flex items-center gap-1">
              <span class="material-symbols-outlined !text-sm">lock</span>
              Nomor dibuat otomatis oleh sistem, tidak bisa diubah manual.
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">
              Tanggal <span class="text-error">*</span>
            </label>
            <input v-model="form.tanggal" type="date" required
              class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">
            Jenis SPJ <span class="text-error">*</span>
          </label>
          <select v-model="form.jenis_spj_id" required
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background">
            <option value="" disabled>Pilih jenis SPJ</option>
            <option v-for="j in jenisSpjOptions" :key="j.id" :value="j.id">
              {{ j.nama }} <template v-if="j.kode">({{ j.kode }})</template>
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">Perihal / Nama Kegiatan</label>
          <input v-model="form.perihal" placeholder="Contoh: Perjalanan Dinas Dalam Kota — Rapat Koordinasi"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">Uraian</label>
          <textarea v-model="form.uraian" rows="3" placeholder="Jelaskan ringkas maksud dan cakupan kegiatan..."
            class="w-full px-3 py-2 rounded-DEFAULT border border-border text-sm focus:bg-background resize-none"></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">Jumlah Anggaran (Rp)</label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-text-subtle pointer-events-none">Rp</span>
            <input v-model="jumlahAnggaranDisplay" type="text" inputmode="numeric" placeholder="0"
              class="w-full h-10 pl-10 pr-3 rounded-DEFAULT border border-border text-sm focus:bg-background" />
          </div>
          <p v-if="form.jumlah_anggaran" class="text-xs text-text-subtle mt-1.5">
            Terbilang: {{ formatTerbilang(form.jumlah_anggaran) }} Rupiah
          </p>
        </div>
      </div>

      <div class="px-4 sm:px-6 py-4 border-t border-border-soft bg-surface-disabled/30 flex flex-col sm:flex-row sm:justify-end gap-2">
        <button :disabled="saving" type="submit"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-60 inline-flex items-center gap-1.5">
          <span v-if="saving" class="material-symbols-outlined !text-base animate-spin">progress_activity</span>
          {{ saving ? 'Menyimpan...' : 'Lanjut ke Dokumen' }}
          <span v-if="!saving" class="material-symbols-outlined !text-base">arrow_forward</span>
        </button>
      </div>
    </form>

    <div v-else-if="step === 2" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
      <Step2Dokumen ref="step2Ref" :spj="spj" @updated="handleUpdated" @error="handleError" />
      <div class="px-4 sm:px-6 py-4 border-t border-border-soft bg-surface-disabled/30 flex flex-col-reverse sm:flex-row sm:justify-between gap-2">
        <button @click="lanjutKe(1)"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface inline-flex items-center gap-1.5">
          <span class="material-symbols-outlined !text-base">arrow_back</span>
          Kembali
        </button>
        <button :disabled="!step2Ref?.semuaWajibTerupload" @click="lanjutKe(3)"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-40 inline-flex items-center gap-1.5">
          Lanjut ke Pihak & TTD
          <span class="material-symbols-outlined !text-base">arrow_forward</span>
        </button>
      </div>
    </div>

    <div v-else-if="step === 3" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
      <Step3PihakTtd ref="step3Ref" :spj="spj" @updated="handleUpdated" @error="handleError" />
      <div class="px-4 sm:px-6 py-4 border-t border-border-soft bg-surface-disabled/30 flex flex-col-reverse sm:flex-row sm:justify-between gap-2">
        <button @click="lanjutKe(2)"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface inline-flex items-center gap-1.5">
          <span class="material-symbols-outlined !text-base">arrow_back</span>
          Kembali
        </button>
        <button :disabled="!step3Ref?.ttdLengkap" @click="lanjutKe(4)"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-40 inline-flex items-center gap-1.5">
          Review & Validasi
          <span class="material-symbols-outlined !text-base">arrow_forward</span>
        </button>
      </div>
    </div>

    <div v-else-if="step === 4" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
      <div class="p-4 sm:p-6 border-b border-border-soft">
        <h2 class="font-display font-semibold text-lg">4. Review & Simpan</h2>
        <p class="text-sm text-text-secondary mt-0.5">
          Periksa kembali seluruh kelengkapan sebelum finalisasi pengajuan.
        </p>
      </div>

      <div v-if="spj" class="p-4 sm:p-6 space-y-5">
        <div>
          <p class="text-xs font-medium text-text-subtle uppercase tracking-wider mb-3">
            Ringkasan Parameter SPJ
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="p-3 rounded-DEFAULT border border-border-soft bg-surface-disabled/30">
              <p class="text-xs text-text-subtle mb-0.5">Nomor Register</p>
              <p class="font-medium font-mono-id text-sm">{{ spj.nomor_spj ?? '-' }}</p>
            </div>
            <div class="p-3 rounded-DEFAULT border border-border-soft bg-surface-disabled/30">
              <p class="text-xs text-text-subtle mb-0.5">Tanggal Pengajuan</p>
              <p class="font-medium text-sm">{{ formatTanggalIndo(spj.tanggal) }}</p>
            </div>
            <div class="p-3 rounded-DEFAULT border border-border-soft bg-surface-disabled/30">
              <p class="text-xs text-text-subtle mb-0.5">Jenis SPJ</p>
              <p class="font-medium text-sm">{{ spj.jenis_spj?.nama ?? '-' }}</p>
            </div>
            <div class="p-3 rounded-DEFAULT border border-border-soft bg-surface-disabled/30">
              <p class="text-xs text-text-subtle mb-0.5">Jumlah Anggaran</p>
              <p class="font-medium text-sm">{{ formatRupiah(spj.jumlah_anggaran) }}</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="flex items-center gap-3 p-3 rounded-DEFAULT border" :class="dokumenSudahUpload === dokumenWajibTotal && dokumenWajibTotal > 0
              ? 'border-success-border bg-success-bg'
              : 'border-warning-border bg-warning-bg'
            ">
            <span class="material-symbols-outlined !text-lg shrink-0" :class="dokumenSudahUpload === dokumenWajibTotal && dokumenWajibTotal > 0
                ? 'text-success-text'
                : 'text-warning-text'
              ">
              {{ dokumenSudahUpload === dokumenWajibTotal && dokumenWajibTotal > 0 ? 'check_circle' : 'error_outline' }}
            </span>
            <div class="min-w-0">
              <p class="text-sm font-medium">Dokumen</p>
              <p class="text-xs text-text-secondary">
                {{ dokumenSudahUpload }} dari {{ dokumenWajibTotal }} berkas wajib terupload
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-DEFAULT border" :class="spj.ttd_pihak1_path && spj.ttd_pihak2_path
              ? 'border-success-border bg-success-bg'
              : 'border-warning-border bg-warning-bg'
            ">
            <span class="material-symbols-outlined !text-lg shrink-0" :class="spj.ttd_pihak1_path && spj.ttd_pihak2_path
                ? 'text-success-text'
                : 'text-warning-text'
              ">
              {{ spj.ttd_pihak1_path && spj.ttd_pihak2_path ? 'check_circle' : 'error_outline' }}
            </span>
            <div class="min-w-0">
              <p class="text-sm font-medium">Tanda Tangan</p>
              <p class="text-xs text-text-secondary">{{ ttdLengkapLabel }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="px-4 sm:px-6 py-4 border-t border-border-soft bg-surface-disabled/30 flex flex-col-reverse sm:flex-row sm:justify-between gap-2">
        <button @click="lanjutKe(3)"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface inline-flex items-center gap-1.5">
          <span class="material-symbols-outlined !text-base">arrow_back</span>
          Kembali
        </button>
        <button @click="selesai"
          class="w-full sm:w-auto justify-center px-5 py-2.5 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover inline-flex items-center gap-1.5">
          <span class="material-symbols-outlined !text-base">save</span>
          Simpan SPJ
        </button>
      </div>
    </div>
  </div>
</template>