<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { RouterLink, useRouter } from 'vue-router';
import Icon from '@/components/Icon.vue';
import NotificationBell from '@/components/NotificationBell.vue';

const { user, isAdmin, logout } = useAuth();
const router = useRouter();
const route = useRoute();

const sidebarOpen = ref(false);
const searchQuery = ref('');

/* ============ Sidebar auto-close ============ */
watch(
  () => route.fullPath,
  () => { sidebarOpen.value = false; }
);

function onResize() {
  if (window.innerWidth >= 1024) sidebarOpen.value = false;
}

onMounted(() => window.addEventListener('resize', onResize));
onUnmounted(() => window.removeEventListener('resize', onResize));

/* ============ Search ============ */
function lakukanPencarian() {
  const q = searchQuery.value.trim();
  if (!q) return;
  router.push({ name: 'spj.index', query: { search: q } });
  searchQuery.value = '';
}

watch(
  () => route.query.search,
  (val) => {
    if (val && val !== searchQuery.value) searchQuery.value = val;
  }
);

/* ============ Computed ============ */
const namaUser = computed(() => user.value?.name ?? '-');
const roleLabel = computed(() => (isAdmin.value ? 'Administrator' : 'Pegawai / Staf'));
const inisial = computed(() => {
  const name = namaUser.value;
  return name && name !== '-' ? name.charAt(0).toUpperCase() : '?';
});

async function handleLogout() {
  await logout();
  router.push('/login');
}
</script>

<template>
  <div class="flex min-h-dvh bg-background">
    <!-- ============ SIDEBAR ============ -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 w-64 shrink-0 border-r border-border bg-surface flex flex-col safe-top safe-bottom',
        'transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:overflow-y-auto',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      ]"
      aria-label="Menu utama"
    >
      <!-- Logo -->
      <div class="px-5 py-4 border-b border-border-soft flex items-center gap-2.5 shrink-0">
        <div class="w-9 h-9 rounded-inner bg-primary flex items-center justify-center shrink-0">
          <Icon name="account_balance" size="18" class="!text-white" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-display font-bold text-sm leading-tight text-text-primary truncate">Sistem SPJ</p>
          <p class="text-[10px] text-text-subtle tracking-wider uppercase leading-tight truncate">Portal Internal</p>
        </div>
        <button
          @click="sidebarOpen = false"
          class="lg:hidden w-8 h-8 rounded-inner flex items-center justify-center hover:bg-surface-disabled shrink-0"
          aria-label="Tutup menu"
          type="button"
        >
          <Icon name="close" size="18" />
        </button>
      </div>

      <!-- Menu -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <p class="px-3 pb-1 text-[10px] font-semibold text-text-subtle uppercase tracking-widest select-none">
          Menu Utama
        </p>

        <RouterLink
          to="/"
          class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
          active-class="!bg-primary !text-white"
        >
          <Icon name="dashboard" />
          <span>Dashboard</span>
        </RouterLink>

        <RouterLink
          to="/spj"
          class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
          active-class="!bg-primary !text-white"
        >
          <Icon name="description" />
          <span>Daftar SPJ</span>
        </RouterLink>

        <RouterLink
          to="/responsible"
          class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
          active-class="!bg-primary !text-white"
        >
          <Icon name="contacts" />
          <span>Responsible</span>
        </RouterLink>

        <RouterLink
          to="/profile"
          class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
          active-class="!bg-primary !text-white"
        >
          <Icon name="person" />
          <span>Profil Saya</span>
        </RouterLink>

        <!-- Admin only -->
        <template v-if="isAdmin">
          <p class="px-3 pt-5 pb-1 text-[10px] font-semibold text-text-subtle uppercase tracking-widest select-none">
            Administrasi
          </p>

          <RouterLink
            to="/jenis-spj"
            class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
            active-class="!bg-primary !text-white"
          >
            <Icon name="category" />
            <span>Jenis SPJ</span>
          </RouterLink>

          <RouterLink
            to="/kelola-user"
            class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
            active-class="!bg-primary !text-white"
          >
            <Icon name="group" />
            <span>Kelola User</span>
          </RouterLink>

          <RouterLink
            to="/kelola-backup"
            class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
            active-class="!bg-primary !text-white"
          >
            <Icon name="backup" />
            <span>Backup Database</span>
          </RouterLink>

          <RouterLink
            to="/log-aktivitas"
            class="flex items-center gap-2.5 px-3 py-2.5 rounded-DEFAULT text-sm font-medium text-text-secondary hover:bg-surface-disabled hover:text-text-primary transition-colors"
            active-class="!bg-primary !text-white"
          >
            <Icon name="history" />
            <span>Log Aktivitas</span>
          </RouterLink>
        </template>
      </nav>

      <!-- User info + logout -->
      <div class="px-4 py-4 border-t border-border-soft shrink-0">
        <RouterLink
          to="/profile"
          class="flex items-center gap-2.5 mb-3 p-2 -m-2 rounded-DEFAULT hover:bg-surface-disabled transition-colors group"
          active-class="!bg-surface-disabled"
        >
          <span class="w-9 h-9 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center shrink-0 select-none">
            {{ inisial }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-text-primary truncate">{{ namaUser }}</p>
            <p class="text-[10px] text-text-subtle truncate uppercase tracking-wider">{{ roleLabel }}</p>
          </div>
          <span class="material-symbols-outlined !text-base text-text-subtle opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            chevron_right
          </span>
        </RouterLink>

        <button
          @click="handleLogout"
          class="flex items-center gap-1.5 text-xs font-medium text-error hover:underline w-full"
          type="button"
        >
          <Icon name="logout" size="14" />
          Keluar Sistem
        </button>
      </div>
    </aside>

    <!-- Overlay mobile -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/40 z-30 lg:hidden"
      @click="sidebarOpen = false"
      aria-hidden="true"
    ></div>

    <!-- ============ MAIN ============ -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Topbar -->
      <header class="sticky top-0 z-20 bg-surface/95 backdrop-blur border-b border-border">
        <div class="flex items-center gap-3 px-4 lg:px-6 h-14">
          <button
            @click="sidebarOpen = true"
            class="lg:hidden w-9 h-9 flex items-center justify-center rounded-inner hover:bg-surface-disabled shrink-0"
            aria-label="Buka menu"
            type="button"
          >
            <Icon name="menu" size="20" />
          </button>

          <div class="lg:hidden flex items-center gap-2 shrink-0">
            <div class="w-7 h-7 rounded-inner bg-primary flex items-center justify-center shrink-0">
              <Icon name="account_balance" size="14" class="!text-white" />
            </div>
            <p class="font-display font-bold text-sm truncate">SPJ</p>
          </div>

          <div class="hidden lg:block w-full max-w-md">
            <div class="relative">
              <Icon
                name="search"
                size="18"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle pointer-events-none"
              />
              <input
                v-model="searchQuery"
                @keydown.enter="lakukanPencarian"
                type="text"
                placeholder="Cari nomor SPJ, kegiatan, atau status... (Enter)"
                class="w-full h-9 pl-10 pr-3 rounded-DEFAULT border border-border bg-background text-sm
                       placeholder:text-text-subtle focus:bg-surface transition-colors"
              />
            </div>
          </div>

          <div class="flex-1"></div>

          <div class="flex items-center gap-1 shrink-0">
            <NotificationBell />

            <div class="hidden md:block w-px h-6 bg-border mx-1"></div>

            <RouterLink
              to="/profile"
              class="hidden md:flex items-center gap-2 shrink-0 pl-2 pr-1 py-1 rounded-DEFAULT hover:bg-surface-disabled transition-colors"
              active-class="!bg-surface-disabled"
            >
              <div class="text-right min-w-0">
                <p class="text-xs font-medium leading-tight truncate max-w-[140px]">{{ namaUser }}</p>
                <p class="text-[10px] text-text-subtle leading-tight truncate">{{ roleLabel }}</p>
              </div>
              <span class="w-8 h-8 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center shrink-0 select-none">
                {{ inisial }}
              </span>
            </RouterLink>

            <span class="md:hidden w-8 h-8 rounded-pill bg-primary text-white text-xs font-semibold flex items-center justify-center shrink-0 select-none">
              {{ inisial }}
            </span>
          </div>
        </div>
      </header>

      <main class="flex-1 min-w-0 safe-bottom">
        <slot />
      </main>
    </div>
  </div>
</template>