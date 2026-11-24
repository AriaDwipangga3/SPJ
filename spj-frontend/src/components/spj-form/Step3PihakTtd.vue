<script setup>
import { ref, computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import spjService from '@/services/spjService';
import responsibleService from '@/services/responsibleService';
import SignaturePad from '@/components/SignaturePad.vue';
import ResponsibleFormModal from '@/components/ResponsibleFormModal.vue';

const props = defineProps({
  spj: { type: Object, required: true },
});
const emit = defineEmits(['updated', 'error']);

const responsibleOptions = ref([]);

const pihak1Id = ref(props.spj.pihak1_id ?? '');
const pihak2Id = ref(props.spj.pihak2_id ?? '');
const metodeTtd = ref({ 1: 'gambar', 2: 'gambar' });

const modalPihakAktif = ref(null);

function urutkanUntukPihak(pihak) {
  const target = pihak === 1 ? 'pihak1_internal' : 'pihak2_rekanan';
  return [...responsibleOptions.value].sort((a, b) => {
    const aMatch = a.kategori === target ? 0 : 1;
    const bMatch = b.kategori === target ? 0 : 1;
    return aMatch - bMatch;
  });
}

const pihak1Options = computed(() =>
  urutkanUntukPihak(1).filter((r) => r.id !== pihak2Id.value)
);
const pihak2Options = computed(() =>
  urutkanUntukPihak(2).filter((r) => r.id !== pihak1Id.value)
);

const kategoriModalAktif = computed(() =>
  modalPihakAktif.value === 1 ? 'pihak1_internal' : 'pihak2_rekanan'
);

const ttdLengkap = computed(
  () => !!props.spj.ttd_pihak1_path && !!props.spj.ttd_pihak2_path
);
defineExpose({ ttdLengkap });

// Info responsible yang dipilih (buat panel identitas di bawah dropdown)
const pihak1Terpilih = computed(
  () => responsibleOptions.value.find((r) => r.id === Number(pihak1Id.value)) ?? null
);
const pihak2Terpilih = computed(
  () => responsibleOptions.value.find((r) => r.id === Number(pihak2Id.value)) ?? null
);

onMounted(async () => {
  try {
    const data = await responsibleService.index();
    responsibleOptions.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (err) {
    emit('error', 'Gagal memuat daftar Responsible.');
    responsibleOptions.value = [];
  }
});

function formatOptionLabel(r) {
  const bagian = [r.nama];
  if (r.jabatan) bagian.push(r.jabatan);
  else if (r.instansi) bagian.push(r.instansi);
  return bagian.join(' — ');
}

async function onPihakChange(pihak, val) {
  if (pihak === 1) pihak1Id.value = val;
  else pihak2Id.value = val;
  await simpanPihak();
}

async function simpanPihak() {
  try {
    const updated = await spjService.update(props.spj.slug, {
      pihak1_id: pihak1Id.value || null,
      pihak2_id: pihak2Id.value || null,
    });
    emit('updated', updated);
  } catch (err) {
    emit('error', err.response?.data?.message || 'Gagal menyimpan Pihak I/II.');
  }
}

function bukaModalTambah(pihak) {
  modalPihakAktif.value = pihak;
}

async function handleResponsibleTersimpan(baru) {
  responsibleOptions.value.push(baru);
  if (modalPihakAktif.value === 1) pihak1Id.value = baru.id;
  else pihak2Id.value = baru.id;
  modalPihakAktif.value = null;
  await simpanPihak();
}

async function simpanTtdGambar(pihak, base64) {
  try {
    const updated = await spjService.simpanTandaTanganGambar(
      props.spj.slug,
      String(pihak),
      base64
    );
    emit('updated', updated);
  } catch (err) {
    emit('error', 'Gagal menyimpan tanda tangan.');
  }
}

async function simpanTtdUpload(pihak, e) {
  const file = e.target.files?.[0];
  if (!file) return;
  try {
    const updated = await spjService.simpanTandaTanganUpload(
      props.spj.slug,
      String(pihak),
      file
    );
    emit('updated', updated);
  } catch (err) {
    emit('error', 'Gagal upload tanda tangan.');
  }
}
</script>

<template>
  <div>
    <!-- Header -->
    <div class="p-4 sm:p-6 border-b border-border-soft">
      <h2 class="font-display font-semibold text-lg">3. Pihak & Tanda Tangan</h2>
      <p class="text-sm text-text-secondary mt-0.5">
        Pilih pejabat penandatangan untuk Pihak I dan Pihak II, lalu bubuhkan tanda tangan digital.
      </p>
    </div>

    <div class="p-4 sm:p-6 grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div
        v-for="pihak in [1, 2]"
        :key="pihak"
        class="rounded-DEFAULT border border-border bg-surface overflow-hidden"
      >
        <!-- Card header -->
        <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between bg-surface-disabled/30">
          <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center">
              {{ pihak }}
            </span>
            <p class="text-sm font-semibold">
              Pihak {{ pihak === 1 ? 'I' : 'II' }}
            </p>
          </div>
          <span
            class="text-[10px] font-medium px-2 py-0.5 rounded-pill border"
            :class="
              (pihak === 1 ? spj.ttd_pihak1_path : spj.ttd_pihak2_path)
                ? 'bg-success-bg text-success-text border-success-border'
                : 'bg-surface-disabled text-text-subtle border-border-soft'
            "
          >
            {{ (pihak === 1 ? spj.ttd_pihak1_path : spj.ttd_pihak2_path) ? 'Sudah TTD' : 'Belum TTD' }}
          </span>
        </div>

        <div class="p-4 space-y-4">
          <!-- Dropdown + Tambah -->
          <div>
            <label class="block text-xs font-medium text-text-secondary mb-1.5">
              Pilih Penanggung Jawab
            </label>
            <div class="flex gap-2">
              <select
                :value="pihak === 1 ? pihak1Id : pihak2Id"
                @change="onPihakChange(pihak, $event.target.value)"
                class="flex-1 h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
              >
                <option value="">— Pilih Responsible —</option>
                <option
                  v-for="r in (pihak === 1 ? pihak1Options : pihak2Options)"
                  :key="r.id"
                  :value="r.id"
                >
                  {{ formatOptionLabel(r) }}
                </option>
              </select>
              <button
                type="button"
                @click="bukaModalTambah(pihak)"
                class="h-10 px-3 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center gap-1 text-xs font-medium shrink-0"
                title="Tambah Responsible Baru"
              >
                <span class="material-symbols-outlined !text-base">add</span>
                Baru
              </button>
            </div>
          </div>

          <!-- Panel identitas responsible terpilih -->
          <div
            v-if="(pihak === 1 ? pihak1Terpilih : pihak2Terpilih)"
            class="rounded-DEFAULT border border-border-soft bg-surface-disabled/30 p-3 space-y-1.5"
          >
            <div class="flex items-center gap-2 mb-1">
              <span class="w-8 h-8 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center shrink-0">
                {{ (pihak === 1 ? pihak1Terpilih : pihak2Terpilih).nama?.charAt(0)?.toUpperCase() }}
              </span>
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">
                  {{ (pihak === 1 ? pihak1Terpilih : pihak2Terpilih).nama }}
                </p>
                <p class="text-xs text-text-subtle truncate">
                  {{ (pihak === 1 ? pihak1Terpilih : pihak2Terpilih).jabatan || '-' }}
                </p>
              </div>
            </div>
            <div
              v-if="(pihak === 1 ? pihak1Terpilih : pihak2Terpilih).nip_nik"
              class="text-xs text-text-secondary flex items-center gap-1.5"
            >
              <span class="material-symbols-outlined !text-sm">badge</span>
              NIP/NIK: {{ (pihak === 1 ? pihak1Terpilih : pihak2Terpilih).nip_nik }}
            </div>
            <div
              v-if="(pihak === 1 ? pihak1Terpilih : pihak2Terpilih).instansi"
              class="text-xs text-text-secondary flex items-center gap-1.5"
            >
              <span class="material-symbols-outlined !text-sm">domain</span>
              {{ (pihak === 1 ? pihak1Terpilih : pihak2Terpilih).instansi }}
            </div>
          </div>

          <!-- Metode TTD -->
          <div>
            <p class="text-xs font-medium text-text-secondary mb-1.5">Metode Pembubuhan TTD</p>
            <div class="inline-flex rounded-DEFAULT border border-border p-0.5 bg-surface-disabled/40 w-full">
              <button
                type="button"
                class="flex-1 text-xs font-medium py-1.5 rounded-inner transition-colors inline-flex items-center justify-center gap-1"
                :class="
                  metodeTtd[pihak] === 'gambar'
                    ? 'bg-surface text-text-primary shadow-elevation-1'
                    : 'text-text-secondary hover:text-text-primary'
                "
                @click="metodeTtd[pihak] = 'gambar'"
              >
                <span class="material-symbols-outlined !text-sm">draw</span>
                Gambar
              </button>
              <button
                type="button"
                class="flex-1 text-xs font-medium py-1.5 rounded-inner transition-colors inline-flex items-center justify-center gap-1"
                :class="
                  metodeTtd[pihak] === 'upload'
                    ? 'bg-surface text-text-primary shadow-elevation-1'
                    : 'text-text-secondary hover:text-text-primary'
                "
                @click="metodeTtd[pihak] = 'upload'"
              >
                <span class="material-symbols-outlined !text-sm">upload_file</span>
                Upload
              </button>
            </div>
          </div>

          <!-- Canvas / Upload -->
          <SignaturePad
            v-if="metodeTtd[pihak] === 'gambar'"
            :key="`pad-${pihak}`"
            @simpan="(b64) => simpanTtdGambar(pihak, b64)"
          />
          <div v-else>
            <label
              class="flex flex-col items-center justify-center w-full h-32 rounded-DEFAULT border-2 border-dashed border-border hover:border-primary hover:bg-surface-disabled/30 cursor-pointer text-center px-4"
            >
              <span class="material-symbols-outlined !text-2xl text-text-subtle mb-1">upload</span>
              <span class="text-xs font-medium">Pilih file tanda tangan</span>
              <span class="text-[10px] text-text-subtle mt-0.5">PNG / JPG, latar putih, maks 2 MB</span>
              <input
                type="file"
                accept="image/png,image/jpeg"
                class="hidden"
                @change="simpanTtdUpload(pihak, $event)"
              />
            </label>
          </div>

          <!-- Preview TTD tersimpan -->
          <div
            v-if="pihak === 1 ? spj.ttd_pihak1_path : spj.ttd_pihak2_path"
            class="pt-3 border-t border-border-soft"
          >
            <p class="text-xs text-text-subtle mb-1.5">Spesimen tersimpan:</p>
            <img
              :src="pihak === 1 ? spj.ttd_pihak1_url : spj.ttd_pihak2_url"
              class="h-16 rounded-inner border border-border-soft bg-white"
              :alt="`Tanda tangan Pihak ${pihak === 1 ? 'I' : 'II'}`"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Footer link -->
    <div class="px-4 sm:px-6 py-4 border-t border-border-soft">
      <RouterLink
        to="/responsible"
        class="text-xs text-text-secondary hover:text-text-primary hover:underline inline-flex items-center gap-1"
      >
        <span class="material-symbols-outlined !text-sm">open_in_new</span>
        Kelola daftar Responsible
      </RouterLink>
    </div>

    <ResponsibleFormModal
      :show="modalPihakAktif !== null"
      :kategori-default="kategoriModalAktif"
      :lock-kategori="true"
      @close="modalPihakAktif = null"
      @saved="handleResponsibleTersimpan"
    />
  </div>
</template>