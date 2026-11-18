<script setup>
import { computed } from 'vue';

const props = defineProps({
  // Dibuat nullable, bukan required — biar nggak warning kalau data belum lengkap
  status: { type: String, default: '' },
});

const META = {
  draft: {
    label: 'Draft',
    icon: 'edit_note',
    class: 'bg-surface-disabled text-text-secondary border-border-soft',
  },
  belum_lengkap: {
    label: 'Belum Lengkap',
    icon: 'error_outline',
    class: 'bg-error-bg text-error border-transparent',
  },
  proses_verifikasi: {
    label: 'Proses Verifikasi',
    icon: 'schedule',
    class: 'bg-surface-disabled text-text-secondary border-border-soft',
  },
  terverifikasi: {
    label: 'Terverifikasi',
    icon: 'check_circle',
    class: 'bg-primary text-white border-primary',
  },
  selesai: {
    label: 'Selesai',
    icon: 'verified',
    class: 'bg-primary text-white border-primary',
  },
};

const FALLBACK = {
  label: 'Tanpa Status',
  icon: 'help_outline',
  class: 'bg-surface-disabled text-text-secondary border-border-soft',
};

const meta = computed(() => {
  const key = (props.status ?? '').toString().trim();
  return META[key] ?? FALLBACK;
});
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-pill border text-xs font-medium whitespace-nowrap',
      meta.class,
    ]"
  >
    <span class="material-symbols-outlined text-[14px] leading-none" aria-hidden="true">
      {{ meta.icon }}
    </span>
    {{ meta.label }}
  </span>
</template>