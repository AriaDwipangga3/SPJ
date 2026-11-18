<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { formatAngkaSingkat } from '@/helpers/format';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const chartData = computed(() => ({
  labels: props.data.map((d) => d.label),
  datasets: [
    {
      label: 'Jumlah SPJ',
      data: props.data.map((d) => d.count),
      backgroundColor: '#0f172a',
      borderRadius: 4,
      maxBarThickness: 32,
    },
  ],
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 10,
      cornerRadius: 6,
      titleFont: { size: 12, weight: 'bold' },
      bodyFont: { size: 12 },
      callbacks: {
        label: (ctx) => ` ${ctx.raw} SPJ`,
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' },
      ticks: {
        color: '#94a3b8',
        font: { size: 10 },
        // Auto-format kalau angka besar
        callback: (value) => formatAngkaSingkat(value),
        precision: 0,
      },
      border: { display: false },
    },
    x: {
      grid: { display: false },
      ticks: {
        color: '#64748b',
        font: { size: 10 },
        maxRotation: 45,
        minRotation: 0,
        autoSkip: true,
      },
      border: { display: false },
    },
  },
};
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
    <div class="px-5 py-3.5 border-b border-border-soft flex items-center gap-2">
      <span class="material-symbols-outlined !text-lg text-text-subtle">bar_chart</span>
      <h3 class="font-display font-semibold text-sm">SPJ per Bulan</h3>
      <span class="text-xs text-text-subtle ml-auto">12 bulan terakhir</span>
    </div>
    <div class="p-5">
      <div v-if="data.length === 0" class="h-64 flex items-center justify-center text-sm text-text-subtle">
        Belum ada data
      </div>
      <div v-else class="h-64">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>