<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  caption: { type: String, default: '' },
  icon: { type: String, default: '' },
  /**
   * 'default' | 'warning' | 'success' | 'info'
   */
  accent: { type: String, default: 'default' },
});

const ACCENT_MAP = {
  default: {
    iconBg: 'bg-surface-disabled',
    iconColor: 'text-text-secondary',
    dot: 'bg-text-subtle',
  },
  warning: {
    iconBg: 'bg-warning-bg',
    iconColor: 'text-warning-text',
    dot: 'bg-warning-text',
  },
  success: {
    iconBg: 'bg-success-bg',
    iconColor: 'text-success-text',
    dot: 'bg-success-text',
  },
  info: {
    iconBg: 'bg-info-bg',
    iconColor: 'text-info-text',
    dot: 'bg-info-text',
  },
};

// Auto-shrink font kalau value panjang
const valueLength = computed(() => String(props.value ?? '').length);

const valueFontClass = computed(() => {
  const len = valueLength.value;
  if (len > 20) return 'text-lg sm:text-xl';
  if (len > 14) return 'text-xl sm:text-2xl';
  return 'text-2xl sm:text-3xl';
});
</script>

<template>
  <div class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-4 sm:p-5 overflow-hidden">
    <div class="flex items-start justify-between mb-3 gap-2">
      <p class="text-xs font-medium text-text-subtle uppercase tracking-wider leading-tight min-w-0">
        {{ label }}
      </p>
      <span
        v-if="icon"
        :class="[
          'w-9 h-9 rounded-inner flex items-center justify-center shrink-0',
          ACCENT_MAP[accent]?.iconBg ?? ACCENT_MAP.default.iconBg,
        ]"
      >
        <span
          :class="[
            'material-symbols-outlined text-[18px] leading-none',
            ACCENT_MAP[accent]?.iconColor ?? ACCENT_MAP.default.iconColor,
          ]"
          aria-hidden="true"
        >
          {{ icon }}
        </span>
      </span>
    </div>

    <p
      :class="[
        'font-display font-bold mb-2 break-all leading-tight',
        valueFontClass,
      ]"
      :title="String(value)"
    >
      {{ value }}
    </p>

    <div v-if="caption" class="flex items-center gap-1.5">
      <span
        :class="[
          'w-1.5 h-1.5 rounded-pill shrink-0',
          ACCENT_MAP[accent]?.dot ?? ACCENT_MAP.default.dot,
        ]"
      ></span>
      <p class="text-xs text-text-subtle leading-tight min-w-0">{{ caption }}</p>
    </div>
  </div>
</template>