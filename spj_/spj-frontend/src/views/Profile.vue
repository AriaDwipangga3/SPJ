<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '@/composables/useAuth';
import profileService from '@/services/profileService';
import Breadcrumb from '@/components/Breadcrumb.vue';

const { user, isAdmin } = useAuth();

/* ---------- State ---------- */
const savingProfile = ref(false);
const savingPassword = ref(false);
const toast = ref({ type: '', message: '' });

const formProfile = ref({
  name: '',
});

const formPassword = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showPassword = ref({
  current: false,
  baru: false,
  konfirmasi: false,
});

const namaUser = computed(() => user.value?.name ?? '-');
const emailUser = computed(() => user.value?.email ?? '-');
const roleUser = computed(() => user.value?.role ?? '-');
const roleLabel = computed(() => (isAdmin.value ? 'Administrator' : 'Pegawai / Staf'));
const inisial = computed(() => {
  const name = namaUser.value;
  return name && name !== '-' ? name.charAt(0).toUpperCase() : '?';
});

/* ---------- Toast ---------- */
function showToast(type, message) {
  toast.value = { type, message };
  setTimeout(() => {
    toast.value = { type: '', message: '' };
  }, 3500);
}

/* ---------- Load Data ---------- */
onMounted(() => {
  formProfile.value.name = user.value?.name ?? '';
});

/* ---------- Save Profile ---------- */
async function simpanProfil() {
  if (savingProfile.value) return;
  if (!formProfile.value.name.trim()) {
    showToast('error', 'Nama wajib diisi.');
    return;
  }

  savingProfile.value = true;
  try {
    const res = await profileService.updateProfile({
      name: formProfile.value.name,
    });

    // Update user state di localStorage + reactive
    const updatedUser = { ...user.value, ...res.user };
    localStorage.setItem('spj_user', JSON.stringify(updatedUser));
    user.value = updatedUser;

    showToast('success', 'Profil berhasil diperbarui.');
  } catch (err) {
    showToast(
      'error',
      err.response?.data?.message || 'Gagal menyimpan profil.'
    );
  } finally {
    savingProfile.value = false;
  }
}

/* ---------- Change Password ---------- */
async function gantiPassword() {
  if (savingPassword.value) return;

  if (!formPassword.value.current_password) {
    showToast('error', 'Password saat ini wajib diisi.');
    return;
  }
  if (formPassword.value.password.length < 8) {
    showToast('error', 'Password baru minimal 8 karakter.');
    return;
  }
  if (formPassword.value.password !== formPassword.value.password_confirmation) {
    showToast('error', 'Konfirmasi password tidak cocok.');
    return;
  }

  savingPassword.value = true;
  try {
    await profileService.changePassword({
      current_password: formPassword.value.current_password,
      password: formPassword.value.password,
      password_confirmation: formPassword.value.password_confirmation,
    });

    formPassword.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    };
    showToast('success', 'Password berhasil diubah. Sesi di perangkat lain telah logout.');
  } catch (err) {
    const errors = err.response?.data?.errors;
    let msg = err.response?.data?.message || 'Gagal mengubah password.';
    if (errors) {
      msg = Object.values(errors).flat()[0] || msg;
    }
    showToast('error', msg);
  } finally {
    savingPassword.value = false;
  }
}
</script>

<template>
  <div class="p-4 sm:p-6 lg:p-8 max-w-4xl mx-auto">
    <Breadcrumb :items="['Sistem SPJ', 'Profil Saya']" />

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
    <div class="mb-6">
      <h1 class="font-display font-bold text-xl sm:text-2xl mb-1">Profil Saya</h1>
      <p class="text-text-secondary text-sm">
        Kelola data diri dan keamanan akun Anda.
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-4">
      <!-- ============ SIDEBAR KIRI: KARTU PROFIL ============ -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-6 text-center h-fit">
        <div class="w-20 h-20 mx-auto rounded-pill bg-primary text-white text-2xl font-bold flex items-center justify-center mb-4 select-none">
          {{ inisial }}
        </div>
        <p class="font-display font-semibold text-base mb-0.5 truncate">{{ namaUser }}</p>
        <p class="text-xs text-text-subtle truncate mb-3">{{ emailUser }}</p>
        <span
          :class="[
            'inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-pill border uppercase tracking-wider',
            isAdmin
              ? 'bg-primary text-white border-primary'
              : 'bg-surface-disabled text-text-secondary border-border',
          ]"
        >
          <span class="material-symbols-outlined !text-sm">
            {{ isAdmin ? 'shield_person' : 'person' }}
          </span>
          {{ roleLabel }}
        </span>
      </div>

      <!-- ============ KONTEN KANAN ============ -->
      <div class="space-y-4">
        <!-- ===== CARD 1: DATA DIRI ===== -->
        <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
          <div class="px-5 py-4 border-b border-border-soft flex items-center gap-2">
            <span class="material-symbols-outlined !text-lg text-text-subtle">badge</span>
            <h2 class="font-display font-semibold text-base">Data Diri</h2>
          </div>

          <div class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1.5">
                Nama Lengkap <span class="text-error">*</span>
              </label>
              <input
                v-model="formProfile.name"
                type="text"
                placeholder="Nama lengkap & gelar"
                class="w-full h-10 px-3 rounded-DEFAULT border border-border text-sm focus:bg-background"
                :disabled="savingProfile"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5">Email</label>
              <input
                :value="emailUser"
                type="email"
                disabled
                class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface-disabled text-sm text-text-subtle cursor-not-allowed"
              />
              <p class="text-xs text-text-subtle mt-1.5 flex items-center gap-1">
                <span class="material-symbols-outlined !text-sm">lock</span>
                Email hanya bisa diubah oleh Administrator.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1.5">Role / Hak Akses</label>
              <input
                :value="roleLabel"
                type="text"
                disabled
                class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface-disabled text-sm text-text-subtle cursor-not-allowed"
              />
            </div>
          </div>

          <div class="px-5 py-3 border-t border-border-soft bg-surface-disabled/30 flex justify-end">
            <button
              type="button"
              @click="simpanProfil"
              :disabled="savingProfile"
              class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-60 inline-flex items-center gap-1.5"
            >
              <span v-if="savingProfile" class="material-symbols-outlined !text-base animate-spin">
                progress_activity
              </span>
              <span v-else class="material-symbols-outlined !text-base">save</span>
              {{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </div>

        <!-- ===== CARD 2: GANTI PASSWORD ===== -->
        <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
          <div class="px-5 py-4 border-b border-border-soft flex items-center gap-2">
            <span class="material-symbols-outlined !text-lg text-text-subtle">lock</span>
            <h2 class="font-display font-semibold text-base">Ganti Password</h2>
          </div>

          <div class="p-5 space-y-4">
            <div class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-info-bg border border-info-border">
              <span class="material-symbols-outlined !text-base text-info-text shrink-0 mt-0.5">info</span>
              <p class="text-xs text-info-text leading-relaxed">
                Setelah ganti password, sesi di perangkat lain akan otomatis logout.
                Password minimal 8 karakter, mengandung huruf dan angka.
              </p>
            </div>

            <!-- Password saat ini -->
            <div>
              <label class="block text-sm font-medium mb-1.5">
                Password Saat Ini <span class="text-error">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="formPassword.current_password"
                  :type="showPassword.current ? 'text' : 'password'"
                  placeholder="Masukkan password lama"
                  class="w-full h-10 px-3 pr-10 rounded-DEFAULT border border-border text-sm focus:bg-background"
                  :disabled="savingPassword"
                />
                <button
                  type="button"
                  @click="showPassword.current = !showPassword.current"
                  class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface-disabled text-text-subtle"
                  :aria-label="showPassword.current ? 'Sembunyikan' : 'Tampilkan'"
                >
                  <span class="material-symbols-outlined !text-base">
                    {{ showPassword.current ? 'visibility_off' : 'visibility' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Password baru -->
            <div>
              <label class="block text-sm font-medium mb-1.5">
                Password Baru <span class="text-error">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="formPassword.password"
                  :type="showPassword.baru ? 'text' : 'password'"
                  placeholder="Minimal 8 karakter"
                  class="w-full h-10 px-3 pr-10 rounded-DEFAULT border border-border text-sm focus:bg-background"
                  :disabled="savingPassword"
                />
                <button
                  type="button"
                  @click="showPassword.baru = !showPassword.baru"
                  class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface-disabled text-text-subtle"
                  :aria-label="showPassword.baru ? 'Sembunyikan' : 'Tampilkan'"
                >
                  <span class="material-symbols-outlined !text-base">
                    {{ showPassword.baru ? 'visibility_off' : 'visibility' }}
                  </span>
                </button>
              </div>
              <p class="text-xs text-text-subtle mt-1.5">
                Harus mengandung minimal 8 karakter, kombinasi huruf dan angka.
              </p>
            </div>

            <!-- Konfirmasi -->
            <div>
              <label class="block text-sm font-medium mb-1.5">
                Konfirmasi Password Baru <span class="text-error">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="formPassword.password_confirmation"
                  :type="showPassword.konfirmasi ? 'text' : 'password'"
                  placeholder="Ulangi password baru"
                  class="w-full h-10 px-3 pr-10 rounded-DEFAULT border border-border text-sm focus:bg-background"
                  :disabled="savingPassword"
                />
                <button
                  type="button"
                  @click="showPassword.konfirmasi = !showPassword.konfirmasi"
                  class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface-disabled text-text-subtle"
                  :aria-label="showPassword.konfirmasi ? 'Sembunyikan' : 'Tampilkan'"
                >
                  <span class="material-symbols-outlined !text-base">
                    {{ showPassword.konfirmasi ? 'visibility_off' : 'visibility' }}
                  </span>
                </button>
              </div>
            </div>
          </div>

          <div class="px-5 py-3 border-t border-border-soft bg-surface-disabled/30 flex justify-end">
            <button
              type="button"
              @click="gantiPassword"
              :disabled="savingPassword"
              class="px-4 py-2 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover disabled:opacity-60 inline-flex items-center gap-1.5"
            >
              <span v-if="savingPassword" class="material-symbols-outlined !text-base animate-spin">
                progress_activity
              </span>
              <span v-else class="material-symbols-outlined !text-base">lock_reset</span>
              {{ savingPassword ? 'Memproses...' : 'Ganti Password' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>