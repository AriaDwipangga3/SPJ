<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  label: { type: String, required: true },
  // Path tanda tangan yang sudah tersimpan di server (ttd_pihakN_path), kalau ada.
  existingPath: { type: String, default: null },
  // URL bertanda tangan untuk menampilkan spesimen (dari server).
  existingUrl: { type: String, default: null },
  existingMetode: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
});

const emit = defineEmits(['simpan-gambar', 'simpan-upload']);

const mode = ref('gambar'); // 'gambar' | 'upload'
const canvasEl = ref(null);
const menggambar = ref(false);
const adaGoresan = ref(false);
const fileTerpilih = ref(null);

function posisi(e) {
  const rect = canvasEl.value.getBoundingClientRect();
  const titik = e.touches ? e.touches[0] : e;
  return { x: titik.clientX - rect.left, y: titik.clientY - rect.top };
}

function mulaiGambar(e) {
  if (props.disabled) return;
  menggambar.value = true;
  const ctx = canvasEl.value.getContext('2d');
  const { x, y } = posisi(e);
  ctx.beginPath();
  ctx.moveTo(x, y);
}

function gambar(e) {
  if (!menggambar.value) return;
  e.preventDefault();
  const ctx = canvasEl.value.getContext('2d');
  const { x, y } = posisi(e);
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#0f172a';
  ctx.lineTo(x, y);
  ctx.stroke();
  adaGoresan.value = true;
}

function selesaiGambar() {
  menggambar.value = false;
}

function bersihkanKanvas() {
  const ctx = canvasEl.value.getContext('2d');
  ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height);
  adaGoresan.value = false;
}

function simpanGambar() {
  if (!adaGoresan.value) return;
  const base64 = canvasEl.value.toDataURL('image/png');
  emit('simpan-gambar', base64);
}

function pilihFile(e) {
  fileTerpilih.value = e.target.files[0] || null;
}

function simpanUpload() {
  if (!fileTerpilih.value) return;
  emit('simpan-upload', fileTerpilih.value);
}

onMounted(() => {
  // Kanvas putih polos sebagai kertas spesimen tanda tangan.
  const ctx = canvasEl.value.getContext('2d');
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, canvasEl.value.width, canvasEl.value.height);
});

watch(mode, () => {
  fileTerpilih.value = null;
});
</script>

<template>
  <div class="border border-border rounded-DEFAULT p-4" :class="{ 'opacity-60 pointer-events-none': disabled }">
    <div class="flex items-center justify-between mb-3">
      <p class="text-sm font-medium">{{ label }}</p>
      <span
        v-if="existingPath"
        class="text-xs font-medium px-2 py-0.5 rounded-pill bg-primary text-white"
      >
        Sudah TTD ({{ existingMetode }})
      </span>
      <span v-else class="text-xs font-medium px-2 py-0.5 rounded-pill bg-error-bg text-error">
        Belum TTD
      </span>
    </div>

    <a
      v-if="existingPath"
      :href="existingUrl"
      target="_blank"
      rel="noopener"
      class="block mb-3"
    >
      <img
        :src="existingUrl"
        alt="Spesimen tanda tangan tersimpan"
        class="h-16 border border-border-soft rounded-inner bg-white"
      />
    </a>

    <div class="flex gap-2 mb-3">
      <button
        type="button"
        class="text-xs font-medium px-3 py-1.5 rounded-inner border"
        :class="mode === 'gambar' ? 'bg-primary text-white border-primary' : 'border-border hover:bg-surface-disabled'"
        @click="mode = 'gambar'"
      >
        Gambar Tanda Tangan
      </button>
      <button
        type="button"
        class="text-xs font-medium px-3 py-1.5 rounded-inner border"
        :class="mode === 'upload' ? 'bg-primary text-white border-primary' : 'border-border hover:bg-surface-disabled'"
        @click="mode = 'upload'"
      >
        Upload Tanda Tangan
      </button>
    </div>

    <div v-if="mode === 'gambar'">
      <canvas
        ref="canvasEl"
        width="320"
        height="140"
        class="w-full max-w-[320px] border border-border-soft rounded-inner bg-white touch-none cursor-crosshair"
        @pointerdown="mulaiGambar"
        @pointermove="gambar"
        @pointerup="selesaiGambar"
        @pointerleave="selesaiGambar"
      />
      <div class="flex gap-2 mt-2">
        <button
          type="button"
          class="text-xs font-medium px-3 py-1.5 rounded-inner border border-border hover:bg-surface-disabled"
          @click="bersihkanKanvas"
        >
          Bersihkan
        </button>
        <button
          type="button"
          :disabled="!adaGoresan || saving"
          class="text-xs font-medium px-3 py-1.5 rounded-inner bg-primary text-white hover:bg-primary-hover disabled:opacity-60"
          @click="simpanGambar"
        >
          {{ saving ? 'Menyimpan...' : 'Simpan Tanda Tangan' }}
        </button>
      </div>
    </div>

    <div v-else>
      <input
        type="file"
        accept="image/png,image/jpeg"
        class="block w-full text-xs mb-2"
        @change="pilihFile"
      />
      <p class="text-xs text-text-subtle mb-2">PNG/JPG, latar putih polos, maks. 2 MB.</p>
      <button
        type="button"
        :disabled="!fileTerpilih || saving"
        class="text-xs font-medium px-3 py-1.5 rounded-inner bg-primary text-white hover:bg-primary-hover disabled:opacity-60"
        @click="simpanUpload"
      >
        {{ saving ? 'Mengunggah...' : 'Simpan Tanda Tangan' }}
      </button>
    </div>
  </div>
</template>
