<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import userService from '@/services/userService';
import BaseModal from '@/components/BaseModal.vue';
import Pagination from '@/components/Pagination.vue';
import Breadcrumb from '@/components/Breadcrumb.vue';
import { useAuth } from '@/composables/useAuth';
import SkeletonTable from '@/components/skeleton/SkeletonTable.vue';

const { user: currentUser } = useAuth();

const userList = ref([]);
const loading = ref(true);
const errorMsg = ref('');
const search = ref('');

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 10,
});

const showModal = ref(false);
const editingUser = ref(null);
const saving = ref(false);
const formUser = ref({ name: '', email: '', password: '', role: 'user' });

async function muatUser(page = 1) {
  loading.value = true;
  errorMsg.value = '';
  try {
    const params = {
      page,
      per_page: pagination.value.per_page,
    };
    if (search.value.trim()) params.search = search.value.trim();

    const res = await userService.index(params);
    userList.value = Array.isArray(res) ? res : (res?.data ?? []);

    if (res && !Array.isArray(res)) {
      pagination.value = {
        current_page: res.current_page ?? 1,
        last_page: res.last_page ?? 1,
        total: res.total ?? userList.value.length,
        per_page: res.per_page ?? 10,
      };
    } else {
      pagination.value = {
        current_page: 1,
        last_page: 1,
        total: userList.value.length,
        per_page: userList.value.length || 10,
      };
    }
  } catch (err) {
    errorMsg.value = 'Gagal memuat daftar user.';
    userList.value = [];
    pagination.value = { current_page: 1, last_page: 1, total: 0, per_page: 10 };
  } finally {
    loading.value = false;
  }
}

const totalAdmin = computed(() => userList.value.filter((u) => u.role === 'admin').length);
const totalUser = computed(() => userList.value.filter((u) => u.role === 'user').length);

function bukaTambah() {
  editingUser.value = null;
  formUser.value = { name: '', email: '', password: '', role: 'user' };
  showModal.value = true;
}

function bukaEdit(u) {
  editingUser.value = u;
  formUser.value = {
    name: u.name ?? '',
    email: u.email ?? '',
    password: '',
    role: u.role ?? 'user',
  };
  showModal.value = true;
}

async function simpanUser() {
  saving.value = true;
  errorMsg.value = '';
  try {
    if (editingUser.value?.id) {
      const payload = { ...formUser.value };
      if (!payload.password) delete payload.password;
      const updated = await userService.update(editingUser.value.id, payload);
      const idx = userList.value.findIndex((u) => u.id === editingUser.value.id);
      if (idx !== -1) userList.value[idx] = updated;
    } else {
      const baru = await userService.store(formUser.value);
      userList.value.unshift(baru);
      pagination.value.total += 1;
    }
    showModal.value = false;
  } catch (err) {
    errorMsg.value =
      err.response?.data?.message ||
      'Gagal menyimpan user. Cek email, mungkin sudah dipakai.';
  } finally {
    saving.value = false;
  }
}

async function hapusUser(u) {
  if (u.id === currentUser.value?.id) {
    alert('Tidak bisa menghapus akun sendiri.');
    return;
  }
  if (!confirm(`Nonaktifkan/hapus akun "${u.name}"?`)) return;
  try {
    await userService.destroy(u.id);
    const totalAfterDelete = pagination.value.total - 1;
    const maxPage = Math.max(1, Math.ceil(totalAfterDelete / pagination.value.per_page));
    const targetPage = Math.min(pagination.value.current_page, maxPage);
    await muatUser(targetPage);
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menghapus user.');
  }
}

function handlePageChange(newPage) {
  if (newPage === pagination.value.current_page) return;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  muatUser(newPage);
}

function inisial(nama) {
  if (!nama) return '?';
  return nama
    .split(' ')
    .slice(0, 2)
    .map((s) => s.charAt(0).toUpperCase())
    .join('');
}

let debounceTimer;
watch(search, () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => muatUser(1), 300);
});

onMounted(() => muatUser());
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Kelola User']" />

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
      <div>
        <h1 class="font-display font-bold text-2xl mb-1">Kelola User</h1>
        <p class="text-text-secondary text-sm">
          Manajemen kredensial & hak akses. Registrasi publik dinonaktifkan.
        </p>
      </div>
      <button
        @click="bukaTambah"
        class="text-sm font-medium px-4 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover inline-flex items-center gap-1.5"
      >
        <span class="material-symbols-outlined !text-base">person_add</span>
        Tambah User
      </button>
    </div>

    <!-- Info banner -->
    <div class="flex items-start gap-3 p-4 rounded-DEFAULT bg-info-bg border border-info-border mb-4">
      <span class="material-symbols-outlined !text-lg text-info-text shrink-0 mt-0.5">info</span>
      <div>
        <p class="text-sm font-medium text-info-text">Registrasi Mandiri Dinonaktifkan</p>
        <p class="text-xs text-info-text/80 mt-0.5 leading-relaxed">
          Setiap akun baru dibuat eksklusif oleh Administrator dengan role dan
          kewenangan terverifikasi.
        </p>
      </div>
    </div>

    <!-- Chips -->
    <div class="flex items-center gap-2 mb-4 flex-wrap">
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface border border-border text-xs font-medium text-text-secondary">
        Total: {{ pagination.total }}
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-primary text-white text-xs font-medium">
        Admin: {{ totalAdmin }}
      </span>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill bg-surface-disabled text-text-secondary text-xs font-medium">
        User: {{ totalUser }}
      </span>
    </div>

    <!-- Search -->
    <div class="relative mb-4">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-subtle !text-base pointer-events-none">
        search
      </span>
      <input
        v-model="search"
        type="text"
        placeholder="Cari nama atau email..."
        class="w-full h-10 pl-9 pr-3 rounded-DEFAULT border border-border bg-surface text-sm placeholder:text-text-subtle"
      />
    </div>

    <p v-if="errorMsg" class="text-sm text-error mb-4">{{ errorMsg }}</p>

        <!-- Loading -->
    <SkeletonTable v-if="loading" :rows="6" />

    <!-- Table + pagination -->
    <div v-else class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-border text-left text-text-secondary bg-surface-disabled/50">
              <th class="px-4 py-3 font-medium">Pengguna</th>
              <th class="px-4 py-3 font-medium hidden sm:table-cell">Email</th>
              <th class="px-4 py-3 font-medium">Role</th>
              <th class="px-4 py-3 font-medium text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="u in userList"
              :key="u.id"
              class="border-b border-border-soft last:border-0 hover:bg-surface-disabled/30 transition-colors"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <span
                    :class="[
                      'w-8 h-8 rounded-pill text-xs font-semibold flex items-center justify-center shrink-0',
                      u.role === 'admin'
                        ? 'bg-primary text-white'
                        : 'bg-surface-disabled text-text-secondary',
                    ]"
                  >
                    {{ inisial(u.name) }}
                  </span>
                  <div class="min-w-0">
                    <p class="font-medium truncate">{{ u.name }}</p>
                    <p class="text-xs text-text-subtle truncate sm:hidden">{{ u.email }}</p>
                    <p v-if="u.id === currentUser?.id" class="text-[10px] text-text-subtle italic">
                      (Anda)
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-text-secondary hidden sm:table-cell">{{ u.email }}</td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'text-[10px] font-semibold px-2 py-0.5 rounded-pill uppercase tracking-wider',
                    u.role === 'admin'
                      ? 'bg-primary text-white'
                      : 'bg-surface-disabled text-text-secondary',
                  ]"
                >
                  {{ u.role }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-1.5">
                  <button
                    @click="bukaEdit(u)"
                    class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled inline-flex items-center gap-1"
                  >
                    <span class="material-symbols-outlined !text-sm">edit</span>
                    Edit
                  </button>
                  <button
                    v-if="u.id !== currentUser?.id"
                    @click="hapusUser(u)"
                    class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border text-error hover:bg-error-bg inline-flex items-center gap-1"
                  >
                    <span class="material-symbols-outlined !text-sm">delete</span>
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="userList.length === 0">
              <td colspan="4" class="px-4 py-12 text-center">
                <span class="material-symbols-outlined !text-3xl text-text-subtle">group_off</span>
                <p class="text-sm font-medium text-text-secondary mt-2">
                  {{ search ? 'Tidak ada hasil' : 'Belum ada user' }}
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        :total="pagination.total"
        :per-page="pagination.per_page"
        item-label="pengguna"
        @update:page="handlePageChange"
      />
    </div>

    <!-- Modal -->
    <BaseModal
      v-if="showModal"
      :title="editingUser ? 'Edit User' : 'Tambah User Baru'"
      @close="showModal = false"
    >
      <div v-if="!editingUser" class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-surface-disabled border border-border-soft mb-4">
        <span class="material-symbols-outlined !text-base text-text-subtle shrink-0 mt-0.5">mail</span>
        <p class="text-xs text-text-secondary leading-relaxed">
          Akun akan langsung terdaftar dan kredensial sementara akan dikirimkan ke email kedinasan.
        </p>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1.5">
            Nama <span class="text-error">*</span>
          </label>
          <input
            v-model="formUser.name"
            placeholder="Contoh: Rina Wulandari, S.Sos"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">
            Email <span class="text-error">*</span>
          </label>
          <input
            v-model="formUser.email"
            type="email"
            placeholder="nama@instansi.go.id"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">
            Password
            <span v-if="!editingUser" class="text-error">*</span>
            <span v-else class="text-text-subtle text-xs font-normal ml-1">(kosongkan jika tidak diubah)</span>
          </label>
          <input
            v-model="formUser.password"
            type="password"
            :placeholder="editingUser ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter'"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1.5">
            Role <span class="text-error">*</span>
          </label>
          <select
            v-model="formUser.role"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm"
          >
            <option value="user">User — Pengusul SPJ</option>
            <option value="admin">Admin — Verifikator & Monitoring</option>
          </select>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 rounded-DEFAULT border border-border text-sm font-medium hover:bg-surface-disabled"
          >
            Batal
          </button>
          <button
            @click="simpanUser"
            :disabled="saving || !formUser.name.trim() || !formUser.email.trim() || (!editingUser && !formUser.password)"
            class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium disabled:opacity-60 inline-flex items-center gap-1.5"
          >
            <span v-if="saving" class="material-symbols-outlined !text-base animate-spin">
              progress_activity
            </span>
            {{ saving ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>