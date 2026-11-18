<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import passwordService from '@/services/passwordService';

const route = useRoute();
const router = useRouter();

const token = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const success = ref(false);
const errorMsg = ref('');
const showPassword = ref({ baru: false, konfirmasi: false });

const tokenValid = computed(() => !!token.value && !!email.value);
const passwordMismatch = computed(() =>
  passwordConfirmation.value && password.value !== passwordConfirmation.value
);

onMounted(() => {
  // Ambil token & email — handle berbagai format query
  // (kadang Laravel escape `&` jadi `&amp;` di log, kita bersihin)
  const params = new URLSearchParams(window.location.search);

  let tokenVal = params.get('token') || params.get('amp;token') || route.query.token || route.query['amp;token'] || '';
  let emailVal = params.get('email') || params.get('amp;email') || route.query.email || route.query['amp;email'] || '';

  // Bersihin HTML entity kalau ada
  tokenVal = String(tokenVal).replace(/&amp;/g, '&').trim();
  emailVal = String(emailVal).replace(/&amp;/g, '&').trim();

  token.value = tokenVal;
  email.value = emailVal;

  if (!tokenValid.value) {
    errorMsg.value = 'Link reset password tidak valid atau tidak lengkap. Silakan minta link baru.';
  }
});
async function handleSubmit() {
  if (!tokenValid.value) return;
  if (password.value.length < 8) {
    errorMsg.value = 'Password minimal 8 karakter.';
    return;
  }
  if (password.value !== passwordConfirmation.value) {
    errorMsg.value = 'Konfirmasi password tidak cocok.';
    return;
  }

  errorMsg.value = '';
  loading.value = true;
  try {
    await passwordService.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    success.value = true;
    setTimeout(() => router.push('/login'), 3000);
  } catch (err) {
    const errors = err.response?.data?.errors;
    let msg = err.response?.data?.message || 'Gagal mereset password.';
    if (errors) msg = Object.values(errors).flat()[0] || msg;
    errorMsg.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-dvh flex items-center justify-center bg-background px-4 py-8">
    <div class="w-full max-w-sm">
      <!-- Brand -->
      <div class="flex flex-col items-center mb-8">
        <div class="w-14 h-14 rounded-DEFAULT bg-primary flex items-center justify-center mb-4 shadow-elevation-2">
          <span class="material-symbols-outlined !text-white !text-2xl">account_balance</span>
        </div>
        <h1 class="font-display font-bold text-2xl mb-1">Sistem SPJ</h1>
        <p class="text-sm text-text-secondary">Buat Password Baru</p>
      </div>

      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-2 p-6 sm:p-8">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-6 pb-6 border-b border-border-soft">
          <span class="material-symbols-outlined !text-base text-text-subtle">password</span>
          <p class="text-xs font-medium text-text-subtle uppercase tracking-wider">
            Reset Password
          </p>
        </div>

        <!-- Success state -->
        <div v-if="success" class="text-center">
          <div class="w-16 h-16 mx-auto rounded-pill bg-success-bg flex items-center justify-center mb-4">
            <span class="material-symbols-outlined !text-3xl text-success-text">check_circle</span>
          </div>
          <h2 class="font-display font-semibold text-lg mb-2">Password Berhasil Direset</h2>
          <p class="text-sm text-text-secondary mb-6 leading-relaxed">
            Password Anda sudah diganti. Anda akan diarahkan ke halaman login dalam
            beberapa detik...
          </p>
          <RouterLink
            to="/login"
            class="w-full h-10 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover
                   inline-flex items-center justify-center gap-2"
          >
            <span class="material-symbols-outlined !text-base">login</span>
            Login Sekarang
          </RouterLink>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
          <!-- Info email locked -->
          <div v-if="tokenValid" class="flex items-start gap-2.5 p-3 rounded-DEFAULT bg-info-bg border border-info-border mb-2">
            <span class="material-symbols-outlined !text-base text-info-text shrink-0 mt-0.5">info</span>
            <div class="min-w-0">
              <p class="text-xs text-info-text leading-relaxed">
                Reset password untuk akun:<br />
                <strong>{{ email }}</strong>
              </p>
            </div>
          </div>

          <!-- Password baru -->
          <div>
            <label class="block text-sm font-medium mb-1.5" for="password">
              Password Baru <span class="text-error">*</span>
            </label>
            <div class="relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword.baru ? 'text' : 'password'"
                required
                placeholder="Minimal 8 karakter"
                class="w-full h-10 px-3 pr-10 rounded-DEFAULT border border-border bg-surface text-sm
                       placeholder:text-text-subtle focus:bg-background"
                :disabled="!tokenValid || loading"
              />
              <button
                type="button"
                @click="showPassword.baru = !showPassword.baru"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface-disabled text-text-subtle"
              >
                <span class="material-symbols-outlined !text-base">
                  {{ showPassword.baru ? 'visibility_off' : 'visibility' }}
                </span>
              </button>
            </div>
            <p class="text-xs text-text-subtle mt-1">
              Minimal 8 karakter, kombinasi huruf dan angka.
            </p>
          </div>

          <!-- Konfirmasi -->
          <div>
            <label class="block text-sm font-medium mb-1.5" for="password_confirmation">
              Konfirmasi Password <span class="text-error">*</span>
            </label>
            <div class="relative">
              <input
                id="password_confirmation"
                v-model="passwordConfirmation"
                :type="showPassword.konfirmasi ? 'text' : 'password'"
                required
                placeholder="Ulangi password baru"
                class="w-full h-10 px-3 pr-10 rounded-DEFAULT border text-sm bg-surface
                       placeholder:text-text-subtle focus:bg-background"
                :class="passwordMismatch ? 'border-error' : 'border-border'"
                :disabled="!tokenValid || loading"
              />
              <button
                type="button"
                @click="showPassword.konfirmasi = !showPassword.konfirmasi"
                class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-inner flex items-center justify-center hover:bg-surface-disabled text-text-subtle"
              >
                <span class="material-symbols-outlined !text-base">
                  {{ showPassword.konfirmasi ? 'visibility_off' : 'visibility' }}
                </span>
              </button>
            </div>
            <p v-if="passwordMismatch" class="text-xs text-error mt-1">
              Konfirmasi password tidak cocok.
            </p>
          </div>

          <p v-if="errorMsg" class="text-sm text-error flex items-start gap-1.5">
            <span class="material-symbols-outlined !text-base shrink-0">error</span>
            <span>{{ errorMsg }}</span>
          </p>

          <button
            type="submit"
            :disabled="loading || !tokenValid || passwordMismatch || !password || !passwordConfirmation"
            class="w-full h-10 rounded-DEFAULT bg-primary text-white text-sm font-medium
                   hover:bg-primary-hover disabled:opacity-60
                   inline-flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined !text-base animate-spin">
              progress_activity
            </span>
            <span v-else class="material-symbols-outlined !text-base">lock_reset</span>
            {{ loading ? 'Memproses...' : 'Reset Password' }}
          </button>

          <p class="text-center text-sm text-text-secondary pt-2">
            <RouterLink to="/login" class="hover:underline hover:text-text-primary">
              Batal, kembali ke login
            </RouterLink>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>