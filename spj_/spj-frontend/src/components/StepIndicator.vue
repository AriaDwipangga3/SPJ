<script setup>
defineProps({
  steps: { type: Array, required: true },
  current: { type: Number, required: true },
});
</script>

<template>
  <div class="mb-8">
    <div class="flex items-center">
      <template v-for="(label, idx) in steps" :key="label">
        <div class="flex items-center gap-2.5 shrink-0">
          <!-- Step dot -->
          <div
            class="w-8 h-8 rounded-pill flex items-center justify-center text-xs font-semibold shrink-0 transition-all"
            :class="[
              idx + 1 < current
                ? 'bg-primary text-white'
                : idx + 1 === current
                ? 'bg-primary text-white ring-4 ring-primary/10'
                : 'bg-surface-disabled text-text-subtle border border-border',
            ]"
          >
            <span
              v-if="idx + 1 < current"
              class="material-symbols-outlined !text-base"
              aria-hidden="true"
            >
              check
            </span>
            <span v-else>{{ idx + 1 }}</span>
          </div>

          <!-- Label -->
          <div class="hidden sm:block">
            <p
              class="text-sm font-medium leading-tight"
              :class="idx + 1 <= current ? 'text-text-primary' : 'text-text-subtle'"
            >
              {{ label }}
            </p>
            <p
              class="text-[10px] uppercase tracking-wider leading-tight"
              :class="idx + 1 < current ? 'text-text-subtle' : 'text-text-subtle/70'"
            >
              {{ idx + 1 < current ? 'Selesai' : idx + 1 === current ? 'Sedang Aktif' : 'Menunggu' }}
            </p>
          </div>
        </div>

        <!-- Connector -->
        <div
          v-if="idx < steps.length - 1"
          class="flex-1 h-0.5 mx-3 rounded-pill"
          :class="idx + 1 < current ? 'bg-primary' : 'bg-border'"
        />
      </template>
    </div>

    <!-- Mobile: teks step aktif -->
    <p class="sm:hidden mt-3 text-sm font-medium text-text-primary">
      Langkah {{ current }} dari {{ steps.length }} — {{ steps[current - 1] }}
    </p>
  </div>
</template>