<script setup>
import { ref, onMounted, computed } from 'vue';
import spjService from '@/services/spjService';
import { useAuth } from '@/composables/useAuth';
import StatCard from '@/components/StatCard.vue';
import SpjTable from '@/components/SpjTable.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import SpjBarChart from '@/components/dashboard/SpjBarChart.vue';
import SpjStatusChart from '@/components/dashboard/SpjStatusChart.vue';
import SpjJenisChart from '@/components/dashboard/SpjJenisChart.vue';
import { downloadBlob, generateExportFilename } from '@/helpers/download';
import { formatRupiah, formatRupiahSingkat } from '@/helpers/format';
import SkeletonStatCard from '@/components/skeleton/SkeletonStatCard.vue';
import SkeletonTable from '@/components/skeleton/SkeletonTable.vue';
import SkeletonChart from '@/components/skeleton/SkeletonChart.vue';
import { useSpjRealtime } from '@/composables/useSpjRealtime';

const { isAdmin } = useAuth();

const spjList = ref([]);
const pagination = ref({ total: 0, current_page: 1, last_page: 1 });
const stats = ref({
  summary: {
    total_spj: 0, total_anggaran: 0,
    draft: 0, belum_lengkap: 0, proses_verifikasi: 0,
    terverifikasi: 0, selesai: 0,
  },
  by_month: [],
  by_status: [],
  by_jenis: [],
});
const loading = ref(true);
const errorMsg = ref('');
const exporting = ref(false);

const tahunAnggaran = new Date().getFullYear();

const totalSpj = computed(() => stats.value.summary.total_spj ?? 0);
const totalAnggaran = computed(() => stats.value.summary.total_anggaran ?? 0);
const belumLengkap = computed(() => stats.value.summary.belum_lengkap ?? 0);
const terverifikasi = computed(() => stats.value.summary.terverifikasi ?? 0);

// silent = true: dipakai realtime. Muat ulang diam-diam tanpa skeleton.
async function muatData(silent = false) {
  if (!silent) {
    loading.value = true;
    errorMsg.value = '';
  }
  try {
    // Fetch paralel: tabel recent + stats
    const [res, statsRes] = await Promise.all([
      spjService.index({ per_page: 5 }),
      spjService.stats(),
    ]);

    const items = Array.isArray(res) ? res : (res?.data ?? []);
    spjList.value = Array.isArray(items) ? items : [];

    if (res && !Array.isArray(res)) {
      pagination.value = {
        total: res.total ?? spjList.value.length,
        current_page: res.current_page ?? 1,
        last_page: res.last_page ?? 1,
      };
    } else {
      pagination.value = {
        total: spjList.value.length,
        current_page: 1,
        last_page: 1,
      };
    }

    stats.value = statsRes;
  } catch (err) {
    if (silent === true) return; // gagal muat ulang diam-diam: biarkan data lama tampil
    // Detail penyebab supaya mudah didiagnosis (terutama di APK)
    let detail;
    if (err.response) {
      const url = `${err.config?.baseURL ?? ''}${err.config?.url ?? ''}`;
      detail = `Server membalas ${err.response.status} untuk ${url}`;
    } else {
      detail = `Tidak bisa terhubung ke server (${err.config?.baseURL ?? 'alamat API kosong'}). Cek WiFi/hotspot, IP, dan apakah Laravel jalan dengan --host=0.0.0.0.`;
    }
    errorMsg.value = `Gagal memuat data dashboard. ${detail}`;
    spjList.value = [];
  } finally {
    if (!silent) loading.value = false;
  }
}

async function handleHapus(spj) {
  try {
    await spjService.destroy(spj.slug);
    await muatData();
  } catch (err) {
    alert('Gagal menghapus SPJ.');
  }
}

async function eksporCsv() {
  if (exporting.value) return;
  exporting.value = true;
  try {
    const blob = await spjService.exportCsv({});
    await downloadBlob(blob, generateExportFilename('SPJ', 'xlsx'));
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengekspor file.');
  } finally {
    exporting.value = false;
  }
}

onMounted(() => muatData());

// Realtime: ada SPJ ditambah/diubah/dihapus -> segarkan angka, grafik, dan tabel.
useSpjRealtime(() => muatData(true));
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', isAdmin ? 'Dashboard Admin' : 'Dashboard']" />

    <!-- Chip status -->
    <div class="flex items-center gap-2 mb-3 flex-wrap">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface-disabled text-xs font-medium text-text-secondary">
        <span class="w-1.5 h-1.5 rounded-pill bg-primary"></span>
        Hak Akses: {{ isAdmin ? 'Administrator' : 'Pengusul (User)' }}
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface-disabled text-xs font-medium text-text-secondary">
        <span class="material-symbols-outlined !text-sm">calendar_month</span>
        Tahun Anggaran: {{ tahunAnggaran }}
      </span>
    </div>

    <!-- Header -->
    <div class="flex items-start justify-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="font-display font-bold text-2xl mb-1">Dashboard</h1>
        <p class="text-text-secondary text-sm">
          Ringkasan data pertanggungjawaban keuangan.
        </p>
      </div>
      <button
        @click="eksporCsv"
        :disabled="exporting"
        class="text-sm font-medium px-3.5 py-2 rounded-DEFAULT border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1.5 disabled:opacity-60"
      >
        <span :class="['material-symbols-outlined !text-base', exporting ? 'animate-spin' : '']">
          {{ exporting ? 'progress_activity' : 'download' }}
        </span>
        {{ exporting ? 'Menyiapkan...' : 'Ekspor (.XLSX)' }}
      </button>
    </div>

        <!-- Stat cards -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <SkeletonStatCard v-for="i in 4" :key="`sk-stat-${i}`" />
    </div>
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard
        label="Total SPJ"
        :value="totalSpj"
        icon="description"
        caption="Seluruh berkas diajukan"
        accent="default"
      />
      <StatCard
        label="Total Anggaran"
        :value="formatRupiahSingkat(totalAnggaran)"
        icon="payments"
        caption="Akumulasi nilai SPJ"
        accent="info"
      />
      <StatCard
        label="Belum Lengkap"
        :value="belumLengkap"
        icon="error_outline"
        caption="Perlu perbaikan berkas"
        accent="warning"
      />
      <StatCard
        label="Terverifikasi"
        :value="terverifikasi"
        icon="check_circle"
        caption="Selesai verifikasi"
        accent="success"
      />
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
      <div class="lg:col-span-2">
        <SkeletonChart v-if="loading" />
        <SpjBarChart v-else :data="stats.by_month" />
      </div>
      <SkeletonChart v-if="loading" />
      <SpjStatusChart v-else :data="stats.by_status" />
      <SkeletonChart v-if="loading" />
      <SpjJenisChart v-else :data="stats.by_jenis" />
    </div>

    <!-- Table section -->
    <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
      <h2 class="font-display font-semibold text-lg">SPJ Terbaru</h2>
      <RouterLink
        v-if="!isAdmin"
        :to="{ name: 'spj.buat' }"
        class="text-sm font-medium px-4 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center gap-1.5"
      >
        <span class="material-symbols-outlined !text-base">add</span>
        Buat SPJ Baru
      </RouterLink>
    </div>

    <SkeletonTable v-if="loading" :rows="5" />
    <p v-else-if="errorMsg" class="text-sm text-error">{{ errorMsg }}</p>
    <SpjTable v-else :items="spjList" :is-admin="isAdmin" @hapus="handleHapus" />

    <!-- Info panel -->
    <div
      v-if="!loading"
      class="flex items-start gap-3 mt-6 p-4 rounded-DEFAULT border border-border-soft bg-surface"
    >
      <span class="material-symbols-outlined !text-lg text-text-subtle shrink-0 mt-0.5">info</span>
      <div>
        <p class="text-sm font-medium mb-0.5">Catatan Verifikasi Berkas SPJ Mandiri</p>
        <p class="text-xs text-text-secondary leading-relaxed">
          Status berkas <strong class="text-warning-text">Belum Lengkap</strong> menunjukkan
          kelengkapan bukti pembayaran, kuitansi bermeterai, atau daftar hadir perlu dilengkapi
          ulang sebelum disahkan oleh Pejabat Pembuat Komitmen (PPK).
        </p>
      </div>
    </div>
  </div>
</template>