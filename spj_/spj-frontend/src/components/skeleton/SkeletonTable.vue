<script setup>
import SkeletonBase from './SkeletonBase.vue';

defineProps({
  rows: { type: Number, default: 5 },
  // Kalau true, tanpa border+shadow wrapper sendiri
  noWrapper: { type: Boolean, default: false },
});
</script>

<template>
  <div
    :class="
      noWrapper
        ? ''
        : 'bg-surface border border-border rounded-DEFAULT shadow-elevation-1 overflow-hidden'
    "
  >
    <!-- Header -->
    <div class="px-4 py-3 border-b border-border bg-surface-disabled/50 flex gap-4">
      <SkeletonBase class="h-4 w-24 rounded" />
      <SkeletonBase class="h-4 w-20 rounded" />
      <SkeletonBase class="h-4 w-28 rounded" />
      <SkeletonBase class="h-4 w-24 rounded hidden md:block" />
      <SkeletonBase class="h-4 w-24 rounded hidden md:block" />
      <SkeletonBase class="h-4 w-16 rounded hidden lg:block" />
      <div class="flex-1"></div>
      <SkeletonBase class="h-4 w-12 rounded" />
    </div>

    <!-- Rows -->
    <div
      v-for="i in rows"
      :key="i"
      class="px-4 py-4 border-b border-border-soft last:border-0 flex items-center gap-4"
    >
      <SkeletonBase class="h-4 w-28 rounded shrink-0" />
      <SkeletonBase class="h-4 w-20 rounded shrink-0" />
      <SkeletonBase class="h-4 w-24 rounded shrink-0" />
      <div class="hidden md:flex items-center gap-2 shrink-0">
        <SkeletonBase class="h-7 w-7 rounded-pill shrink-0" />
        <SkeletonBase class="h-4 w-20 rounded" />
      </div>
      <div class="hidden md:flex items-center gap-2 shrink-0">
        <SkeletonBase class="h-7 w-7 rounded-pill shrink-0" />
        <SkeletonBase class="h-4 w-20 rounded" />
      </div>
      <SkeletonBase class="h-6 w-20 rounded-pill hidden lg:block shrink-0" />
      <div class="flex-1"></div>
      <div class="flex gap-2 shrink-0">
        <SkeletonBase class="h-7 w-16 rounded-inner" />
        <SkeletonBase class="h-7 w-7 rounded-inner" />
      </div>
    </div>
  </div>
</template>