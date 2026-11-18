<script setup>
import { computed, ref } from 'vue';
import spjService from '@/services/spjService';
import { formatUkuranFile } from '@/helpers/format';
import { isMobileDevice } from '@/helpers/platform';

const props = defineProps({
  spj: { type: Object, required: true },
});
const emit = defineEmits(['updated', 'error']);

const uploadingId = ref(null);
const tampilkanTombolKamera = isMobileDevice();

const dokumenWajibList = computed(
  () => (props.spj.dokumen ?? []).filter((d) => d.dokumen_wajib?.wajib)
);

const dokumenOpsionalList = computed(
  () => (props.spj.dokumen ?? []).filter((d) => !d.dokumen_wajib?.wajib)
);

const dokumenTerupload = computed(
  () => (props.spj.dokumen ?? []).filter((d) => d.status === 'sudah').length
);

const dokumenWajibTotal = computed(() => dokumenWajibList.value.length);

const dokumenWajibTerupload = computed(
  () => dokumenWajibList.value.filter((d) => d.status === 'sudah').length
);

const progressPersen = computed(() => {
  if (dokumenWajibTotal.value === 0) return 0;
  return Math.round((dokumenWajibTerupload.value / dokumenWajibTotal.value) * 100);
});

const semuaWajibTerupload = computed(() => {
  return (
    dokumenWajibTotal.value > 0 &&
    dokumenWajibList.value.every((d) => d.status === 'sudah')
  );
});

defineExpose({ semuaWajibTerupload });

async function upload(dokumen, file, sumber) {
  if (!file) return;
  uploadingId.value = dokumen.id;
  try {
    const updated = await spjService.uploadDokumen(
      props.spj.slug,
      dokumen.id,
      file,
      sumber
    );
    const dokumenBaru = props.spj.dokumen.map((d) =>
      d.id === dokumen.id ? { ...d, ...updated } : d
    );
    emit('updated', { ...props.spj, dokumen: dokumenBaru });
  } catch (err) {
    emit(
      'error',
      err.response?.data?.message ||
        'Gagal upload dokumen. Cek ukuran atau format file.'
    );
  } finally {
    uploadingId.value = null;
  }
}

function handleFileInput(e, dokumen, sumber) {
  const file = e.target.files?.[0];
  upload(dokumen, file, sumber);
  e.target.value = '';
}
</script>

<template>
  <div>
    <!-- ============ HEADER + PROGRESS ============ -->
    <div class="p-4 sm:p-6 border-b border-border-soft">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h2 class="font-display font-semibold text-lg">2. Upload Dokumen Wajib</h2>
          <p class="text-sm text-text-secondary mt-0.5">
            Unggah seluruh bukti pengeluaran dan lampiran pendukung.
          </p>
        </div>
        <div class="text-right">
          <p class="text-sm font-semibold">
            {{ dokumenWajibTerupload }} / {{ dokumenWajibTotal }}
          </p>
          <p class="text-xs text-text-subtle">dokumen wajib</p>
        </div>
      </div>

      <!-- Progress bar -->
      <div class="mt-4">
        <div class="flex items-center justify-between text-xs text-text-secondary mb-1.5">
          <span>Progress kelengkapan</span>
          <span class="font-medium">{{ progressPersen }}%</span>
        </div>
        <div class="w-full h-1.5 rounded-pill bg-surface-disabled overflow-hidden">
          <div
            class="h-full rounded-pill transition-all duration-300"
            :class="progressPersen === 100 ? 'bg-success-text' : 'bg-primary'"
            :style="{ width: `${progressPersen}%` }"
          />
        </div>
      </div>
    </div>

    <!-- ============ DOKUMEN WAJIB ============ -->
    <div class="p-4 sm:p-6 space-y-3">
      <div
        v-for="d in dokumenWajibList"
        :key="d.id"
        class="rounded-DEFAULT border transition-all"
        :class="
          d.status === 'sudah'
            ? 'border-success-border/60 bg-success-bg/40'
            : 'border-border bg-surface'
        "
      >
        <div class="p-4">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
            <div class="flex items-start gap-3 min-w-0 flex-1">
              <span
                class="w-9 h-9 rounded-inner flex items-center justify-center shrink-0 mt-0.5"
                :class="
                  d.status === 'sudah'
                    ? 'bg-success-bg text-success-text'
                    : 'bg-surface-disabled text-text-secondary'
                "
              >
                <span class="material-symbols-outlined !text-lg">
                  {{ d.status === 'sudah' ? 'check_circle' : 'upload_file' }}
                </span>
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                  <p class="text-sm font-medium">
                    {{ d.dokumen_wajib?.nama_dokumen }}
                  </p>
                  <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-inner bg-error/10 text-error uppercase tracking-wider">
                    Wajib
                  </span>
                </div>
                <p class="text-xs text-text-subtle">
                  Format PDF / JPG, maks.
                  {{ d.dokumen_wajib?.max_size_mb ?? 10 }} MB
                </p>

                <div
                  v-if="d.status === 'sudah'"
                  class="mt-2 inline-flex items-center gap-2 px-2.5 py-1.5 rounded-inner bg-surface border border-border-soft max-w-full"
                >
                  <span class="material-symbols-outlined !text-sm text-text-subtle shrink-0">description</span>
                  <span class="text-xs font-medium truncate max-w-[140px] sm:max-w-[200px]">
                    {{ d.file_nama_asli }}
                  </span>
                  <span class="text-xs text-text-subtle shrink-0">
                    · {{ formatUkuranFile(d.file_size_kb) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap pl-12 sm:pl-0 sm:shrink-0">
              <a
                v-if="d.status === 'sudah'"
                :href="d.file_url"
                target="_blank"
                rel="noopener"
                class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface inline-flex items-center gap-1"
              >
                <span class="material-symbols-outlined !text-sm">visibility</span>
                Lihat
              </a>

              <label
                class="text-xs font-medium px-3 py-1.5 rounded-inner cursor-pointer inline-flex items-center gap-1 transition-colors"
                :class="
                  d.status === 'sudah'
                    ? 'border border-border hover:bg-surface text-text-secondary'
                    : 'bg-primary text-white hover:bg-primary-hover'
                "
              >
                <span
                  v-if="uploadingId === d.id"
                  class="material-symbols-outlined !text-sm animate-spin"
                >
                  progress_activity
                </span>
                <span v-else class="material-symbols-outlined !text-sm">
                  {{ d.status === 'sudah' ? 'swap_horiz' : 'upload' }}
                </span>
                {{ uploadingId === d.id ? 'Mengunggah...' : d.status === 'sudah' ? 'Ganti' : 'Pilih File' }}
                <input
                  type="file"
                  accept=".pdf,.jpg,.jpeg,.png"
                  class="hidden"
                  @change="handleFileInput($event, d, 'upload')"
                />
              </label>

              <label
                v-if="d.status !== 'sudah' && tampilkanTombolKamera"
                class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface cursor-pointer inline-flex items-center gap-1"
                title="Ambil foto langsung dari kamera"
              >
                <span class="material-symbols-outlined !text-sm">photo_camera</span>
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  class="hidden"
                  @change="handleFileInput($event, d, 'kamera')"
                />
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state kalau tidak ada dokumen wajib -->
      <div
        v-if="dokumenWajibList.length === 0"
        class="text-center py-8 text-sm text-text-subtle"
      >
        <span class="material-symbols-outlined !text-3xl mb-2">folder_off</span>
        <p>Belum ada dokumen wajib yang dikonfigurasi untuk jenis SPJ ini.</p>
      </div>

      <!-- ============ DOKUMEN OPSIONAL ============ -->
      <div
        v-if="dokumenOpsionalList.length > 0"
        class="pt-4 mt-4 border-t border-border-soft"
      >
        <div class="flex items-center gap-2 mb-3">
          <span class="material-symbols-outlined !text-lg text-text-subtle">add_circle</span>
          <p class="text-sm font-semibold text-text-secondary">
            Dokumen Tambahan (Opsional)
          </p>
          <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-inner bg-surface-disabled text-text-subtle uppercase tracking-wider">
            Tidak Wajib
          </span>
        </div>

        <div class="space-y-3">
          <div
            v-for="d in dokumenOpsionalList"
            :key="d.id"
            class="rounded-DEFAULT border transition-all"
            :class="
              d.status === 'sudah'
                ? 'border-success-border/60 bg-success-bg/40'
                : 'border-dashed border-border bg-surface'
            "
          >
            <div class="p-4">
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                <div class="flex items-start gap-3 min-w-0 flex-1">
                  <span
                    class="w-9 h-9 rounded-inner flex items-center justify-center shrink-0 mt-0.5"
                    :class="
                      d.status === 'sudah'
                        ? 'bg-success-bg text-success-text'
                        : 'bg-surface-disabled text-text-subtle'
                    "
                  >
                    <span class="material-symbols-outlined !text-lg">
                      {{ d.status === 'sudah' ? 'check_circle' : 'add_circle' }}
                    </span>
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                      <p class="text-sm font-medium">
                        {{ d.dokumen_wajib?.nama_dokumen }}
                      </p>
                      <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-inner bg-surface-disabled text-text-subtle uppercase tracking-wider">
                        Opsional
                      </span>
                    </div>
                    <p class="text-xs text-text-subtle">
                      Format PDF / JPG, maks.
                      {{ d.dokumen_wajib?.max_size_mb ?? 10 }} MB
                    </p>

                    <div
                      v-if="d.status === 'sudah'"
                      class="mt-2 inline-flex items-center gap-2 px-2.5 py-1.5 rounded-inner bg-surface border border-border-soft max-w-full"
                    >
                      <span class="material-symbols-outlined !text-sm text-text-subtle shrink-0">description</span>
                      <span class="text-xs font-medium truncate max-w-[140px] sm:max-w-[200px]">
                        {{ d.file_nama_asli }}
                      </span>
                      <span class="text-xs text-text-subtle shrink-0">
                        · {{ formatUkuranFile(d.file_size_kb) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap pl-12 sm:pl-0 sm:shrink-0">
                  <a
                    v-if="d.status === 'sudah'"
                    :href="d.file_url"
                    target="_blank"
                    rel="noopener"
                    class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface inline-flex items-center gap-1"
                  >
                    <span class="material-symbols-outlined !text-sm">visibility</span>
                    Lihat
                  </a>

                  <label
                    class="text-xs font-medium px-3 py-1.5 rounded-inner cursor-pointer inline-flex items-center gap-1 transition-colors"
                    :class="
                      d.status === 'sudah'
                        ? 'border border-border hover:bg-surface text-text-secondary'
                        : 'border border-border hover:bg-surface-disabled text-text-secondary'
                    "
                  >
                    <span
                      v-if="uploadingId === d.id"
                      class="material-symbols-outlined !text-sm animate-spin"
                    >
                      progress_activity
                    </span>
                    <span v-else class="material-symbols-outlined !text-sm">
                      {{ d.status === 'sudah' ? 'swap_horiz' : 'upload' }}
                    </span>
                    {{ uploadingId === d.id ? 'Mengunggah...' : d.status === 'sudah' ? 'Ganti' : 'Pilih File' }}
                    <input
                      type="file"
                      accept=".pdf,.jpg,.jpeg,.png"
                      class="hidden"
                      @change="handleFileInput($event, d, 'upload')"
                    />
                  </label>

                  <label
                    v-if="d.status !== 'sudah' && tampilkanTombolKamera"
                    class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface cursor-pointer inline-flex items-center gap-1"
                    title="Ambil foto langsung dari kamera"
                  >
                    <span class="material-symbols-outlined !text-sm">photo_camera</span>
                    <input
                      type="file"
                      accept="image/*"
                      capture="environment"
                      class="hidden"
                      @change="handleFileInput($event, d, 'kamera')"
                    />
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============ INFO BOX ============ -->
      <div
        v-if="dokumenWajibList.length > 0"
        class="flex items-start gap-2 p-3 rounded-DEFAULT bg-info-bg border border-info-border text-xs text-info-text mt-4"
      >
        <span class="material-symbols-outlined !text-base shrink-0 mt-0.5">info</span>
        <p>
          Semua dokumen <strong>wajib</strong> harus terupload sebelum bisa lanjut ke langkah
          Pihak &amp; TTD. Dokumen <strong>opsional</strong> boleh dikosongkan.
          Gunakan <strong>"Ambil Foto"</strong> untuk dokumentasi langsung dari kamera perangkat.
        </p>
      </div>
    </div>
  </div>
</template>