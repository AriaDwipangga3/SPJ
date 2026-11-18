<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const STATUS_COLORS = {
  draft: '#94a3b8',
  belum_lengkap: '#f59e0b',
  proses_verifikasi: '#3b82f6',
  terverifikasi: '#10b981',
  selesai: '#0f172a',
};

const chartData = computed(() => {
  // Filter status yang count-nya > 0
  const filtered = props.data.filter((d) => d.count > 0);

  return {
    labels: filtered.map((d) => d.label),
    datasets: [
      {
        data: filtered.map((d) => d.count),
        backgroundColor: filtered.map((d) => STATUS_COLORS[d.status] ?? '#94a3b8'),
        borderWidth: 2,
        borderColor: '#ffffff',
      },
    ],
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '62%',
  plugins: {
    legend: {
  position: 'bottom',
  labels: {
    boxWidth: 10,
    boxHeight: 10,
    padding: 10,
    font: { size: 10 },
    color: '#64748b',
    usePointStyle: true,
    pointStyle: 'circle',
    // Truncate label panjang
    generateLabels: (chart) => {
      const defaultLabels = ChartJS.overrides.doughnut.plugins.legend.labels.generateLabels(chart);
      return defaultLabels.map((l) => ({
        ...l,
        text: l.text.length > 18 ? l.text.substring(0, 16) + '…' : l.text,
      }));
    },
  },
},
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 10,
      cornerRadius: 6,
      callbacks: {
        label: (ctx) => {
          const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
          const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
          return ` ${ctx.label}: ${ctx.raw} (${pct}%)`;
        },
      },
    },
  },
};

const totalCount = computed(() =>
  props.data.reduce((sum, d) => sum + d.count, 0)
);
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1">
    <div class="px-5 py-3.5 border-b border-border-soft flex items-center gap-2">
      <span class="material-symbols-outlined !text-lg text-text-subtle">donut_small</span>
      <h3 class="font-display font-semibold text-sm">Breakdown Status</h3>
      <span class="text-xs text-text-subtle ml-auto">{{ totalCount }} berkas</span>
    </div>
    <div class="p-5">
      <div v-if="totalCount === 0" class="h-64 flex items-center justify-center text-sm text-text-subtle">
        Belum ada data
      </div>
      <div v-else class="h-64">
        <Doughnut :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>