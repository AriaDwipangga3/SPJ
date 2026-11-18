<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import activityLogService from '@/services/activityLogService';
import Breadcrumb from '@/components/Breadcrumb.vue';
import Pagination from '@/components/Pagination.vue';
import BaseModal from '@/components/BaseModal.vue';

const logs = ref([]);
const summary = ref({ total: 0, per_category: [], per_action: [], top_users: [] });
const loading = ref(true);
const errorMsg = ref('');
const toast = ref({ type: '', message: '' });

const filters = ref({
  search: '',
  category: '',
  dari_tanggal: '',
  sampai_tanggal: '',
});

const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 25 });

const detailLog = ref(null);

const CATEGORY_OPTIONS = [
  { value: '', label: 'Semua Kategori' },
  { value: 'auth', label: 'Autentikasi' },
  { value: 'spj', label: 'SPJ' },
  { value: 'master', label: 'Master Data' },
  { value: 'user', label: 'Kelola User' },
  { value: 'backup', label: 'Backup' },
  { value: 'profile', label: 'Profil' },
];

/* ============ Toast ============ */
function showToast(type, message) {
  toast.value = { type, message };
  setTimeout(() => { toast.value = { type: '', message: '' }; }, 4000);
}

/* ============ Load Data ============ */
async function muatData(page = 1) {
  loading.value = true;
  errorMsg.value = '';
  try {
    const params = { page, per_page: 25 };
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.category) params.category = filters.value.category;
    if (filters.value.dari_tanggal) params.dari_tanggal = filters.value.dari_tanggal;
    if (filters.value.sampai_tanggal) params.sampai_tanggal = filters.value.sampai_tanggal;

    const res = await activityLogService.index(params);
    logs.value = res.data ?? [];
    pagination.value = {
      current_page: res.current_page ?? 1,
      last_page: res.last_page ?? 1,
      total: res.total ?? 0,
      per_page: res.per_page ?? 25,
    };
  } catch (err) {
    errorMsg.value = 'Gagal memuat log aktivitas.';
    logs.value = [];
  } finally {
    loading.value = false;
  }
}

async function muatSummary() {
  try {
    const params = {};
    if (filters.value.dari_tanggal) params.dari_tanggal = filters.value.dari_tanggal;
    if (filters.value.sampai_tanggal) params.sampai_tanggal = filters.value.sampai_tanggal;
    summary.value = await activityLogService.summary(params);
  } catch (err) { /* silent */ }
}

function resetFilter() {
  filters.value = { search: '', category: '', dari_tanggal: '', sampai_tanggal: '' };
}

const adaFilter = computed(() =>
  !!(filters.value.search || filters.value.category || filters.value.dari_tanggal || filters.value.sampai_tanggal)
);

function handlePageChange(p) {
  if (p === pagination.value.current_page) return;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  muatData(p);
}

/* ============ Format ============ */
function formatTanggalIndo(str) {
  if (!str) return '-';
  try {
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  } catch { return str; }
}

const ACTION_COLORS = {
  success: 'bg-success-bg text-success-text border-success-border',
  warning: 'bg-warning-bg text-warning-text border-warning-border',
  error: 'bg-error-bg text-error border-error-border',
  info: 'bg-info-bg text-info-text border-info-border',
  default: 'bg-surface-disabled text-text-secondary border-border-soft',
};

function actionColor(action) {
  if (action.includes('delete')) return 'error';
  if (action.includes('reject')) return 'warning';
  if (action.includes('create') || action.includes('verify')) return 'success';
  if (action.includes('update') || action.includes('login')) return 'info';
  return 'default';
}

function actionLabel(action) {
  return action.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

/* ============ Cleanup ============ */
async function handleCleanup() {
  const days = prompt('Hapus log lebih lama dari berapa hari? (default 90)', '90');
  if (!days) return;
  const n = parseInt(days);
  if (!n || n < 1) { showToast('error', 'Jumlah hari tidak valid.'); return; }

  if (!confirm(`Hapus semua log lebih lama dari ${n} hari? Tindakan ini tidak bisa dibatalkan.`)) return;

  try {
    const res = await activityLogService.cleanup(n);
    showToast('success', res.message);
    muatData(1);
    muatSummary();
  } catch (err) {
    showToast('error', err.response?.data?.message || 'Gagal cleanup.');
  }
}

/* ============ Debounce search ============ */
let debounceTimer;
watch(
  () => [filters.value.search, filters.value.category, filters.value.dari_tanggal, filters.value.sampai_tanggal],
  () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { muatData(1); muatSummary(); }, 400);
  }
);

onMounted(() => { muatData(); muatSummary(); });
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Log Aktivitas']" />

    <!-- Toast -->
    <div
      v-if="toast.message"
      :class="[
        'fixed top-[calc(4.5rem+var(--sat))] left-4 right-4 sm:left-auto z-50 sm:max-w-sm p-3 rounded-DEFAULT border shadow-elevation-2 flex items-start gap-2 text-sm',
        toast.type === 'success'
          ? 'bg-success-bg border-success-border text-success-text'
          : 'bg-error-bg border-error-border text-error',
      ]"
    >
      <span class="material-symbols-outlined !text-base shrink-0 mt-0.5">
        {{ toast.type === 'success' ? 'check_circle' : 'error' }}
      </span>
      <p class="flex-1">{{ toast.message }}</p>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
      <div class="min-w-0">
        <h1 class="font-display font-bold text-xl sm:text-2xl mb-1">Log Aktivitas</h1>
        <p class="text-text-secondary text-sm">
          Riwayat semua aktivitas user di sistem — untuk keperluan audit.
        </p>
      </div>
      <button
        @click="handleCleanup"
        class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1.5 shrink-0"
      >
        <span class="material-symbols-outlined !text-base">delete_sweep</span>
        Bersihkan Log Lama
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
      <div class="bg-surface border border-border rounded-DEFAULT p-4">
        <p class="text-xs text-text-subtle uppercase tracking-wider mb-1">Total Log</p>
        <p class="font-display font-bold text-2xl">{{ summary.total }}</p>
      </div>
      <div class="bg-surface border border-border rounded-DEFAULT p-4">
        <p class="text-xs text-text-subtle uppercase tracking-wider mb-1">Kategori Teratas</p>
        <p class="font-medium text-sm truncate">
          {{ summary.per_category[0]?.category ?? '-' }}
          <span class="text-text-subtle">({{ summary.per_category[0]?.count ?? 0 }})</span>
        </p>
      </div>
      <div class="bg-surface border border-border rounded-DEFAULT p-4">
        <p class="text-xs text-text-subtle uppercase tracking-wider mb-1">Aksi Terbanyak</p>
        <p class="font-medium text-sm truncate">
          {{ actionLabel(summary.per_action[0]?.action ?? '-') }}
          <span class="text-text-subtle">({{ summary.per_action[0]?.count ?? 0 }})</span>
        </p>
      </div>
      <div class="bg-surface border border-border rounded-DEFAULT p-4">
        <p class="text-xs text-text-subtle uppercase tracking-wider mb-1">User Aktif</p>
        <p class="font-medium text-sm truncate">
          {{ summary.top_users[0]?.user_name ?? '-' }}
          <span class="text-text-subtle">({{ summary.top_users[0]?.count ?? 0 }})</span>
        </p>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-3 mb-4">
      <div class="flex flex-col sm:flex-row gap-2 flex-wrap">
        <div class="relative flex-1 min-w-[200px]">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle !text-base pointer-events-none">
            search
          </span>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari user, deskripsi, aksi..."
            class="w-full h-10 pl-9 pr-3 rounded-DEFAULT border border-border bg-background text-sm placeholder:text-text-subtle focus:bg-surface"
          />
        </div>
        <select v-model="filters.category" class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm min-w-[160px]">
          <option v-for="c in CATEGORY_OPTIONS" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>
        <label class="flex items-center gap-2 text-xs text-text-subtle"><span class="w-12 sm:w-auto shrink-0">Dari</span><input v-model="filters.dari_tanggal" type="date" class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm flex-1 min-w-0" /></label>
        <label class="flex items-center gap-2 text-xs text-text-subtle"><span class="w-12 sm:w-auto shrink-0">Sampai</span><input v-model="filters.sampai_tanggal" type="date" class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm flex-1 min-w-0" /></label>
        <button
          v-if="adaFilter"
          @click="resetFilter"
          class="h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm text-text-secondary hover:bg-surface-disabled inline-flex items-center gap-1.5"
        >
          <span class="material-symbols-outlined !text-base">restart_alt</span>
          Reset
        </button>
      </div>
    </div>

    <p v-if="errorMsg" class="text-sm text-error mb-4">{{ errorMsg }}</p>

    <!-- Loading -->
    <p v-if="loading" class="text-sm text-text-subtle py-12 text-center">Memuat data...</p>

    <!-- Empty -->
    <div v-else-if="logs.length === 0" class="text-center py-16 border border-dashed border-border rounded-DEFAULT bg-surface">
      <span class="material-symbols-outlined !text-4xl text-text-subtle">history</span>
      <p class="text-sm font-medium text-text-secondary mt-2">Belum ada log aktivitas</p>
    </div>

    <!-- List -->
    <div v-else class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden">
      <div class="divide-y divide-border-soft">
        <div
          v-for="log in logs"
          :key="log.id"
          @click="detailLog = log"
          class="p-4 hover:bg-surface-disabled/30 transition-colors cursor-pointer flex items-start gap-3"
        >
          <!-- Avatar -->
          <span class="w-9 h-9 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center shrink-0 select-none">
            {{ (log.user_name || '?').charAt(0).toUpperCase() }}
          </span>

          <!-- Content -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2 flex-wrap mb-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-sm truncate">{{ log.user_name ?? 'System' }}</span>
                <span
                  v-if="log.user_role"
                  class="text-[10px] font-medium px-1.5 py-0.5 rounded-inner bg-surface-disabled text-text-subtle uppercase tracking-wider"
                >
                  {{ log.user_role }}
                </span>
                <span
                  :class="[
                    'text-[10px] font-bold px-2 py-0.5 rounded-pill border uppercase tracking-wider',
                    ACTION_COLORS[actionColor(log.action)],
                  ]"
                >
                  {{ actionLabel(log.action) }}
                </span>
              </div>
              <span class="text-xs text-text-subtle shrink-0">{{ formatTanggalIndo(log.created_at) }}</span>
            </div>
            <p class="text-sm text-text-secondary line-clamp-2">{{ log.description }}</p>
            <p v-if="log.ip_address" class="text-[10px] text-text-subtle mt-1 font-mono-id">
              IP: {{ log.ip_address }}
            </p>
          </div>
        </div>
      </div>

      <Pagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        item-label="log"
        @update:page="handlePageChange"
      />
    </div>

    <!-- Modal Detail -->
    <BaseModal
      v-if="detailLog"
      title="Detail Log"
      size="md"
      @close="detailLog = null"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div>
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">User</p>
            <p class="font-medium">{{ detailLog.user_name ?? '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">Role</p>
            <p class="font-medium">{{ detailLog.user_role ?? '-' }}</p>
          </div>
          <div>
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">Aksi</p>
            <p class="font-medium">{{ actionLabel(detailLog.action) }}</p>
          </div>
          <div>
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">Kategori</p>
            <p class="font-medium">{{ detailLog.category }}</p>
          </div>
          <div class="col-span-2">
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">Waktu</p>
            <p class="font-medium">{{ formatTanggalIndo(detailLog.created_at) }}</p>
          </div>
          <div class="col-span-2">
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">Deskripsi</p>
            <p>{{ detailLog.description ?? '-' }}</p>
          </div>
          <div v-if="detailLog.ip_address" class="col-span-2">
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">IP Address</p>
            <p class="font-mono-id text-xs">{{ detailLog.ip_address }}</p>
          </div>
          <div v-if="detailLog.user_agent" class="col-span-2">
            <p class="text-xs text-text-subtle uppercase tracking-wider mb-0.5">User Agent</p>
            <p class="font-mono-id text-[11px] break-all">{{ detailLog.user_agent }}</p>
          </div>
        </div>

        <div v-if="detailLog.metadata && Object.keys(detailLog.metadata).length > 0">
          <p class="text-xs text-text-subtle uppercase tracking-wider mb-1.5">Metadata</p>
          <pre class="bg-surface-disabled p-3 rounded-DEFAULT text-xs overflow-x-auto">{{ JSON.stringify(detailLog.metadata, null, 2) }}</pre>
        </div>
      </div>
    </BaseModal>
  </div>
</template>