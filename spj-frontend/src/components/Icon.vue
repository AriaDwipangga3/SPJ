<script setup>
defineProps({
  name: { type: String, required: true }, // nama icon dari Material Symbols, mis. "dashboard", "description"
  size: { type: [String, Number], default: 20 },
});
</script>

<template>
  <span
    class="material-symbols-outlined shrink-0"
    :style="{ fontSize: `${size}px` }"
    aria-hidden="true"
  >{{ name }}</span>
</template>
