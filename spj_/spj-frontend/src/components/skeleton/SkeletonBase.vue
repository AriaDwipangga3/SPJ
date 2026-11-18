<script setup>
defineProps({
  /**
   * Class tambahan untuk width/height/rounded.
   * Contoh: "h-4 w-32 rounded" atau "h-10 w-full rounded-DEFAULT"
   */
  class: { type: String, default: 'h-4 w-full rounded' },
});
</script>

<template>
  <div
    :class="[
      'bg-surface-disabled animate-pulse',
      $props.class,
    ]"
    aria-hidden="true"
  ></div>
</template>