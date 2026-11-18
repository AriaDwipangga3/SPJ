<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';

const { login } = useAuth();
const router = useRouter();

const email = ref('');
const password = ref('');
const ingatSesi = ref(true);
const errorMsg = ref('');
const loading = ref(false);

async function handleSubmit() {
  errorMsg.value = '';
  loading.value = true;
  try {
    await login(email.value, password.value);
    router.push('/');
  } catch (err) {
    // Laravel membungkus error validasi (422) dengan "message" generik
    // ("The given data was invalid."). Pesan spesifik ada di errors.email.
    if (!err.response) {
      // Tidak ada balasan dari server (timeout / server mati / IP salah)
      errorMsg.value =
        'Tidak bisa terhubung ke server. Pastikan backend menyala dan alamat API benar.';
      return;
    }
    const fieldErrors = err.response?.data?.errors;
    errorMsg.value =
      fieldErrors?.email?.[0] ||
      fieldErrors?.password?.[0] ||
      (err.response?.status !== 422 ? err.response?.data?.message : null) ||
      'Email atau password salah.';
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
        <p class="text-sm text-text-secondary">Internal Management Application</p>
      </div>

      <!-- Card -->
      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-2 p-6 sm:p-8">
        <!-- Chip "Authentication Gateway" -->
        <div class="flex items-center gap-2 mb-6 pb-6 border-b border-border-soft">
          <span class="material-symbols-outlined !text-base text-text-subtle">lock</span>
          <p class="text-xs font-medium text-text-subtle uppercase tracking-wider">
            Authentication Gateway
          </p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1.5" for="email">
              Email
            </label>
            <input
              id="email"
              v-model="email"
              type="email"
              required
              autocomplete="email"
              placeholder="nama@instansi.go.id"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm
                     placeholder:text-text-subtle focus:bg-background"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1.5" for="password">
              Password
            </label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="**********"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm
                     placeholder:text-text-subtle focus:bg-background"
            />
          </div>

          <label class="flex items-center gap-2 text-sm text-text-secondary cursor-pointer select-none">
            <input
              type="checkbox"
              v-model="ingatSesi"
              class="w-4 h-4 rounded-inner border-border accent-primary"
            />
            Ingat Sesi Perangkat
          </label>

          <p v-if="errorMsg" class="text-sm text-error flex items-start gap-1.5">
            <span class="material-symbols-outlined !text-base shrink-0">error</span>
            <span>{{ errorMsg }}</span>
          </p>

          <button
            type="submit"
            :disabled="loading"
            class="w-full h-10 rounded-DEFAULT bg-primary text-white text-sm font-medium
                   hover:bg-primary-hover disabled:opacity-60
                   inline-flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined !text-base animate-spin">progress_activity</span>
            {{ loading ? 'Memproses...' : 'Login' }}
          </button>

          <p class="text-center text-sm text-text-secondary pt-2">
  <RouterLink to="/lupa-password" class="hover:underline hover:text-text-primary">
    Lupa password?
  </RouterLink>
</p>
        </form>
      </div>

      <!-- Footer -->
      <div class="mt-6 text-center">
        <div class="inline-flex items-start gap-2 px-4 py-3 rounded-DEFAULT bg-surface-disabled text-left max-w-sm">
          <span class="material-symbols-outlined !text-base text-text-subtle shrink-0 mt-0.5">shield</span>
          <p class="text-xs text-text-secondary leading-relaxed">
            <strong class="text-text-primary">Sistem Internal — Akses terbatas.</strong>
            Akun dibuat dan dikelola secara terpusat oleh Administrator SPJ.
            Registrasi mandiri dinonaktifkan.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>