<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { formatAngkaSingkat } from '@/helpers/format';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const chartData = computed(() => {
  // Ambil top 5 saja
  const top = [...props.data].slice(0, 5);

  return {
    labels: top.map((d) => d.nama.length > 30 ? d.nama.substring(0, 28) + '…' : d.nama),
    datasets: [
      {
        label: 'Jumlah SPJ',
        data: top.map((d) => d.count),
        backgroundColor: [
          '#0f172a',
          '#334155',
          '#64748b',
          '#94a3b8',
          '#cbd5e1',
        ],
        borderRadius: 4,
        maxBarThickness: 24,
      },
    ],
  };
});

const chartOptions = {
  indexAxis: 'y',
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 10,
      cornerRadius: 6,
    },
  },
  scales: {
  x: {
    beginAtZero: true,
    grid: { color: '#f1f5f9' },
    ticks: {
      color: '#94a3b8',
      font: { size: 10 },
      callback: (value) => formatAngkaSingkat(value),
      precision: 0,
    },
    border: { display: false },
  },
  y: {
    grid: { display: false },
    ticks: {
      color: '#475569',
      font: { size: 11 },
      // Truncate nama panjang
      callback: function (value) {
        const label = this.getLabelForValue(value);
        return label.length > 20 ? label.substring(0, 18) + '…' : label;
      },
    },
    border: { display: false },
  },
},
};
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
    <div class="px-5 py-3.5 border-b border-border-soft flex items-center gap-2">
      <span class="material-symbols-outlined !text-lg text-text-subtle">stacked_bar_chart</span>
      <h3 class="font-display font-semibold text-sm">Top 5 Jenis SPJ</h3>
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