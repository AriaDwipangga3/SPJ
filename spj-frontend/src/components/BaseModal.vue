<script setup>
import { onMounted, onUnmounted } from 'vue';

defineProps({
  title: { type: String, default: '' },
  /**
   * Ukuran modal:
   * - 'sm' → max-w-sm (form sederhana)
   * - 'md' → max-w-md (default, form 4-6 field)
   * - 'lg' → max-w-2xl (form kompleks, detail)
   */
  size: { type: String, default: 'md' },
});

const emit = defineEmits(['close']);

function onKey(e) {
  if (e.key === 'Escape') emit('close');
}

onMounted(() => {
  document.addEventListener('keydown', onKey);
  document.body.style.overflow = 'hidden';
});
onUnmounted(() => {
  document.removeEventListener('keydown', onKey);
  document.body.style.overflow = '';
});

const SIZE_MAP = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-2xl',
};
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
      style="padding-top: calc(1rem + var(--sat)); padding-bottom: calc(1rem + var(--sab))"
      @click.self="emit('close')"
    >
      <div
        :class="[
          'bg-surface rounded-DEFAULT shadow-elevation-3 w-full border border-border-soft',
          'flex flex-col max-h-full',
          SIZE_MAP[size] ?? SIZE_MAP.md,
        ]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-border-soft shrink-0">
          <h3 class="font-display font-semibold text-base">{{ title }}</h3>
          <button
            type="button"
            @click="emit('close')"
            class="text-text-subtle hover:text-text-primary w-8 h-8 flex items-center justify-center rounded-inner hover:bg-surface-disabled"
            aria-label="Tutup"
          >
            <span class="material-symbols-outlined !text-lg">close</span>
          </button>
        </div>

        <!-- Content (scrollable) -->
        <div class="px-5 py-4 overflow-y-auto">
          <slot />
        </div>

        <!-- Optional footer -->
        <div v-if="$slots.footer" class="px-5 py-4 border-t border-border-soft shrink-0">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>