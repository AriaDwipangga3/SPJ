<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import spjService from '@/services/spjService';
import jenisSpjService from '@/services/jenisSpjService';
import { useAuth } from '@/composables/useAuth';
import SpjTable from '@/components/SpjTable.vue';
import StatCard from '@/components/StatCard.vue';
import Pagination from '@/components/Pagination.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { downloadBlob, generateExportFilename } from '@/helpers/download';
import SkeletonStatCard from '@/components/skeleton/SkeletonStatCard.vue';
import SkeletonTable from '@/components/skeleton/SkeletonTable.vue';
import { useSpjRealtime } from '@/composables/useSpjRealtime';

import { useRoute } from 'vue-router';
const route = useRoute();

onMounted(() => {
  // Ambil search dari URL kalau ada
  if (route.query.search) {
    filters.value.search = route.query.search;
  }
  muatData();
  muatJenisSpj();
});

const { isAdmin } = useAuth();

const spjList = ref([]);
const jenisSpjOptions = ref([]);
const loading = ref(true);
const errorMsg = ref('');
const exporting = ref(false);

const filters = ref({
  search: '',
  jenis_spj_id: '',
  status: '',
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
});

const STATUS_OPTIONS = [
  { value: '', label: 'Semua Status' },
  { value: 'draft', label: 'Draft' },
  { value: 'belum_lengkap', label: 'Belum Lengkap' },
  { value: 'proses_verifikasi', label: 'Proses Verifikasi' },
  { value: 'terverifikasi', label: 'Terverifikasi' },
  { value: 'selesai', label: 'Selesai' },
];

const total = computed(() => spjList.value.length);
const belumLengkap = computed(
  () => spjList.value.filter((s) => s.status === 'belum_lengkap').length
);
const terverifikasi = computed(
  () => spjList.value.filter((s) => ['terverifikasi', 'selesai'].includes(s.status)).length
);
const prosesVerifikasi = computed(
  () => spjList.value.filter((s) => s.status === 'proses_verifikasi').length
);

const adaFilter = computed(
  () => !!(filters.value.search || filters.value.jenis_spj_id || filters.value.status)
);

function ambilItems(res) {
  if (Array.isArray(res)) return res;
  if (Array.isArray(res?.data)) return res.data;
  return [];
}

// silent = true: dipakai realtime. Muat ulang diam-diam tanpa skeleton/loading
// dan tanpa menghapus daftar kalau gagal.
async function muatData(page = 1, silent = false) {
  if (!silent) {
    loading.value = true;
    errorMsg.value = '';
  }
  try {
    const params = { page, per_page: pagination.value.per_page };
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.jenis_spj_id) params.jenis_spj_id = filters.value.jenis_spj_id;
    if (filters.value.status) params.status = filters.value.status;

    const res = await spjService.index(params);
    spjList.value = ambilItems(res);

    if (res && !Array.isArray(res)) {
      pagination.value = {
        current_page: res.current_page ?? 1,
        last_page: res.last_page ?? 1,
        total: res.total ?? spjList.value.length,
        per_page: res.per_page ?? 10,
      };
    } else {
      pagination.value = {
        current_page: 1,
        last_page: 1,
        total: spjList.value.length,
        per_page: spjList.value.length || 10,
      };
    }

    // Halaman ini baru saja kosong (mis. SPJ terakhir di halaman dihapus orang lain): mundur 1 halaman.
    if (silent && spjList.value.length === 0 && page > 1) {
      await muatData(page - 1, true);
    }
  } catch (err) {
    if (!silent) {
      errorMsg.value = 'Gagal memuat data SPJ.';
      spjList.value = [];
      pagination.value = { current_page: 1, last_page: 1, total: 0, per_page: 10 };
    }
  } finally {
    if (!silent) loading.value = false;
  }
}

// Realtime: ada SPJ ditambah/diubah/dihapus -> segarkan halaman yang sedang dibuka.
useSpjRealtime(() => muatData(pagination.value.current_page, true));

async function muatJenisSpj() {
  try {
    const res = await jenisSpjService.index();
    jenisSpjOptions.value = Array.isArray(res) ? res : (res?.data ?? []);
  } catch (err) {
    jenisSpjOptions.value = [];
  }
}

function resetFilter() {
  filters.value = { search: '', jenis_spj_id: '', status: '' };
}

function handlePageChange(newPage) {
  if (newPage === pagination.value.current_page) return;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  muatData(newPage);
}

async function handleHapus(spj) {
  try {
    await spjService.destroy(spj.slug);
    const totalAfterDelete = pagination.value.total - 1;
    const maxPage = Math.max(1, Math.ceil(totalAfterDelete / pagination.value.per_page));
    const targetPage = Math.min(pagination.value.current_page, maxPage);
    await muatData(targetPage);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus SPJ.');
  }
}

async function eksporCsv() {
  if (exporting.value) return;
  exporting.value = true;
  try {
    const params = {};
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.jenis_spj_id) params.jenis_spj_id = filters.value.jenis_spj_id;
    if (filters.value.status) params.status = filters.value.status;

    const blob = await spjService.exportCsv(params);
    await downloadBlob(blob, generateExportFilename('SPJ', 'xlsx'));
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengekspor file.');
  } finally {
    exporting.value = false;
  }
}

let debounceTimer;
watch(
  () => [filters.value.search, filters.value.jenis_spj_id, filters.value.status],
  () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => muatData(1), 300);
  }
);

</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Daftar SPJ']" />

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 gap-4">
      <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1 flex-wrap">
          <h1 class="font-display font-bold text-xl sm:text-2xl">
            {{ isAdmin ? 'Semua Berkas SPJ' : 'Daftar SPJ Saya' }}
          </h1>
          <span
            v-if="isAdmin"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface-disabled text-xs font-medium text-text-secondary"
          >
            <span class="material-symbols-outlined !text-sm">visibility</span>
            View-Only
          </span>
        </div>
        <p class="text-text-secondary text-sm">
          {{
            isAdmin
              ? 'Kelola pemantauan seluruh berkas SPJ lintas pengguna.'
              : 'Kelola berkas pertanggungjawaban yang dibuat sendiri.'
          }}
        </p>
      </div>

      <div class="flex gap-2 flex-wrap">
        <button
          @click="eksporCsv"
          :disabled="exporting || pagination.total === 0"
          class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1.5 disabled:opacity-60"
        >
          <span
            :class="[
              'material-symbols-outlined !text-base',
              exporting ? 'animate-spin' : '',
            ]"
          >
            {{ exporting ? 'progress_activity' : 'download' }}
          </span>
          <span class="hidden sm:inline">{{ exporting ? 'Menyiapkan...' : 'Ekspor (.XLSX)' }}</span>
          <span class="sm:hidden">{{ exporting ? '...' : 'Ekspor' }}</span>
        </button>

        <RouterLink
          v-if="!isAdmin"
          :to="{ name: 'spj.buat' }"
          class="flex-1 sm:flex-none text-sm font-medium px-4 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center justify-center gap-1.5"
        >
          <span class="material-symbols-outlined !text-base">add</span>
          Buat SPJ Baru
        </RouterLink>
      </div>
    </div>

        <!-- Stat cards ringkas -->
    <div v-if="loading && !adaFilter" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <SkeletonStatCard v-for="i in 4" :key="`sk-stat-${i}`" />
    </div>
    <div v-else-if="!adaFilter" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <StatCard label="Total" :value="total" icon="description" accent="default" />
      <StatCard label="Belum Lengkap" :value="belumLengkap" icon="error_outline" accent="warning" />
      <StatCard label="Proses Verifikasi" :value="prosesVerifikasi" icon="schedule" accent="info" />
      <StatCard label="Terverifikasi" :value="terverifikasi" icon="check_circle" accent="success" />
    </div>

    <!-- Filter bar -->
    <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-3 mb-4">
      <div class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1 min-w-0">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle !text-base pointer-events-none">
            search
          </span>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari nomor SPJ, pihak, instansi..."
            class="w-full h-10 pl-9 pr-3 rounded-DEFAULT border border-border bg-background text-sm
                   placeholder:text-text-subtle focus:bg-surface"
          />
        </div>

        <div class="grid grid-cols-2 sm:flex gap-2">
          <select
            v-model="filters.jenis_spj_id"
            class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm sm:min-w-[160px] min-w-0"
          >
            <option value="">Semua Jenis SPJ</option>
            <option v-for="j in jenisSpjOptions" :key="j.id" :value="j.id">{{ j.nama }}</option>
          </select>
          <select
            v-model="filters.status"
            class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm sm:min-w-[140px] min-w-0"
          >
            <option v-for="s in STATUS_OPTIONS" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>

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

    <!-- Table + pagination -->
        <SkeletonTable v-if="loading" :rows="5" />
    <p v-else-if="errorMsg" class="text-sm text-error py-8 text-center">{{ errorMsg }}</p>
    <div v-else class="border border-border rounded-DEFAULT shadow-elevation-1 bg-surface overflow-hidden">
      <SpjTable :items="spjList" :is-admin="isAdmin" no-wrapper @hapus="handleHapus" />
      <Pagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        item-label="berkas SPJ"
        @update:page="handlePageChange"
      />
    </div>

    <!-- Info panel admin -->
    <div
      v-if="isAdmin && !loading"
      class="flex items-start gap-3 mt-4 p-4 rounded-DEFAULT border border-border-soft bg-surface"
    >
      <span class="material-symbols-outlined !text-lg text-text-subtle shrink-0 mt-0.5">visibility</span>
      <div>
        <p class="text-sm font-medium mb-0.5">Mode Monitoring Administrator</p>
        <p class="text-xs text-text-secondary leading-relaxed">
          Akun Administrator memiliki hak pemantauan seluruh berkas (Read-Only).
        </p>
      </div>
    </div>
  </div>
</template>