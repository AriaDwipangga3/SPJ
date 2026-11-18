<script setup>
import { ref, computed, onMounted } from 'vue';
import backupService from '@/services/backupService';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { downloadBlob } from '@/helpers/download';
import SkeletonCardList from '@/components/skeleton/SkeletonCardList.vue';

const backups = ref([]);
const stats = ref({ total_count: 0, total_size_formatted: '0 KB' });
const loading = ref(true);
const running = ref(false);
const errorMsg = ref('');
const toast = ref({ type: '', message: '' });

const settings = ref({
  backup_enabled: true,
  backup_frequency: 'daily',
  backup_time: '02:00',
  backup_day: 1,
  next_run_label: null,
});
const savingSettings = ref(false);
const loadingSettings = ref(true);

const FREQUENCY_OPTIONS = [
  { value: 'daily', label: 'Setiap Hari' },
  { value: 'every_6_hours', label: 'Setiap 6 Jam' },
  { value: 'every_12_hours', label: 'Setiap 12 Jam' },
  { value: 'weekly', label: 'Setiap Minggu' },
];

const DAY_OPTIONS = [
  { value: 1, label: 'Senin' }, { value: 2, label: 'Selasa' }, { value: 3, label: 'Rabu' },
  { value: 4, label: 'Kamis' }, { value: 5, label: 'Jumat' }, { value: 6, label: 'Sabtu' },
  { value: 7, label: 'Minggu' },
];

const showTimePicker = computed(() => ['daily', 'weekly'].includes(settings.value.backup_frequency));
const showDayPicker = computed(() => settings.value.backup_frequency === 'weekly');

async function muatData() {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await backupService.index();
    backups.value = res.backups ?? [];
    stats.value = {
      total_count: res.total_count ?? 0,
      total_size_formatted: res.total_size_formatted ?? '0 KB',
    };
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal memuat daftar backup.';
  } finally {
    loading.value = false;
  }
}

async function muatSettings() {
  loadingSettings.value = true;
  try {
    const res = await backupService.getSettings();
    settings.value = res;
  } catch (err) {
    /* silent */
  } finally {
    loadingSettings.value = false;
  }
}

function showToast(type, message) {
  toast.value = { type, message };
  setTimeout(() => { toast.value = { type: '', message: '' }; }, 4000);
}

async function simpanSettings() {
  if (savingSettings.value) return;
  savingSettings.value = true;
  try {
    const res = await backupService.updateSettings({
      backup_enabled: settings.value.backup_enabled,
      backup_frequency: settings.value.backup_frequency,
      backup_time: settings.value.backup_time,
      backup_day: settings.value.backup_day,
    });
    settings.value.next_run_label = res.next_run_label;
    showToast('success', res.message || 'Konfigurasi disimpan.');
  } catch (err) {
    const errors = err.response?.data?.errors;
    let msg = err.response?.data?.message || 'Gagal menyimpan konfigurasi.';
    if (errors) msg = Object.values(errors).flat()[0] || msg;
    showToast('error', msg);
  } finally {
    savingSettings.value = false;
  }
}

async function jalankanBackup() {
  if (running.value) return;
  if (!confirm('Jalankan backup sekarang? Isinya database beserta foto dokumen & tanda tangan.')) return;
  running.value = true;
  try {
    await backupService.run();
    showToast('success', 'Backup berhasil dijalankan.');
    await muatData();
  } catch (err) {
    showToast('error', err.response?.data?.message || 'Backup gagal.');
  } finally {
    running.value = false;
  }
}

async function download(b) {
  try {
    const blob = await backupService.download(b.filename);
    await downloadBlob(blob, b.filename);
  } catch (err) {
    showToast('error', err.response?.data?.message || err.message || 'Gagal mengunduh.');
  }
}

async function hapus(b) {
  if (!confirm(`Hapus "${b.filename}"?`)) return;
  try {
    await backupService.destroy(b.filename);
    showToast('success', 'File backup dihapus.');
    await muatData();
  } catch (err) {
    showToast('error', err.response?.data?.message || 'Gagal menghapus.');
  }
}

function formatTanggalIndo(str) {
  if (!str) return '-';
  try {
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', {
      day: 'numeric', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  } catch { return str; }
}

onMounted(() => { muatData(); muatSettings(); });
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Backup Database']" />

    <!-- Toast -->
    <div
      v-if="toast.message"
      :class="[
        'fixed top-[calc(4.5rem+var(--sat))] left-4 right-4 sm:left-auto z-50 sm:max-w-sm p-3 rounded-DEFAULT border shadow-elevation-2 flex items-start gap-2 text-sm',
        toast.type === 'success' ? 'bg-success-bg border-success-border text-success-text' : 'bg-error-bg border-error-border text-error',
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
        <h1 class="font-display font-bold text-xl sm:text-2xl mb-1">Backup Database</h1>
        <p class="text-text-secondary text-sm">Kelola pencadangan data SPJ (database + foto dokumen & tanda tangan) — manual & otomatis.</p>
      </div>
      <button
        @click="jalankanBackup"
        :disabled="running"
        class="text-sm font-medium px-4 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover disabled:opacity-60 inline-flex items-center justify-center gap-1.5 shrink-0"
      >
        <span :class="['material-symbols-outlined !text-base', running ? 'animate-spin' : '']">
          {{ running ? 'progress_activity' : 'backup' }}
        </span>
        {{ running ? 'Memproses...' : 'Backup Sekarang' }}
      </button>
    </div>

    <!-- ============ PANEL KONFIGURASI ============ -->
    <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 mb-4 overflow-hidden">
      <div class="px-5 py-3 border-b border-border-soft bg-surface-disabled/30 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined !text-lg text-text-subtle">schedule</span>
          <p class="text-xs font-semibold text-text-subtle uppercase tracking-wider">Backup Otomatis</p>
        </div>
        <span
          v-if="!loadingSettings"
          :class="[
            'inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-pill border uppercase tracking-wider',
            settings.backup_enabled ? 'bg-success-bg text-success-text border-success-border' : 'bg-surface-disabled text-text-subtle border-border',
          ]"
        >
          <span class="material-symbols-outlined !text-sm">
            {{ settings.backup_enabled ? 'toggle_on' : 'toggle_off' }}
          </span>
          {{ settings.backup_enabled ? 'Aktif' : 'Nonaktif' }}
        </span>
      </div>

      <div v-if="loadingSettings" class="p-5 text-center text-sm text-text-subtle">Memuat konfigurasi...</div>

      <div v-else class="p-5 space-y-5">
        <!-- Toggle -->
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium mb-0.5">Aktifkan Backup Otomatis</p>
            <p class="text-xs text-text-subtle leading-relaxed">
              Jika dinonaktifkan, scheduler tidak akan menjalankan backup otomatis.
              Backup manual tetap bisa dilakukan.
            </p>
          </div>
          <button
            type="button"
            @click="settings.backup_enabled = !settings.backup_enabled"
            :class="[
              'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-pill border-2 border-transparent transition-colors',
              settings.backup_enabled ? 'bg-primary' : 'bg-surface-disabled',
            ]"
            role="switch"
            :aria-checked="settings.backup_enabled"
          >
            <span
              :class="[
                'pointer-events-none inline-block h-5 w-5 transform rounded-pill bg-white shadow transition',
                settings.backup_enabled ? 'translate-x-5' : 'translate-x-0',
              ]"
            />
          </button>
        </div>

        <!-- Interval & waktu -->
        <div v-if="settings.backup_enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-border-soft">
          <div>
            <label class="block text-sm font-medium mb-1.5">Interval Backup</label>
            <select v-model="settings.backup_frequency" class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm">
              <option v-for="opt in FREQUENCY_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div v-if="showTimePicker">
            <label class="block text-sm font-medium mb-1.5">Jam Backup</label>
            <input v-model="settings.backup_time" type="time" class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm" />
          </div>
          <div v-if="showDayPicker">
            <label class="block text-sm font-medium mb-1.5">Hari Backup</label>
            <select v-model.number="settings.backup_day" class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm">
              <option v-for="d in DAY_OPTIONS" :key="d.value" :value="d.value">{{ d.label }}</option>
            </select>
          </div>
        </div>

        <!-- Preview -->
        <div
          v-if="settings.backup_enabled && settings.next_run_label"
          class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-info-bg border border-info-border"
        >
          <span class="material-symbols-outlined !text-base text-info-text shrink-0 mt-0.5">event</span>
          <div class="min-w-0">
            <p class="text-xs font-medium text-info-text">Jadwal backup berikutnya</p>
            <p class="text-xs text-info-text/80 mt-0.5">{{ settings.next_run_label }}</p>
          </div>
        </div>
      </div>

      <div v-if="!loadingSettings" class="px-5 py-3 border-t border-border-soft bg-surface-disabled/30 flex justify-end">
        <button
          type="button"
          @click="simpanSettings"
          :disabled="savingSettings"
          class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-60 inline-flex items-center gap-1.5"
        >
          <span v-if="savingSettings" class="material-symbols-outlined !text-base animate-spin">progress_activity</span>
          <span v-else class="material-symbols-outlined !text-base">save</span>
          {{ savingSettings ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
        </button>
      </div>
    </div>

    <!-- Chips statistik -->
    <div class="flex items-center gap-2 mb-4 flex-wrap">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface border border-border text-xs font-medium text-text-secondary">
        <span class="w-1.5 h-1.5 rounded-pill bg-primary"></span>
        Total: {{ stats.total_count }} file
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-info-bg text-info-text border border-info-border text-xs font-medium">
        <span class="material-symbols-outlined !text-sm">database</span>
        {{ stats.total_size_formatted }}
      </span>
    </div>

    <p v-if="errorMsg" class="text-sm text-error mb-4">{{ errorMsg }}</p>

    <SkeletonCardList v-if="loading" :items="3" :columns="1" />

    <!-- Empty -->
    <div v-else-if="backups.length === 0" class="text-center py-16 border border-dashed border-border rounded-DEFAULT bg-surface">
      <div class="flex flex-col items-center gap-3 max-w-xs mx-auto">
        <span class="w-14 h-14 rounded-pill bg-surface-disabled flex items-center justify-center">
          <span class="material-symbols-outlined !text-3xl text-text-subtle">backup</span>
        </span>
        <p class="text-sm font-medium text-text-secondary">Belum ada file backup</p>
        <p class="text-xs text-text-subtle leading-relaxed">
          Klik "Backup Sekarang" untuk membuat backup pertama, atau tunggu scheduler otomatis.
        </p>
      </div>
    </div>

    <!-- List -->
    <div v-else class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden">
      <div class="px-5 py-3 border-b border-border-soft bg-surface-disabled/30">
        <p class="text-xs font-semibold text-text-subtle uppercase tracking-wider">Riwayat Backup</p>
      </div>

      <div class="divide-y divide-border-soft">
        <div
          v-for="b in backups"
          :key="b.filename"
          class="p-4 hover:bg-surface-disabled/20 transition-colors flex items-center gap-3 flex-wrap"
        >
          <span class="w-10 h-10 rounded-inner bg-info-bg text-info-text flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined !text-lg">folder_zip</span>
          </span>

          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium font-mono-id truncate">{{ b.filename }}</p>
            <div class="flex items-center gap-3 text-xs text-text-subtle mt-0.5 flex-wrap">
              <span class="inline-flex items-center gap-1">
                <span class="material-symbols-outlined !text-sm">calendar_today</span>
                {{ formatTanggalIndo(b.created_at) }}
              </span>
              <span class="inline-flex items-center gap-1">
                <span class="material-symbols-outlined !text-sm">database</span>
                {{ b.size_formatted }}
              </span>
              <span class="text-text-subtle/70 italic">{{ b.created_at_human }}</span>
            </div>
          </div>

          <div class="flex gap-1.5 shrink-0">
            <button
              @click="download(b)"
              class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border bg-surface hover:bg-surface-disabled inline-flex items-center gap-1"
              title="Download"
            >
              <span class="material-symbols-outlined !text-sm">download</span>
              <span class="hidden sm:inline">Download</span>
            </button>
            <button
              @click="hapus(b)"
              class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border bg-surface text-error hover:bg-error-bg inline-flex items-center gap-1"
              title="Hapus"
            >
              <span class="material-symbols-outlined !text-sm">delete</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>