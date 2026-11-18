<script setup>
defineProps({
  items: { type: Array, required: true }, // ['SISTEM SPJ', 'DASHBOARD ADMIN']
});
</script>

<template>
  <p class="text-xs font-semibold text-text-subtle uppercase tracking-wide mb-1">
    <template v-for="(item, idx) in items" :key="idx">
      <span :class="idx === items.length - 1 ? 'text-text-secondary' : ''">{{ item }}</span>
      <span v-if="idx < items.length - 1" class="mx-1.5">/</span>
    </template>
  </p>
</template>
