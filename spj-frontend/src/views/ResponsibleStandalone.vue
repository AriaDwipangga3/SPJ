<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import echo from '@/lib/echo';
import responsibleService from '@/services/responsibleService';
import ResponsibleFormModal from '@/components/ResponsibleFormModal.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { useAuth } from '@/composables/useAuth';
import SkeletonCardList from '@/components/skeleton/SkeletonCardList.vue';


const { isAdmin } = useAuth();

const list = ref([]);
const loading = ref(true);
const errorMsg = ref('');

const search = ref('');
const filterKategori = ref('');

const showModal = ref(false);
const editingItem = ref(null);

async function muatData() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const data = await responsibleService.index();
    list.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (err) {
    errorMsg.value = 'Gagal memuat daftar Responsible.';
    list.value = [];
  } finally {
    loading.value = false;
  }
}

const filteredList = computed(() => {
  let out = list.value;
  if (filterKategori.value) {
    out = out.filter((r) => r.kategori === filterKategori.value);
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    out = out.filter(
      (r) =>
        r.nama?.toLowerCase().includes(q) ||
        r.jabatan?.toLowerCase().includes(q) ||
        r.instansi?.toLowerCase().includes(q) ||
        r.nip_nik?.toLowerCase().includes(q)
    );
  }
  return out;
});

const totalPihak1 = computed(
  () => list.value.filter((r) => r.kategori === 'pihak1_internal').length
);
const totalPihak2 = computed(
  () => list.value.filter((r) => r.kategori === 'pihak2_rekanan').length
);

const adaFilter = computed(() => !!(search.value || filterKategori.value));

function bukaTambah() {
  editingItem.value = null;
  showModal.value = true;
}

function bukaEdit(item) {
  editingItem.value = item;
  showModal.value = true;
}

async function handleSaved(hasil) {
  if (editingItem.value?.id) {
    const idx = list.value.findIndex((r) => r.id === editingItem.value.id);
    if (idx !== -1) list.value[idx] = hasil;
  } else {
    list.value.push(hasil);
  }
  showModal.value = false;
  editingItem.value = null;
}

async function hapus(item) {
  if (!confirm(`Hapus "${item.nama}" dari daftar Responsible?`)) return;
  try {
    await responsibleService.destroy(item.id);
    list.value = list.value.filter((r) => r.id !== item.id);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus Responsible.');
  }
}

function resetFilter() {
  search.value = '';
  filterKategori.value = '';
}

function inisial(nama) {
  if (!nama) return '?';
  return nama
    .split(' ')
    .slice(0, 2)
    .map((s) => s.charAt(0).toUpperCase())
    .join('');
}

function kategoriLabel(k) {
  return k === 'pihak1_internal' ? 'Pihak I' : k === 'pihak2_rekanan' ? 'Pihak II' : '-';
}

function kategoriBadgeClass(k) {
  return k === 'pihak1_internal'
    ? 'bg-info-bg text-info-text border-info-border'
    : 'bg-warning-bg text-warning-text border-warning-border';
}

function kategoriFullLabel(k) {
  return k === 'pihak1_internal'
    ? 'Internal — PPK/PPTK'
    : k === 'pihak2_rekanan'
    ? 'Rekanan / Pengusul'
    : '';
}


// ============ REAL-TIME LISTENER ============
let echoChannel = null;

onMounted(() => {
  muatData();

  echoChannel = echo.channel('responsible-updates');
  echoChannel.listen('.ResponsibleChanged', () => {
    muatData();
  });
});

onUnmounted(() => {
  if (echoChannel) echo.leave('responsible-updates');
});
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Kelola Responsible']" />

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
      <div class="min-w-0">
        <h1 class="font-display font-bold text-xl sm:text-2xl mb-1">Kelola Responsible</h1>
        <p class="text-text-secondary text-sm">
          Master data Pihak I & Pihak II — sinkron otomatis ke formulir SPJ.
        </p>
      </div>
      <button
        @click="bukaTambah"
        class="text-sm font-medium px-4 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center justify-center gap-1.5 shrink-0"
      >
        <span class="material-symbols-outlined !text-base">person_add</span>
        Tambah Responsible
      </button>
    </div>

    <!-- Info panel -->
    <div class="flex items-start gap-3 p-3.5 rounded-DEFAULT bg-info-bg border border-info-border mb-4">
      <span class="material-symbols-outlined !text-lg text-info-text shrink-0 mt-0.5">sync</span>
      <div class="min-w-0">
        <p class="text-sm font-medium text-info-text">Sinkronisasi Otomatis</p>
        <p class="text-xs text-info-text/80 mt-0.5 leading-relaxed">
          Data pejabat dan penanggung jawab yang dikelola di halaman ini secara instan terhubung
          ke dropdown pilihan <strong>Pihak I</strong> dan <strong>Pihak II</strong> pada formulir
          SPJ Langkah 3.
        </p>
      </div>
    </div>

    <!-- Chips statistik -->
    <div class="flex items-center gap-2 mb-4 flex-wrap">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface border border-border text-xs font-medium text-text-secondary">
        <span class="w-1.5 h-1.5 rounded-pill bg-primary"></span>
        Total: {{ list.length }}
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-info-bg text-info-text border border-info-border text-xs font-medium">
        <span class="material-symbols-outlined !text-sm">shield_person</span>
        Pihak I: {{ totalPihak1 }}
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-warning-bg text-warning-text border border-warning-border text-xs font-medium">
        <span class="material-symbols-outlined !text-sm">handshake</span>
        Pihak II: {{ totalPihak2 }}
      </span>
    </div>

    <!-- Filter bar -->
    <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-3 mb-4">
      <div class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1 min-w-0">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle !text-base pointer-events-none">
            search
          </span>
          <input
            v-model="search"
            type="text"
            placeholder="Cari nama, NIP, jabatan, instansi..."
            class="w-full h-10 pl-9 pr-3 rounded-DEFAULT border border-border bg-background text-sm
                   placeholder:text-text-subtle focus:bg-surface"
          />
        </div>

        <div class="flex gap-2">
          <select
            v-model="filterKategori"
            class="flex-1 sm:flex-none h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm sm:min-w-[180px]"
          >
            <option value="">Semua Kategori</option>
            <option value="pihak1_internal">Pihak I (Internal)</option>
            <option value="pihak2_rekanan">Pihak II (Rekanan)</option>
          </select>

          <button
            v-if="adaFilter"
            @click="resetFilter"
            class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm text-text-secondary hover:bg-surface-disabled inline-flex items-center justify-center gap-1.5 shrink-0"
            title="Reset filter"
          >
            <span class="material-symbols-outlined !text-base">restart_alt</span>
            <span class="hidden sm:inline">Reset</span>
          </button>
        </div>
      </div>
    </div>

    <p v-if="errorMsg" class="text-sm text-error mb-4">{{ errorMsg }}</p>

        <SkeletonCardList v-if="loading" :items="4" :columns="2" />

    <!-- Empty state -->
    <div
      v-else-if="filteredList.length === 0"
      class="text-center py-16 border border-dashed border-border rounded-DEFAULT bg-surface"
    >
      <div class="flex flex-col items-center gap-3 max-w-xs mx-auto">
        <span class="w-14 h-14 rounded-pill bg-surface-disabled flex items-center justify-center">
          <span class="material-symbols-outlined !text-3xl text-text-subtle">
            {{ adaFilter ? 'search_off' : 'contacts' }}
          </span>
        </span>
        <p class="text-sm font-medium text-text-secondary">
          {{ adaFilter ? 'Tidak ada hasil' : 'Belum ada Responsible' }}
        </p>
        <p class="text-xs text-text-subtle leading-relaxed">
          {{
            adaFilter
              ? 'Coba ubah kata kunci atau filter kategori.'
              : 'Klik "Tambah Responsible" untuk memulai master data pejabat penandatangan SPJ.'
          }}
        </p>
      </div>
    </div>

    <!-- ============ CARD GRID ============ -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3 items-start">
      <div
        v-for="r in filteredList"
        :key="r.id"
        class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 hover:shadow-elevation-2 transition-shadow overflow-hidden"
      >
        <div class="p-4 flex items-start gap-3">
          <span
            :class="[
              'w-11 h-11 rounded-pill text-white text-sm font-semibold flex items-center justify-center shrink-0',
              r.kategori === 'pihak1_internal' ? 'bg-primary' : 'bg-secondary',
            ]"
          >
            {{ inisial(r.nama) }}
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2 mb-1">
              <p class="font-semibold text-sm leading-tight truncate">{{ r.nama }}</p>
              <span
                :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-pill border shrink-0 whitespace-nowrap uppercase tracking-wider',
                  kategoriBadgeClass(r.kategori),
                ]"
              >
                {{ kategoriLabel(r.kategori) }}
              </span>
            </div>
            <p class="text-xs text-text-secondary truncate">
              {{ r.jabatan || 'Jabatan belum diisi' }}
            </p>
          </div>
        </div>

        <div class="px-4 pb-3 grid grid-cols-1 gap-1.5 text-xs border-t border-border-soft pt-3">
          <div v-if="r.nip_nik" class="flex items-center gap-2 text-text-secondary">
            <span class="material-symbols-outlined !text-sm text-text-subtle shrink-0">badge</span>
            <span class="font-mono-id text-[11px] truncate">{{ r.nip_nik }}</span>
          </div>
          <div v-if="r.instansi" class="flex items-start gap-2 text-text-secondary">
            <span class="material-symbols-outlined !text-sm text-text-subtle shrink-0 mt-0.5">domain</span>
            <span class="leading-snug line-clamp-2">{{ r.instansi }}</span>
          </div>
          <div v-if="r.rekening_bank || r.rekening_nomor" class="flex items-start gap-2 text-text-secondary">
            <span class="material-symbols-outlined !text-sm text-text-subtle shrink-0 mt-0.5">account_balance</span>
            <span class="truncate">
              <template v-if="r.rekening_bank">{{ r.rekening_bank }}</template>
              <template v-if="r.rekening_bank && r.rekening_nomor"> · </template>
              <template v-if="r.rekening_nomor">{{ r.rekening_nomor }}</template>
            </span>
          </div>
          <div class="flex items-center gap-2 text-text-subtle">
            <span class="material-symbols-outlined !text-sm shrink-0">info</span>
            <span class="text-[11px] italic">{{ kategoriFullLabel(r.kategori) }}</span>
          </div>
        </div>

        <div class="px-4 py-2.5 border-t border-border-soft bg-surface-disabled/30 flex justify-end gap-2">
          <button
            @click="bukaEdit(r)"
            class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1"
          >
            <span class="material-symbols-outlined !text-sm">edit</span>
            Edit
          </button>
          <button
            @click="hapus(r)"
            class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border bg-surface text-error hover:bg-error-bg inline-flex items-center gap-1"
          >
            <span class="material-symbols-outlined !text-sm">delete</span>
            Hapus
          </button>
        </div>
      </div>
    </div>

    <ResponsibleFormModal
      :show="showModal"
      :responsible="editingItem"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>