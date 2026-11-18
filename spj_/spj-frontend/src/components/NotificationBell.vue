<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import echo from '@/lib/echo';
import notificationService from '@/services/notificationService';
import Icon from '@/components/Icon.vue';

const router = useRouter();
const { user } = useAuth();

const open = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);
const markingAll = ref(false);

let pollTimer = null;
let echoChannel = null;

/* ============ Fetch ============ */
async function muat() {
  loading.value = true;
  try {
    const res = await notificationService.index({ limit: 15 });
    notifications.value = res.notifications ?? [];
    unreadCount.value = res.unread_count ?? 0;
  } catch (err) {
    // Silent fail
  } finally {
    loading.value = false;
  }
}

async function cekUnread() {
  try {
    const res = await notificationService.unreadCount();
    unreadCount.value = res.unread_count ?? 0;
  } catch (err) {
    // silent
  }
}

/* ============ Actions ============ */
function togglePanel() {
  open.value = !open.value;
  if (open.value) muat();
}

async function handleKlik(n) {
  if (!n.read) {
    try {
      const res = await notificationService.markAsRead(n.id);
      n.read = true;
      unreadCount.value = res.unread_count ?? unreadCount.value - 1;
    } catch (err) { /* silent */ }
  }
  if (n.url) {
    open.value = false;
    router.push(n.url);
  }
}

async function handleMarkAllRead() {
  if (markingAll.value || unreadCount.value === 0) return;
  markingAll.value = true;
  try {
    await notificationService.markAllAsRead();
    notifications.value = notifications.value.map((n) => ({ ...n, read: true }));
    unreadCount.value = 0;
  } catch (err) { /* silent */ }
  finally { markingAll.value = false; }
}

async function handleHapus(n, e) {
  e.stopPropagation();
  try {
    const res = await notificationService.destroy(n.id);
    notifications.value = notifications.value.filter((x) => x.id !== n.id);
    unreadCount.value = res.unread_count ?? unreadCount.value;
  } catch (err) { /* silent */ }
}

async function handleClearRead() {
  if (!confirm('Hapus semua notifikasi yang sudah dibaca?')) return;
  try {
    await notificationService.clearRead();
    notifications.value = notifications.value.filter((n) => !n.read);
  } catch (err) { /* silent */ }
}

/* ============ Styling helper ============ */
const COLOR_MAP = {
  success: { bg: 'bg-success-bg', text: 'text-success-text' },
  warning: { bg: 'bg-warning-bg', text: 'text-warning-text' },
  info:    { bg: 'bg-info-bg',    text: 'text-info-text' },
  error:   { bg: 'bg-error-bg',   text: 'text-error' },
};

function warnaNotif(n) {
  return COLOR_MAP[n.color] ?? COLOR_MAP.info;
}

/* ============ Click outside ============ */
function onClickOutside(e) {
  const root = document.getElementById('notif-bell-root');
  if (root && !root.contains(e.target)) {
    open.value = false;
  }
}

/* ============ Real-time listener ============ */
function setupEchoListener() {
  const userId = user.value?.id;
  if (!userId || echoChannel) return;

  echoChannel = echo.private(`user.${userId}`);
  echoChannel.listen('.NotificationCreated', (e) => {
    // Prepend ke list kalau panel kebuka
    if (open.value) {
      notifications.value.unshift({
        id: e.id,
        type: e.type,
        title: e.title,
        message: e.message,
        catatan: e.catatan,
        spj_id: e.spj_id,
        nomor_spj: e.nomor_spj,
        url: e.url,
        icon: e.icon,
        color: e.color,
        read: false,
        created_at_human: e.created_at_human,
      });
    }
    // Update badge count
    unreadCount.value += 1;
  });
}

/* ============ Lifecycle ============ */
onMounted(() => {
  cekUnread();
  // Polling cadangan tiap 30 detik (untuk jaga-jaga kalau WS putus)
  pollTimer = setInterval(cekUnread, 30000);
  document.addEventListener('click', onClickOutside);
  setupEchoListener();
});

// Kalau user belum loaded saat mount, setup listener setelah user tersedia
watch(() => user.value?.id, (newId) => {
  if (newId && !echoChannel) {
    setupEchoListener();
  }
});

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer);
  document.removeEventListener('click', onClickOutside);
  if (echoChannel && user.value?.id) {
    echo.leave(`user.${user.value.id}`);
  }
});
</script>
<template>
  <div id="notif-bell-root" class="sm:relative">
    <!-- Tombol lonceng -->
    <button
      @click="togglePanel"
      :class="[
        'relative w-9 h-9 flex items-center justify-center rounded-inner hover:bg-surface-disabled shrink-0 transition-colors',
        open ? 'bg-surface-disabled' : '',
      ]"
      aria-label="Notifikasi"
      type="button"
    >
      <Icon name="notifications" size="20" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-pill bg-error text-white text-[10px] font-bold flex items-center justify-center border-2 border-surface"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Panel dropdown -->
    <div
      v-if="open"
      class="absolute left-4 right-4 top-full mt-2 sm:left-auto sm:right-0 sm:w-96 bg-surface border border-border rounded-DEFAULT shadow-elevation-3 z-50 overflow-hidden"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-border-soft flex items-center justify-between bg-surface">
        <div class="flex items-center gap-2">
          <span class="font-display font-semibold text-sm">Notifikasi</span>
          <span
            v-if="unreadCount > 0"
            class="text-[10px] font-bold px-1.5 py-0.5 rounded-pill bg-error text-white"
          >
            {{ unreadCount }} baru
          </span>
        </div>
        <button
          v-if="unreadCount > 0"
          @click="handleMarkAllRead"
          :disabled="markingAll"
          class="text-[11px] font-medium text-primary hover:underline disabled:opacity-60"
        >
          {{ markingAll ? 'Memproses...' : 'Tandai semua dibaca' }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-block w-6 h-6 border-2 border-border border-t-primary rounded-full animate-spin"></div>
        <p class="text-xs text-text-subtle mt-2">Memuat...</p>
      </div>

      <!-- Kosong -->
      <div v-else-if="notifications.length === 0" class="p-10 text-center">
        <span class="material-symbols-outlined !text-4xl text-text-subtle">notifications_off</span>
        <p class="text-sm font-medium text-text-secondary mt-2">Tidak ada notifikasi</p>
        <p class="text-xs text-text-subtle mt-0.5">Semua sudah dibaca</p>
      </div>

      <!-- List -->
      <div v-else class="max-h-[60dvh] sm:max-h-[400px] overflow-y-auto divide-y divide-border-soft">
        <div
          v-for="n in notifications"
          :key="n.id"
          @click="handleKlik(n)"
          :class="[
            'p-3 hover:bg-surface-disabled/30 transition-colors cursor-pointer flex items-start gap-3 group',
            n.read ? '' : 'bg-info-bg/20',
          ]"
        >
          <span
            :class="[
              'w-9 h-9 rounded-inner flex items-center justify-center shrink-0 mt-0.5',
              warnaNotif(n).bg,
              warnaNotif(n).text,
            ]"
          >
            <span class="material-symbols-outlined !text-lg">{{ n.icon }}</span>
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2 mb-0.5">
              <p
                :class="[
                  'text-xs font-semibold leading-tight truncate',
                  n.read ? 'text-text-secondary' : 'text-text-primary',
                ]"
              >
                {{ n.title }}
              </p>
              <span v-if="!n.read" class="w-2 h-2 rounded-pill bg-primary shrink-0 mt-0.5"></span>
            </div>
            <p class="text-[11px] text-text-secondary leading-snug line-clamp-2 mb-1">{{ n.message }}</p>
            <p
              v-if="n.catatan"
              class="text-[11px] text-warning-text bg-warning-bg rounded-inner px-2 py-1 leading-snug line-clamp-3 mb-1"
            >
              Catatan admin: {{ n.catatan }}
            </p>
            <p class="text-[10px] text-text-subtle">{{ n.created_at_human }}</p>
          </div>

          <button
            @click="handleHapus(n, $event)"
            class="w-6 h-6 rounded-inner flex items-center justify-center text-text-subtle hover:bg-error-bg hover:text-error lg:opacity-0 lg:group-hover:opacity-100 transition-opacity shrink-0"
            title="Hapus"
            type="button"
          >
            <span class="material-symbols-outlined !text-sm">close</span>
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div
        v-if="notifications.length > 0"
        class="px-4 py-2.5 border-t border-border-soft bg-surface-disabled/30 flex items-center justify-between"
      >
        <button
          @click="handleClearRead"
          class="text-[11px] font-medium text-text-secondary hover:text-error transition-colors inline-flex items-center gap-1"
        >
          <span class="material-symbols-outlined !text-sm">delete_sweep</span>
          Hapus yang sudah dibaca
        </button>
        <button
          @click="open = false"
          class="text-[11px] font-medium text-text-subtle hover:text-text-primary"
        >
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>
