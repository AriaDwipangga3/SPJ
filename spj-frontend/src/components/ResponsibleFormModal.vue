<script setup>
import { ref, watch } from 'vue';
import responsibleService from '@/services/responsibleService';
import BaseModal from '@/components/BaseModal.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  responsible: { type: Object, default: null },
  kategoriDefault: { type: String, default: '' },
  lockKategori: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const KATEGORI_OPTIONS = [
  { value: 'pihak1_internal', label: 'Pihak I — Internal (PPK/PPTK)' },
  { value: 'pihak2_rekanan', label: 'Pihak II — Pengusul/Rekanan' },
];

const kosong = () => ({
  nama: '',
  nip_nik: '',
  jabatan: '',
  instansi: '',
  kategori: props.kategoriDefault || 'pihak1_internal',
  rekening_bank: '',
  rekening_nomor: '',
});

const form = ref(kosong());
const saving = ref(false);
const errorMsg = ref('');

watch(
  () => [props.show, props.responsible],
  () => {
    if (!props.show) return;
    errorMsg.value = '';
    form.value = props.responsible
      ? {
          nama: props.responsible.nama ?? '',
          nip_nik: props.responsible.nip_nik ?? '',
          jabatan: props.responsible.jabatan ?? '',
          instansi: props.responsible.instansi ?? '',
          kategori: props.responsible.kategori ?? 'pihak1_internal',
          rekening_bank: props.responsible.rekening_bank ?? '',
          rekening_nomor: props.responsible.rekening_nomor ?? '',
        }
      : kosong();
  },
  { immediate: true }
);

async function handleSubmit() {
  saving.value = true;
  errorMsg.value = '';
  try {
    const payload = { ...form.value };
    const hasil = props.responsible?.id
      ? await responsibleService.update(props.responsible.id, payload)
      : await responsibleService.store(payload);

    emit('saved', hasil);
    emit('close');
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal menyimpan data Responsible.';
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <BaseModal
    v-if="show"
    :title="responsible ? 'Edit Responsible' : 'Tambah Responsible Baru'"
    size="md"
    @close="emit('close')"
  >
    <div v-if="!responsible" class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-surface-disabled border border-border-soft mb-4">
      <span class="material-symbols-outlined !text-base text-text-subtle shrink-0 mt-0.5">info</span>
      <p class="text-xs text-text-secondary leading-relaxed">
        Tambahkan data pejabat atau staf penanggung jawab kegiatan baru ke dalam sistem.
        Data ini akan langsung tersinkronisasi sebagai opsi penandatangan dokumen SPJ.
      </p>
    </div>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1.5">
          Nama Lengkap &amp; Gelar <span class="text-error">*</span>
        </label>
        <input
          v-model="form.nama"
          type="text"
          required
          placeholder="Contoh: Drs. Ahmad Fauzi, M.Si"
          class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1.5">NIP / NIK</label>
        <input
          v-model="form.nip_nik"
          type="text"
          placeholder="Contoh: 19780412 200212 1 002"
          class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm font-mono-id focus:bg-background"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1.5">
          Kategori <span class="text-error">*</span>
        </label>
        <select
          v-model="form.kategori"
          required
          :disabled="lockKategori"
          class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background disabled:bg-surface-disabled disabled:text-text-subtle"
        >
          <option v-for="k in KATEGORI_OPTIONS" :key="k.value" :value="k.value">
            {{ k.label }}
          </option>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1.5">Jabatan Kedinasan</label>
          <input
            v-model="form.jabatan"
            type="text"
            placeholder="Contoh: Pejabat Pembuat Komitmen"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">Instansi / Unit Kerja</label>
          <input
            v-model="form.instansi"
            type="text"
            placeholder="Contoh: Dinas Komunikasi dan Informatika"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1.5">Bank</label>
          <input
            v-model="form.rekening_bank"
            type="text"
            placeholder="Contoh: BNI"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">No. Rekening</label>
          <input
            v-model="form.rekening_nomor"
            type="text"
            placeholder="Contoh: 0123456789"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm font-mono-id focus:bg-background"
          />
        </div>
      </div>

      <p v-if="errorMsg" class="text-sm text-error">{{ errorMsg }}</p>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <button
          type="button"
          @click="emit('close')"
          class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled"
        >
          Batal
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="saving || !form.nama.trim()"
          class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5"
        >
          <span v-if="saving" class="material-symbols-outlined !text-base animate-spin">
            progress_activity
          </span>
          <span v-else class="material-symbols-outlined !text-base">save</span>
          {{ saving ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </template>
  </BaseModal>
</template>