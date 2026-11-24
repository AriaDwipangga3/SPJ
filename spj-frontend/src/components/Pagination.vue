<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
  // Label custom, mis. "berkas SPJ", "user", "data"
  itemLabel: { type: String, default: 'data' },
});

const emit = defineEmits(['update:page']);

const bisaPrev = computed(() => props.currentPage > 1);
const bisaNext = computed(() => props.currentPage < props.lastPage);

const rangeStart = computed(() => {
  if (props.total === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const rangeEnd = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total);
});

/**
 * Bangun array nomor halaman dengan ellipsis kalau banyak.
 * Contoh: [1, '...', 4, 5, 6, '...', 20]
 */
const pageNumbers = computed(() => {
  const total = props.lastPage;
  const current = props.currentPage;
  const delta = 1;

  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }

  const left = Math.max(2, current - delta);
  const right = Math.min(total - 1, current + delta);

  const range = [1];
  if (left > 2) range.push('...');
  for (let i = left; i <= right; i++) range.push(i);
  if (right < total - 1) range.push('...');
  range.push(total);

  return range;
});

function goTo(page) {
  if (page === '...') return;
  if (page < 1 || page > props.lastPage) return;
  if (page === props.currentPage) return;
  emit('update:page', page);
}
</script>

<template>
  <div
    v-if="lastPage > 1 || total > 0"
    class="flex items-center justify-between gap-3 flex-wrap px-4 py-3 border-t border-border-soft bg-surface"
  >
    <!-- Info range -->
    <p class="text-xs text-text-secondary">
      Menampilkan
      <span class="font-medium text-text-primary">{{ rangeStart }}–{{ rangeEnd }}</span>
      dari
      <span class="font-medium text-text-primary">{{ total }}</span>
      {{ itemLabel }}
    </p>

    <!-- Kontrol -->
    <div v-if="lastPage > 1" class="flex items-center gap-1">
      <!-- Prev -->
      <button
        type="button"
        :disabled="!bisaPrev"
        @click="goTo(currentPage - 1)"
        class="h-8 px-2.5 rounded-inner border border-border bg-surface text-xs font-medium text-text-secondary
               hover:bg-surface-disabled disabled:opacity-40 disabled:cursor-not-allowed
               inline-flex items-center gap-1"
        aria-label="Halaman sebelumnya"
      >
        <span class="material-symbols-outlined !text-base">chevron_left</span>
        <span class="hidden sm:inline">Prev</span>
      </button>

      <!-- Page numbers (desktop) -->
      <div class="hidden sm:flex items-center gap-1">
        <template v-for="(p, idx) in pageNumbers" :key="`${p}-${idx}`">
          <span
            v-if="p === '...'"
            class="w-8 h-8 flex items-center justify-center text-xs text-text-subtle select-none"
          >
            …
          </span>
          <button
            v-else
            type="button"
            @click="goTo(p)"
            :class="[
              'min-w-[32px] h-8 px-2 rounded-inner text-xs font-medium transition-colors',
              p === currentPage
                ? 'bg-primary text-white'
                : 'border border-border bg-surface text-text-secondary hover:bg-surface-disabled',
            ]"
            :aria-current="p === currentPage ? 'page' : undefined"
          >
            {{ p }}
          </button>
        </template>
      </div>

      <!-- Indicator (mobile) -->
      <span class="sm:hidden text-xs font-medium text-text-secondary px-2">
        {{ currentPage }} / {{ lastPage }}
      </span>

      <!-- Next -->
      <button
        type="button"
        :disabled="!bisaNext"
        @click="goTo(currentPage + 1)"
        class="h-8 px-2.5 rounded-inner border border-border bg-surface text-xs font-medium text-text-secondary
               hover:bg-surface-disabled disabled:opacity-40 disabled:cursor-not-allowed
               inline-flex items-center gap-1"
        aria-label="Halaman berikutnya"
      >
        <span class="hidden sm:inline">Next</span>
        <span class="material-symbols-outlined !text-base">chevron_right</span>
      </button>
    </div>
  </div>
</template>