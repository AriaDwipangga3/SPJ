<script setup>
import { useRoute } from 'vue-router';
import AppLayout from '@/layouts/AppLayout.vue';

const route = useRoute();
</script>

<template>
  <!-- Login & halaman standalone tidak pakai sidebar -->
  <router-view v-if="route.meta.public || route.meta.standalone" />

  <!-- Halaman normal: pakai layout sidebar -->
  <AppLayout v-else>
    <router-view />
  </AppLayout>
</template>