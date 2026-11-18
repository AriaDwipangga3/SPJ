<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import passwordService from '@/services/passwordService';

const email = ref('');
const loading = ref(false);
const sent = ref(false);
const errorMsg = ref('');

async function handleSubmit() {
  errorMsg.value = '';
  loading.value = true;
  try {
    await passwordService.forgotPassword({ email: email.value });
    sent.value = true;
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal mengirim email reset. Coba lagi.';
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

      <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-2 p-6 sm:p-8">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-6 pb-6 border-b border-border-soft">
          <span class="material-symbols-outlined !text-base text-text-subtle">lock_reset</span>
          <p class="text-xs font-medium text-text-subtle uppercase tracking-wider">
            Reset Password
          </p>
        </div>

        <!-- Sent state -->
        <div v-if="sent" class="text-center">
          <div class="w-16 h-16 mx-auto rounded-pill bg-success-bg flex items-center justify-center mb-4">
            <span class="material-symbols-outlined !text-3xl text-success-text">mark_email_read</span>
          </div>
          <h2 class="font-display font-semibold text-lg mb-2">Email Terkirim</h2>
          <p class="text-sm text-text-secondary mb-6 leading-relaxed">
            Jika email <strong class="text-text-primary">{{ email }}</strong> terdaftar di sistem kami,
            Anda akan menerima link reset password dalam beberapa menit.
          </p>
          <p class="text-xs text-text-subtle mb-6">
            Cek folder <strong>Spam</strong> atau <strong>Promotions</strong> kalau email tidak muncul
            di inbox utama.
          </p>
          <RouterLink
            to="/login"
            class="w-full h-10 rounded-DEFAULT bg-primary text-white text-sm font-medium hover:bg-primary-hover
                   inline-flex items-center justify-center gap-2"
          >
            <span class="material-symbols-outlined !text-base">arrow_back</span>
            Kembali ke Login
          </RouterLink>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
          <p class="text-sm text-text-secondary mb-4 leading-relaxed">
            Masukkan email yang terdaftar. Kami akan mengirimkan link untuk membuat password baru.
          </p>

          <div>
            <label class="block text-sm font-medium mb-1.5" for="email">Email</label>
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

          <p v-if="errorMsg" class="text-sm text-error flex items-start gap-1.5">
            <span class="material-symbols-outlined !text-base shrink-0">error</span>
            <span>{{ errorMsg }}</span>
          </p>

          <button
            type="submit"
            :disabled="loading || !email"
            class="w-full h-10 rounded-DEFAULT bg-primary text-white text-sm font-medium
                   hover:bg-primary-hover disabled:opacity-60
                   inline-flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="material-symbols-outlined !text-base animate-spin">
              progress_activity
            </span>
            <span v-else class="material-symbols-outlined !text-base">send</span>
            {{ loading ? 'Mengirim...' : 'Kirim Link Reset' }}
          </button>

          <p class="text-center text-sm text-text-secondary pt-2">
            <RouterLink to="/login" class="hover:underline hover:text-text-primary inline-flex items-center gap-1">
              <span class="material-symbols-outlined !text-base">arrow_back</span>
              Kembali ke Login
            </RouterLink>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>