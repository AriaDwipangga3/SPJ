<script setup>
import SkeletonBase from './SkeletonBase.vue';

defineProps({
  items: { type: Number, default: 4 },
  // 1 = card list single-column, 2 = card grid 2-column
  columns: { type: Number, default: 1 },
});
</script>

<template>
  <div
    :class="
      columns === 2
        ? 'grid grid-cols-1 md:grid-cols-2 gap-3'
        : 'space-y-3'
    "
  >
    <div
      v-for="i in items"
      :key="i"
      class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-4"
    >
      <div class="flex items-start gap-3 mb-3">
        <SkeletonBase class="h-11 w-11 rounded-pill shrink-0" />
        <div class="flex-1 min-w-0 space-y-2">
          <SkeletonBase class="h-4 w-32 rounded" />
          <SkeletonBase class="h-3 w-24 rounded" />
        </div>
        <SkeletonBase class="h-6 w-16 rounded-pill shrink-0" />
      </div>
      <div class="space-y-2 mb-4">
        <SkeletonBase class="h-3 w-40 rounded" />
        <SkeletonBase class="h-3 w-56 rounded" />
      </div>
      <div class="flex justify-end gap-2 pt-3 border-t border-border-soft">
        <SkeletonBase class="h-7 w-16 rounded-inner" />
        <SkeletonBase class="h-7 w-20 rounded-inner" />
      </div>
    </div>
  </div>
</template>