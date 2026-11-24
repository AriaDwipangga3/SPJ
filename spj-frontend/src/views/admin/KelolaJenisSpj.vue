<script setup>
import { ref, computed, onMounted } from 'vue';
import jenisSpjService from '@/services/jenisSpjService';
import dokumenWajibService from '@/services/dokumenWajibService';
import BaseModal from '@/components/BaseModal.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';

const jenisList = ref([]);
const selected = ref(null);
const dokumenWajibList = ref([]);
const loading = ref(true);
const errorMsg = ref('');
const savingDokumen = ref(false);

// Modal jenis
const showJenisModal = ref(false);
const editingJenis = ref(null);
const formJenis = ref({ kode: '', nama: '', mak: '', keterangan: '' });
const savingJenis = ref(false);

// Modal dokumen
const showDokumenModal = ref(false);
const editingDokumen = ref(null);

/* ============ Opsi untuk Form Dokumen ============ */
const FORMAT_OPTIONS = [
  { value: 'pdf', label: 'PDF' },
  { value: 'jpg', label: 'JPG' },
  { value: 'jpeg', label: 'JPEG' },
  { value: 'png', label: 'PNG' },
];

const UKURAN_OPTIONS = [
  { value: 1, label: '1 MB' },
  { value: 2, label: '2 MB' },
  { value: 3, label: '3 MB' },
  { value: 5, label: '5 MB' },
  { value: 10, label: '10 MB' },
  { value: 20, label: '20 MB' },
];

const formDokumen = ref({
  nama_dokumen: '',
  keterangan: '',
  wajib: true,
  max_size_mb: 5,
  formatArray: ['pdf', 'jpg', 'jpeg', 'png'], // checkbox state
});

/* ============ Load Data ============ */
async function muatJenis() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const data = await jenisSpjService.index();
    jenisList.value = Array.isArray(data) ? data : (data?.data ?? []);
    if (!selected.value && jenisList.value.length > 0) {
      await pilihJenis(jenisList.value[0]);
    }
  } catch (err) {
    errorMsg.value = 'Gagal memuat jenis SPJ.';
  } finally {
    loading.value = false;
  }
}

async function pilihJenis(jenis) {
  selected.value = jenis;
  try {
    const data = await dokumenWajibService.index(jenis.id);
    dokumenWajibList.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (err) {
    dokumenWajibList.value = [];
  }
}

/* ============ Jenis SPJ ============ */
function bukaTambahJenis() {
  editingJenis.value = null;
  formJenis.value = { kode: '', nama: '', mak: '', keterangan: '' };
  showJenisModal.value = true;
}

function bukaEditJenis(j) {
  editingJenis.value = j;
  formJenis.value = {
    kode: j.kode ?? '',
    nama: j.nama ?? '',
    mak: j.mak ?? '',
    keterangan: j.keterangan ?? '',
  };
  showJenisModal.value = true;
}

async function simpanJenis() {
  savingJenis.value = true;
  errorMsg.value = '';
  try {
    if (editingJenis.value?.id) {
      const updated = await jenisSpjService.update(editingJenis.value.id, formJenis.value);
      const idx = jenisList.value.findIndex((j) => j.id === editingJenis.value.id);
      if (idx !== -1) jenisList.value[idx] = updated;
      if (selected.value?.id === editingJenis.value.id) selected.value = updated;
    } else {
      const baru = await jenisSpjService.store(formJenis.value);
      jenisList.value.push(baru);
      await pilihJenis(baru);
    }
    showJenisModal.value = false;
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Gagal menyimpan jenis SPJ. Cek kode, mungkin sudah dipakai.';
  } finally {
    savingJenis.value = false;
  }
}

async function hapusJenis(jenis) {
  if (!confirm(`Hapus jenis SPJ "${jenis.nama}"? Semua dokumen wajibnya ikut terhapus.`))
    return;
  try {
    await jenisSpjService.destroy(jenis.id);
    jenisList.value = jenisList.value.filter((j) => j.id !== jenis.id);
    if (selected.value?.id === jenis.id) {
      selected.value = null;
      dokumenWajibList.value = [];
      if (jenisList.value.length > 0) await pilihJenis(jenisList.value[0]);
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus jenis SPJ.');
  }
}

/* ============ Dokumen Wajib ============ */
function bukaTambahDokumen() {
  editingDokumen.value = null;
  formDokumen.value = {
    nama_dokumen: '',
    keterangan: '',
    wajib: true,
    max_size_mb: 5,
    formatArray: ['pdf', 'jpg', 'jpeg', 'png'],
  };
  showDokumenModal.value = true;
}

function bukaEditDokumen(d) {
  editingDokumen.value = d;
  // Parse format string → array
  const formatArr = d.format
    ? String(d.format).split(',').map((s) => s.trim().toLowerCase()).filter(Boolean)
    : [];
  formDokumen.value = {
    nama_dokumen: d.nama_dokumen ?? '',
    keterangan: d.keterangan ?? '',
    wajib: d.wajib ?? true,
    max_size_mb: d.max_size_mb ?? 5,
    formatArray: formatArr.length > 0 ? formatArr : ['pdf'],
  };
  showDokumenModal.value = true;
}

async function simpanDokumen() {
  savingDokumen.value = true;
  errorMsg.value = '';

  // Validasi: minimal 1 format dipilih
  if (formDokumen.value.formatArray.length === 0) {
    errorMsg.value = 'Pilih minimal 1 format dokumen.';
    savingDokumen.value = false;
    return;
  }

  try {
    // Convert formatArray → string comma-separated
    const payload = {
      nama_dokumen: formDokumen.value.nama_dokumen,
      keterangan: formDokumen.value.keterangan,
      wajib: formDokumen.value.wajib,
      max_size_mb: formDokumen.value.max_size_mb,
      format: formDokumen.value.formatArray.join(','),
    };

    if (editingDokumen.value?.id) {
      const updated = await dokumenWajibService.update(editingDokumen.value.id, payload);
      const idx = dokumenWajibList.value.findIndex((d) => d.id === editingDokumen.value.id);
      if (idx !== -1) dokumenWajibList.value[idx] = updated;
    } else {
      const baru = await dokumenWajibService.store(selected.value.id, payload);
      dokumenWajibList.value.push(baru);
    }
    showDokumenModal.value = false;
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal menyimpan dokumen wajib.';
  } finally {
    savingDokumen.value = false;
  }
}

async function hapusDokumen(dok) {
  if (!confirm(`Hapus dokumen wajib "${dok.nama_dokumen}"?`)) return;
  try {
    await dokumenWajibService.destroy(dok.id);
    dokumenWajibList.value = dokumenWajibList.value.filter((d) => d.id !== dok.id);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus dokumen.');
  }
}

/* ============ Computed ============ */
const totalWajib = computed(
  () => dokumenWajibList.value.filter((d) => d.wajib).length
);
const totalOpsional = computed(
  () => dokumenWajibList.value.filter((d) => !d.wajib).length
);

onMounted(muatJenis);
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Jenis SPJ']" />

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
      <div>
        <h1 class="font-display font-bold text-2xl mb-1">Jenis SPJ & Dokumen Wajib</h1>
        <p class="text-text-secondary text-sm">
          Konfigurasi klasifikasi pengajuan SPJ dan penetapan daftar dokumen wajib.
        </p>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="errorMsg"
      class="flex items-start gap-2 p-3 rounded-DEFAULT border border-error/20 bg-error-bg text-error text-sm mb-4"
    >
      <span class="material-symbols-outlined !text-base shrink-0 mt-0.5">error</span>
      <p>{{ errorMsg }}</p>
    </div>

    <p v-if="loading" class="text-sm text-text-subtle py-8 text-center">Memuat data...</p>

    <!-- Two-column layout -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-4">
      <!-- ============ LEFT: LIST JENIS ============ -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden">
        <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between bg-surface-disabled/30">
          <p class="text-xs font-semibold text-text-subtle uppercase tracking-wider">
            Daftar Jenis SPJ
          </p>
          <span class="text-xs text-text-subtle">Total · {{ jenisList.length }}</span>
        </div>

        <div class="p-2 space-y-1 max-h-[600px] overflow-y-auto">
          <div
            v-for="j in jenisList"
            :key="j.id"
            @click="pilihJenis(j)"
            class="group p-3 rounded-DEFAULT cursor-pointer border transition-colors"
            :class="
              selected?.id === j.id
                ? 'border-primary bg-surface-disabled'
                : 'border-transparent hover:bg-surface-disabled/60'
            "
          >
            <div class="flex items-start justify-between gap-2 mb-1">
              <p class="text-sm font-medium leading-tight truncate">{{ j.nama }}</p>
              <div class="flex gap-1 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity shrink-0">
                <button
                  @click.stop="bukaEditJenis(j)"
                  class="w-6 h-6 rounded-inner flex items-center justify-center hover:bg-surface text-text-secondary"
                  title="Edit"
                >
                  <span class="material-symbols-outlined !text-sm">edit</span>
                </button>
                <button
                  @click.stop="hapusJenis(j)"
                  class="w-6 h-6 rounded-inner flex items-center justify-center hover:bg-error-bg text-error"
                  title="Hapus"
                >
                  <span class="material-symbols-outlined !text-sm">delete</span>
                </button>
              </div>
            </div>
            <p class="text-xs text-text-subtle">
              <span class="font-mono-id">{{ j.kode }}</span>
              <span> · {{ j.dokumen_wajib_count ?? 0 }} dokumen</span>
            </p>
          </div>

          <button
            @click="bukaTambahJenis"
            class="w-full text-sm font-medium px-3 py-2.5 rounded-DEFAULT border border-dashed border-border hover:bg-surface-disabled inline-flex items-center justify-center gap-1.5 text-text-secondary"
          >
            <span class="material-symbols-outlined !text-base">add</span>
            Tambah Jenis SPJ
          </button>
        </div>
      </div>

      <!-- ============ RIGHT: DOKUMEN WAJIB ============ -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden">
        <template v-if="selected">
          <div class="px-5 py-4 border-b border-border-soft">
            <div class="flex items-start justify-between gap-3 flex-wrap">
              <div class="min-w-0">
                <h2 class="font-display font-semibold text-lg truncate">
                  {{ selected.nama }}
                </h2>
                <div class="flex items-center gap-2 mt-1 flex-wrap text-xs">
                  <span class="font-mono-id px-1.5 py-0.5 rounded-inner bg-surface-disabled text-text-secondary">
                    {{ selected.kode }}
                  </span>
                  <span v-if="selected.mak" class="text-text-subtle">
                    MAK: {{ selected.mak }}
                  </span>
                </div>
              </div>
              <button
                @click="bukaTambahDokumen"
                class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center gap-1.5 shrink-0"
              >
                <span class="material-symbols-outlined !text-base">add</span>
                Tambah Dokumen
              </button>
            </div>

            <div class="flex items-center gap-3 mt-3 text-xs text-text-secondary">
              <span class="inline-flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-pill bg-error"></span>
                Wajib: <strong class="text-text-primary">{{ totalWajib }}</strong>
              </span>
              <span class="inline-flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-pill bg-text-subtle"></span>
                Opsional: <strong class="text-text-primary">{{ totalOpsional }}</strong>
              </span>
            </div>
          </div>

          <div class="p-4 space-y-2">
            <div
              v-for="d in dokumenWajibList"
              :key="d.id"
              class="flex items-start gap-3 p-3 rounded-DEFAULT border border-border-soft hover:bg-surface-disabled/30 transition-colors group"
            >
              <span
                class="w-9 h-9 rounded-inner flex items-center justify-center shrink-0"
                :class="
                  d.wajib
                    ? 'bg-error/10 text-error'
                    : 'bg-surface-disabled text-text-secondary'
                "
              >
                <span class="material-symbols-outlined !text-base">
                  {{ d.wajib ? 'assignment' : 'draft' }}
                </span>
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                  <p class="text-sm font-medium">{{ d.nama_dokumen }}</p>
                  <span
                    :class="[
                      'text-[10px] font-medium px-1.5 py-0.5 rounded-inner uppercase tracking-wider',
                      d.wajib
                        ? 'bg-error/10 text-error'
                        : 'bg-surface-disabled text-text-subtle',
                    ]"
                  >
                    {{ d.wajib ? 'Wajib' : 'Opsional' }}
                  </span>
                </div>
                <p v-if="d.keterangan" class="text-xs text-text-secondary mb-1">
                  {{ d.keterangan }}
                </p>
                <div class="flex items-center gap-3 text-[11px] text-text-subtle">
                  <span v-if="d.format" class="flex items-center gap-1">
                    <span class="material-symbols-outlined !text-xs">description</span>
                    {{ d.format }}
                  </span>
                  <span v-if="d.max_size_mb" class="flex items-center gap-1">
                    <span class="material-symbols-outlined !text-xs">database</span>
                    Maks. {{ d.max_size_mb }} MB
                  </span>
                </div>
              </div>

              <div class="flex gap-1 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity shrink-0">
                <button
                  @click="bukaEditDokumen(d)"
                  class="w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface text-text-secondary"
                  title="Edit"
                >
                  <span class="material-symbols-outlined !text-sm">edit</span>
                </button>
                <button
                  @click="hapusDokumen(d)"
                  class="w-7 h-7 rounded-inner flex items-center justify-center hover:bg-error-bg text-error"
                  title="Hapus"
                >
                  <span class="material-symbols-outlined !text-sm">delete</span>
                </button>
              </div>
            </div>

            <div
              v-if="dokumenWajibList.length === 0"
              class="text-center py-10 border border-dashed border-border rounded-DEFAULT"
            >
              <span class="material-symbols-outlined !text-3xl text-text-subtle">folder_open</span>
              <p class="text-sm font-medium text-text-secondary mt-2">
                Belum ada dokumen wajib
              </p>
              <p class="text-xs text-text-subtle mt-0.5">
                Klik "Tambah Dokumen" untuk menambahkan.
              </p>
            </div>
          </div>

          <div class="px-5 py-3 border-t border-border-soft bg-surface-disabled/30">
            <p class="text-xs text-text-subtle">
              Perubahan daftar dokumen berlaku pada pengajuan SPJ baru. SPJ yang sudah diajukan tidak terpengaruh.
            </p>
          </div>
        </template>

        <div v-else class="p-12 text-center">
          <span class="material-symbols-outlined !text-4xl text-text-subtle">touch_app</span>
          <p class="text-sm font-medium text-text-secondary mt-2">
            Pilih jenis SPJ di sebelah kiri
          </p>
        </div>
      </div>
    </div>

    <!-- ============ MODAL JENIS ============ -->
    <BaseModal
      v-if="showJenisModal"
      :title="editingJenis ? 'Edit Jenis SPJ' : 'Tambah Jenis SPJ Baru'"
      @close="showJenisModal = false"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1.5">
            Kode <span class="text-error">*</span>
          </label>
          <input
            v-model="formJenis.kode"
            placeholder="Contoh: SPJ-06 atau BOP-01"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm font-mono-id"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">
            Nama Jenis SPJ <span class="text-error">*</span>
          </label>
          <input
            v-model="formJenis.nama"
            placeholder="Contoh: Belanja Operasional & Pemeliharaan"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">
            MAK — Mata Anggaran Kegiatan (opsional)
          </label>
          <input
            v-model="formJenis.mak"
            placeholder="Contoh: 5.2.2.01.01"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm font-mono-id"
          />
          <p class="text-xs text-text-subtle mt-1">
            Kode klasifikasi anggaran. Kosongkan kalau belum ada.
          </p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">Keterangan</label>
          <textarea
            v-model="formJenis.keterangan"
            rows="2"
            placeholder="Deskripsi singkat (opsional)"
            class="w-full px-3 py-2 rounded-DEFAULT border border-border text-sm resize-none"
          ></textarea>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            @click="showJenisModal = false"
            class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled"
          >
            Batal
          </button>
          <button
            @click="simpanJenis"
            :disabled="savingJenis || !formJenis.kode.trim() || !formJenis.nama.trim()"
            class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5"
          >
            <span v-if="savingJenis" class="material-symbols-outlined !text-base animate-spin">
              progress_activity
            </span>
            {{ savingJenis ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- ============ MODAL DOKUMEN ============ -->
    <BaseModal
      v-if="showDokumenModal"
      :title="editingDokumen ? 'Edit Dokumen Wajib' : 'Tambah Dokumen Wajib'"
      size="md"
      @close="showDokumenModal = false"
    >
      <div class="space-y-5">
        <!-- Nama -->
        <div>
          <label class="block text-sm font-medium mb-1.5">
            Nama Dokumen <span class="text-error">*</span>
          </label>
          <input
            v-model="formDokumen.nama_dokumen"
            placeholder="Contoh: Kuitansi / Bukti Pembayaran Rili"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          />
        </div>

        <!-- Keterangan -->
        <div>
          <label class="block text-sm font-medium mb-1.5">Keterangan</label>
          <textarea
            v-model="formDokumen.keterangan"
            rows="2"
            placeholder="Penjelasan singkat tentang dokumen ini"
            class="w-full px-3 py-2 rounded-DEFAULT border border-border text-sm resize-none"
          ></textarea>
        </div>

        <!-- Format Dokumen — CHECKBOX -->
        <div>
          <label class="block text-sm font-medium mb-2">
            Format yang Diizinkan <span class="text-error">*</span>
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <label
              v-for="f in FORMAT_OPTIONS"
              :key="f.value"
              :class="[
                'flex items-center gap-2 p-2.5 rounded-DEFAULT border cursor-pointer transition-colors select-none',
                formDokumen.formatArray.includes(f.value)
                  ? 'border-primary bg-primary/5'
                  : 'border-border hover:bg-surface-disabled',
              ]"
            >
              <input
                type="checkbox"
                :value="f.value"
                v-model="formDokumen.formatArray"
                class="w-4 h-4 rounded-inner border-border accent-primary"
              />
              <span class="text-sm font-medium">{{ f.label }}</span>
            </label>
          </div>
          <p class="text-xs text-text-subtle mt-1.5">
            Pilih minimal 1 format. Bisa lebih dari satu.
          </p>
        </div>

        <!-- Maks Ukuran — DROPDOWN -->
        <div>
          <label class="block text-sm font-medium mb-1.5">
            Ukuran Maksimal <span class="text-error">*</span>
          </label>
          <select
            v-model.number="formDokumen.max_size_mb"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm bg-surface"
          >
            <option v-for="u in UKURAN_OPTIONS" :key="u.value" :value="u.value">
              {{ u.label }}
            </option>
          </select>
        </div>

        <!-- Wajib checkbox -->
        <label class="flex items-start gap-2.5 p-3 rounded-DEFAULT border border-border-soft cursor-pointer hover:bg-surface-disabled/30">
          <input
            type="checkbox"
            v-model="formDokumen.wajib"
            class="mt-0.5 w-4 h-4 rounded-inner border-border accent-primary"
          />
          <div>
            <p class="text-sm font-medium">Wajib diupload</p>
            <p class="text-xs text-text-subtle mt-0.5">
              Kalau dicentang, user harus upload dokumen ini sebelum bisa lanjut ke langkah berikutnya.
            </p>
          </div>
        </label>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            @click="showDokumenModal = false"
            class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled"
          >
            Batal
          </button>
          <button
            @click="simpanDokumen"
            :disabled="savingDokumen || !formDokumen.nama_dokumen.trim() || formDokumen.formatArray.length === 0"
            class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5"
          >
            <span v-if="savingDokumen" class="material-symbols-outlined !text-base animate-spin">
              progress_activity
            </span>
            {{ savingDokumen ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>