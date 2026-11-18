<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const emit = defineEmits(['simpan']);

const canvasRef = ref(null);
const isEmpty = ref(true);
const isDrawing = ref(false);
let ctx;
let resizeHandler;
let lastWidth = 0;

function setupCanvas() {
  const canvas = canvasRef.value;
  if (!canvas) return;

  const ratio = window.devicePixelRatio || 1;
  const rect = canvas.getBoundingClientRect();
  const cssWidth = rect.width || canvas.offsetWidth;
  const cssHeight = rect.height || canvas.offsetHeight;
  lastWidth = cssWidth;
  isEmpty.value = true;

  canvas.width = cssWidth * ratio;
  canvas.height = cssHeight * ratio;

  ctx = canvas.getContext('2d');
  ctx.scale(ratio, ratio);
  ctx.lineWidth = 2.2;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.strokeStyle = '#0f172a';

  // Default: putih polos
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, cssWidth, cssHeight);
  ctx.fillStyle = '#0f172a';
}

onMounted(() => {
  setupCanvas();
  resizeHandler = () => {
    // Hanya siapkan ulang kalau lebar berubah (mis. rotasi layar).
    // Resize karena keyboard / address bar tidak boleh menghapus tanda tangan.
    const w = canvasRef.value?.getBoundingClientRect().width;
    if (w && Math.abs(w - lastWidth) > 1) setupCanvas();
  };
  window.addEventListener('resize', resizeHandler);
});

onBeforeUnmount(() => {
  if (resizeHandler) window.removeEventListener('resize', resizeHandler);
});

function getPos(e) {
  const rect = canvasRef.value.getBoundingClientRect();
  const point = e.touches ? e.touches[0] : e;
  return {
    x: point.clientX - rect.left,
    y: point.clientY - rect.top,
  };
}

function mulai(e) {
  e.preventDefault();
  canvasRef.value.setPointerCapture?.(e.pointerId);
  isDrawing.value = true;
  isEmpty.value = false;
  const { x, y } = getPos(e);
  ctx.beginPath();
  ctx.moveTo(x, y);
}

function gambar(e) {
  if (!isDrawing.value) return;
  e.preventDefault();
  const { x, y } = getPos(e);
  ctx.lineTo(x, y);
  ctx.stroke();
}

function selesai() {
  isDrawing.value = false;
}

function clear() {
  const canvas = canvasRef.value;
  const ratio = window.devicePixelRatio || 1;
  ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = '#0f172a';
  isEmpty.value = true;
}

function simpan() {
  if (isEmpty.value) {
    alert('Tanda tangan masih kosong. Silakan gambar tanda tangan terlebih dahulu.');
    return;
  }
  const base64 = canvasRef.value.toDataURL('image/png');
  emit('simpan', base64);
}

defineExpose({ clear });
</script>

<template>
  <div>
    <!-- Canvas area -->
    <div class="relative rounded-DEFAULT border-2 border-dashed border-border bg-white overflow-hidden">
      <canvas
        ref="canvasRef"
        class="w-full h-40 touch-none cursor-crosshair block"
        @pointerdown="mulai"
        @pointermove="gambar"
        @pointerup="selesai"
        @pointercancel="selesai"
      />

      <!-- Placeholder -->
      <div
        v-if="isEmpty"
        class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-text-subtle"
      >
        <span class="material-symbols-outlined !text-2xl mb-1">draw</span>
        <p class="text-xs">Tanda tangan di area ini</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between gap-2 mt-2">
      <button
        type="button"
        @click="clear"
        class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface-disabled inline-flex items-center gap-1"
      >
        <span class="material-symbols-outlined !text-sm">refresh</span>
        Bersihkan
      </button>
      <button
        type="button"
        @click="simpan"
        :disabled="isEmpty"
        class="text-xs font-medium px-3.5 py-1.5 rounded-inner bg-primary text-white hover:bg-primary-hover disabled:opacity-40 inline-flex items-center gap-1"
      >
        <span class="material-symbols-outlined !text-sm">save</span>
        Simpan Tanda Tangan
      </button>
    </div>
  </div>
</template>