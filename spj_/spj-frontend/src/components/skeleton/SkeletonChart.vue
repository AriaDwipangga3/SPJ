<script setup>
import SkeletonBase from './SkeletonBase.vue';

defineProps({
  height: { type: String, default: 'h-64' },
});
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
    <div class="px-5 py-3.5 border-b border-border-soft flex items-center gap-2">
      <SkeletonBase class="h-5 w-5 rounded" />
      <SkeletonBase class="h-4 w-32 rounded" />
      <SkeletonBase class="h-3 w-24 rounded ml-auto" />
    </div>
    <div class="p-5">
      <div :class="height" class="flex items-end gap-2">
        <SkeletonBase class="w-full rounded-t" :class="['h-1/4', 'bg-surface-disabled']" style="height: 25%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 60%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 40%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 80%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 55%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 70%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 35%" />
        <SkeletonBase class="w-full rounded-t" :class="['bg-surface-disabled']" style="height: 90%" />
      </div>
    </div>
  </div>
</template>