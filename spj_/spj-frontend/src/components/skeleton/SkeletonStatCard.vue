<script setup>
import SkeletonBase from './SkeletonBase.vue';
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-5">
    <div class="flex items-start justify-between mb-3">
      <SkeletonBase class="h-3 w-20 rounded" />
      <SkeletonBase class="h-9 w-9 rounded-inner" />
    </div>
    <SkeletonBase class="h-8 w-16 rounded mb-3" />
    <SkeletonBase class="h-3 w-32 rounded" />
  </div>
</template>