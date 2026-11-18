<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import spjService from '@/services/spjService';
import jenisSpjService from '@/services/jenisSpjService';
import responsibleService from '@/services/responsibleService';
import { formatRupiah, formatTanggal, formatUkuranFile } from '@/helpers/format';
import StatusBadge from '@/components/StatusBadge.vue';
import ResponsibleFormModal from '@/components/ResponsibleFormModal.vue';
import SignatureBox from '@/components/SignatureBox.vue';

const props = defineProps({
  // Kalau diisi -> mode edit (SPJ sudah ada). Kalau kosong -> mode buat baru.
  id: { type: [String, Number], default: null },
});

const router = useRouter();

const STEPS = [
  { n: 1, label: 'Info Dasar', caption: 'Register & Kegiatan' },
  { n: 2, label: 'Dokumen', caption: 'Kwitansi, BAST & Nota' },
  { n: 3, label: 'Pihak & TTD', caption: 'PPTK, BPP & KPA' },
  { n: 4, label: 'Review', caption: 'Validasi Akhir' },
];

const step = ref(1);
const spj = ref(null); // hasil dari server setelah SPJ dibuat/dimuat
const loading = ref(true);
const errorMsg = ref('');

const jenisSpjOptions = ref([]);
const responsibles = ref([]);

const form = ref({
  nomor_spj: '',
  tanggal: new Date().toISOString().slice(0, 10),
  jenis_spj_id: '',
  perihal: '',
  uraian: '',
  jumlah_anggaran: '',
});

const savingStep1 = ref(false);

async function muatJenisSpj() {
  try {
    jenisSpjOptions.value = await jenisSpjService.index();
  } catch (err) {
    jenisSpjOptions.value = [];
  }
}

async function muatResponsibles() {
  try {
    responsibles.value = await responsibleService.index();
  } catch (err) {
    responsibles.value = [];
  }
}

async function muatSpj() {
  const hasil = await spjService.show(props.id ?? spj.value.id);
  spj.value = hasil;
  return hasil;
}

function isiFormDari(data) {
  form.value = {
    nomor_spj: data.nomor_spj ?? '',
    tanggal: data.tanggal ?? new Date().toISOString().slice(0, 10),
    jenis_spj_id: data.jenis_spj_id ?? '',
    perihal: data.perihal ?? '',
    uraian: data.uraian ?? '',
    jumlah_anggaran: data.jumlah_anggaran ?? '',
  };
}

onMounted(async () => {
  await Promise.all([muatJenisSpj(), muatResponsibles()]);

  if (props.id) {
    try {
      const hasil = await muatSpj();
      isiFormDari(hasil);
    } catch (err) {
      errorMsg.value = 'Gagal memuat data SPJ.';
    }
  }

  loading.value = false;
});

// ---------- Step 1: Info Dasar ----------

async function simpanStep1() {
  savingStep1.value = true;
  errorMsg.value = '';
  try {
    const payload = { ...form.value };
    if (!spj.value) {
      spj.value = await spjService.store(payload);
    } else {
      await spjService.update(spj.value.id, payload);
      await muatSpj();
    }
    step.value = 2;
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal menyimpan data SPJ. Cek kembali isian nomor SPJ.';
  } finally {
    savingStep1.value = false;
  }
}

// ---------- Step 2: Dokumen ----------

const dokumenUrut = computed(() =>
  [...(spj.value?.dokumen ?? [])].sort((a, b) => (a.dokumen_wajib?.urutan ?? 0) - (b.dokumen_wajib?.urutan ?? 0))
);
const dokumenTerupload = computed(() => dokumenUrut.value.filter((d) => d.status === 'sudah').length);
const dokumenWajibBelum = computed(
  () => dokumenUrut.value.filter((d) => d.dokumen_wajib?.wajib && d.status !== 'sudah').length
);

const uploadingId = ref(null);

async function pilihFileDokumen(dokumen, event, sumber) {
  const file = event.target.files[0];
  event.target.value = ''; // supaya bisa pilih file yang sama lagi kalau perlu
  if (!file) return;
  uploadingId.value = dokumen.id;
  try {
    await spjService.uploadDokumen(spj.value.id, dokumen.id, file, sumber);
    await muatSpj();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal mengunggah dokumen.');
  } finally {
    uploadingId.value = null;
  }
}

async function hapusDokumen(dokumen) {
  if (!confirm(`Hapus berkas "${dokumen.dokumen_wajib?.nama_dokumen}"?`)) return;
  uploadingId.value = dokumen.id;
  try {
    await spjService.hapusDokumen(spj.value.id, dokumen.id);
    await muatSpj();
  } catch (err) {
    alert('Gagal menghapus dokumen.');
  } finally {
    uploadingId.value = null;
  }
}

// ---------- Step 3: Pihak & TTD ----------

const pihak1Id = ref('');
const pihak2Id = ref('');
const savingPihak = ref(false);
const savingTtd1 = ref(false);
const savingTtd2 = ref(false);

watch(spj, (val) => {
  if (!val) return;
  pihak1Id.value = val.pihak1_id ?? '';
  pihak2Id.value = val.pihak2_id ?? '';
});

const responsiblesPihak1 = computed(() => responsibles.value.filter((r) => r.kategori === 'pihak1_internal'));
const responsiblesPihak2 = computed(() => responsibles.value.filter((r) => r.kategori === 'pihak2_rekanan'));

async function simpanPihak() {
  savingPihak.value = true;
  try {
    await spjService.update(spj.value.id, {
      pihak1_id: pihak1Id.value || null,
      pihak2_id: pihak2Id.value || null,
    });
    await muatSpj();
  } catch (err) {
    alert('Gagal menyimpan Pihak I / Pihak II.');
  } finally {
    savingPihak.value = false;
  }
}

const showModalResponsible = ref(false);
const modalKategori = ref('pihak1_internal');

function bukaModalResponsible(kategori) {
  modalKategori.value = kategori;
  showModalResponsible.value = true;
}

function onResponsibleSaved(hasil) {
  responsibles.value.push(hasil);
  if (hasil.kategori === 'pihak1_internal') {
    pihak1Id.value = hasil.id;
  } else {
    pihak2Id.value = hasil.id;
  }
}

async function simpanTtd(pihak, metode, payload) {
  const savingRef = pihak === '1' ? savingTtd1 : savingTtd2;
  savingRef.value = true;
  try {
    if (metode === 'gambar') {
      await spjService.simpanTandaTanganGambar(spj.value.id, pihak, payload);
    } else {
      await spjService.simpanTandaTanganUpload(spj.value.id, pihak, payload);
    }
    await muatSpj();
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal menyimpan tanda tangan.');
  } finally {
    savingRef.value = false;
  }
}

// ---------- Navigasi antar step ----------

function gotoStep(n) {
  // Sebelum SPJ tersimpan (belum ada id), step 2-4 belum boleh diakses.
  if (n > 1 && !spj.value) return;
  step.value = n;
}

function selesai() {
  router.push({ name: 'spj.detail', params: { id: spj.value.id } });
}
</script>

<template>
  <div class="p-8 max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-1">
      <h1 class="font-display font-bold text-2xl">{{ id ? 'Edit SPJ' : 'Buat SPJ Baru' }}</h1>
      <RouterLink :to="{ name: 'spj.index' }" class="text-sm text-text-secondary hover:underline">
        &larr; Kembali ke Daftar
      </RouterLink>
    </div>
    <p class="text-text-secondary text-sm mb-6">
      Lengkapi 4 langkah berikut: Info Dasar, Dokumen, Pihak &amp; TTD, lalu Review.
    </p>

    <p v-if="loading" class="text-sm text-text-subtle">Memuat...</p>

    <template v-else>
      <!-- Stepper -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <button
          v-for="s in STEPS"
          :key="s.n"
          type="button"
          class="text-left p-3 rounded-DEFAULT border flex items-center gap-3 transition-colors"
          :class="[
            step === s.n ? 'border-primary bg-surface shadow-elevation-1' : 'border-border bg-surface hover:bg-surface-disabled',
            s.n > 1 && !spj ? 'opacity-50 cursor-not-allowed' : '',
          ]"
          @click="gotoStep(s.n)"
        >
          <span
            class="w-8 h-8 rounded-inner flex items-center justify-center text-sm font-semibold shrink-0"
            :class="step === s.n ? 'bg-primary text-white' : 'bg-surface-disabled text-text-secondary'"
          >
            {{ s.n }}
          </span>
          <span class="min-w-0">
            <span class="block text-sm font-medium truncate">{{ s.label }}</span>
            <span class="block text-xs text-text-subtle truncate">{{ s.caption }}</span>
          </span>
        </button>
      </div>

      <p v-if="errorMsg" class="text-sm text-error mb-4">{{ errorMsg }}</p>

      <!-- Step 1: Info Dasar -->
      <form v-if="step === 1" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-6 space-y-4" @submit.prevent="simpanStep1">
        <h2 class="font-display font-semibold text-lg mb-2">Informasi Identitas SPJ</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Nomor SPJ *</label>
            <input
              v-model="form.nomor_spj"
              type="text"
              required
              placeholder="Contoh: SPJ/2026/BPP-0199"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface font-mono-id text-sm
                     focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Tanggal *</label>
            <input
              v-model="form.tanggal"
              type="date"
              required
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm
                     focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Jenis SPJ *</label>
            <select
              v-model="form.jenis_spj_id"
              required
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm"
            >
              <option value="" disabled>Pilih jenis SPJ...</option>
              <option v-for="j in jenisSpjOptions" :key="j.id" :value="j.id">{{ j.nama }}</option>
            </select>
            <p class="text-xs text-text-subtle mt-1">Menentukan daftar dokumen wajib pada Langkah 2.</p>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Jumlah Anggaran (Rp)</label>
            <input
              v-model="form.jumlah_anggaran"
              type="number"
              min="0"
              step="1000"
              placeholder="0"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm
                     focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Perihal</label>
          <input
            v-model="form.perihal"
            type="text"
            placeholder="Contoh: Perjalanan Dinas Koordinasi Triwulan III"
            class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm
                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
          />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Uraian Kegiatan</label>
          <textarea
            v-model="form.uraian"
            rows="4"
            placeholder="Tuliskan tujuan kegiatan atau spesifikasi barang/jasa secara ringkas..."
            class="w-full p-3 rounded-DEFAULT border border-border bg-surface text-sm resize-y
                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/10"
          ></textarea>
        </div>

        <div class="flex justify-end pt-2">
          <button
            type="submit"
            :disabled="savingStep1"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover disabled:opacity-60"
          >
            {{ savingStep1 ? 'Menyimpan...' : 'Simpan & Lanjut →' }}
          </button>
        </div>
      </form>

      <!-- Step 2: Dokumen -->
      <div v-else-if="step === 2" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-6">
        <div class="flex items-center justify-between mb-1">
          <h2 class="font-display font-semibold text-lg">Kelengkapan Dokumen</h2>
          <span class="text-sm text-text-secondary">{{ dokumenTerupload }} dari {{ dokumenUrut.length }} terupload</span>
        </div>
        <p v-if="dokumenWajibBelum > 0" class="text-sm text-error mb-4">
          {{ dokumenWajibBelum }} dokumen wajib masih perlu dilengkapi.
        </p>
        <p v-else class="text-sm text-text-secondary mb-4">Semua dokumen wajib sudah terupload.</p>

        <div class="space-y-3">
          <div
            v-for="dokumen in dokumenUrut"
            :key="dokumen.id"
            class="border border-border-soft rounded-DEFAULT p-4 flex flex-col md:flex-row md:items-center gap-3 justify-between"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium">
                {{ dokumen.dokumen_wajib?.nama_dokumen }}
                <span v-if="dokumen.dokumen_wajib?.wajib" class="text-xs text-error ml-1">*wajib</span>
              </p>
              <p class="text-xs text-text-subtle">
                Format: {{ dokumen.dokumen_wajib?.format_file }} • maks. {{ dokumen.dokumen_wajib?.max_size_mb }} MB
              </p>
              <p v-if="dokumen.status === 'sudah'" class="text-xs text-text-secondary mt-1">
                {{ dokumen.file_nama_asli }} ({{ formatUkuranFile(dokumen.file_size_kb) }})
              </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <StatusBadge :status="dokumen.status === 'sudah' ? 'terverifikasi' : 'belum_lengkap'" />

              <a
                v-if="dokumen.status === 'sudah'"
                :href="dokumen.file_url"
                target="_blank"
                rel="noopener"
                class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled"
              >
                Lihat
              </a>

              <label
                class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border hover:bg-surface-disabled cursor-pointer"
                :class="{ 'opacity-60 pointer-events-none': uploadingId === dokumen.id }"
              >
                {{ uploadingId === dokumen.id ? 'Memproses...' : 'Pilih File' }}
                <input type="file" class="hidden" @change="pilihFileDokumen(dokumen, $event, 'upload')" />
              </label>

              <button
                v-if="dokumen.status === 'sudah'"
                type="button"
                :disabled="uploadingId === dokumen.id"
                class="text-xs font-medium px-2.5 py-1.5 rounded-inner border border-border text-error hover:bg-error-bg disabled:opacity-60"
                @click="hapusDokumen(dokumen)"
              >
                Hapus
              </button>
            </div>
          </div>

          <p v-if="dokumenUrut.length === 0" class="text-sm text-text-subtle text-center py-6">
            Tidak ada dokumen wajib untuk jenis SPJ ini.
          </p>
        </div>

        <div class="flex justify-between pt-6">
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT border border-border hover:bg-surface-disabled"
            @click="gotoStep(1)"
          >
            ← Kembali
          </button>
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover"
            @click="gotoStep(3)"
          >
            Lanjut →
          </button>
        </div>
      </div>

      <!-- Step 3: Pihak & TTD -->
      <div v-else-if="step === 3" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-6 space-y-6">
        <h2 class="font-display font-semibold text-lg">Pihak &amp; Tanda Tangan</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Pihak I -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium">Pihak I (Internal / PPK-PPTK)</label>
              <button type="button" class="text-xs text-primary hover:underline" @click="bukaModalResponsible('pihak1_internal')">
                + Tambah Responsible Baru
              </button>
            </div>
            <select
              v-model="pihak1Id"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm"
            >
              <option value="">Pilih penanggung jawab...</option>
              <option v-for="r in responsiblesPihak1" :key="r.id" :value="r.id">
                {{ r.nama }} — {{ r.jabatan || 'Tanpa jabatan' }}
              </option>
            </select>

            <SignatureBox
              label="Tanda Tangan Pihak I"
              :existing-path="spj?.ttd_pihak1_path"
              :existing-url="spj?.ttd_pihak1_url"
              :existing-metode="spj?.ttd_pihak1_metode"
              :disabled="!pihak1Id"
              :saving="savingTtd1"
              @simpan-gambar="(b64) => simpanTtd('1', 'gambar', b64)"
              @simpan-upload="(file) => simpanTtd('1', 'upload', file)"
            />
          </div>

          <!-- Pihak II -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium">Pihak II (Pengusul / Rekanan)</label>
              <button type="button" class="text-xs text-primary hover:underline" @click="bukaModalResponsible('pihak2_rekanan')">
                + Tambah Responsible Baru
              </button>
            </div>
            <select
              v-model="pihak2Id"
              class="w-full h-10 px-3 rounded-DEFAULT border border-border bg-surface text-sm"
            >
              <option value="">Pilih penanggung jawab...</option>
              <option v-for="r in responsiblesPihak2" :key="r.id" :value="r.id">
                {{ r.nama }} — {{ r.jabatan || 'Tanpa jabatan' }}
              </option>
            </select>

            <SignatureBox
              label="Tanda Tangan Pihak II"
              :existing-path="spj?.ttd_pihak2_path"
              :existing-url="spj?.ttd_pihak2_url"
              :existing-metode="spj?.ttd_pihak2_metode"
              :disabled="!pihak2Id"
              :saving="savingTtd2"
              @simpan-gambar="(b64) => simpanTtd('2', 'gambar', b64)"
              @simpan-upload="(file) => simpanTtd('2', 'upload', file)"
            />
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="button"
            :disabled="savingPihak"
            class="text-sm font-medium px-4 py-2 rounded-DEFAULT border border-border hover:bg-surface-disabled disabled:opacity-60"
            @click="simpanPihak"
          >
            {{ savingPihak ? 'Menyimpan...' : 'Simpan Pihak I / II' }}
          </button>
        </div>

        <div class="flex justify-between pt-4 border-t border-border-soft">
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT border border-border hover:bg-surface-disabled"
            @click="gotoStep(2)"
          >
            ← Kembali
          </button>
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover"
            @click="gotoStep(4)"
          >
            Lanjut →
          </button>
        </div>
      </div>

      <!-- Step 4: Review -->
      <div v-else-if="step === 4 && spj" class="bg-surface border border-border rounded-DEFAULT shadow-elevation-1 p-6 space-y-6">
        <div class="flex items-center justify-between">
          <h2 class="font-display font-semibold text-lg">Review &amp; Validasi</h2>
          <StatusBadge :status="spj.status" />
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-text-subtle">Nomor SPJ</dt>
            <dd class="font-medium font-mono-id">{{ spj.nomor_spj }}</dd>
          </div>
          <div>
            <dt class="text-text-subtle">Tanggal</dt>
            <dd class="font-medium">{{ formatTanggal(spj.tanggal) }}</dd>
          </div>
          <div>
            <dt class="text-text-subtle">Jenis SPJ</dt>
            <dd class="font-medium">{{ spj.jenis_spj?.nama ?? '-' }}</dd>
          </div>
          <div>
            <dt class="text-text-subtle">Jumlah Anggaran</dt>
            <dd class="font-medium">{{ formatRupiah(spj.jumlah_anggaran) }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-text-subtle">Perihal</dt>
            <dd class="font-medium">{{ spj.perihal || '-' }}</dd>
          </div>
          <div class="md:col-span-2">
            <dt class="text-text-subtle">Uraian</dt>
            <dd>{{ spj.uraian || '-' }}</dd>
          </div>
          <div>
            <dt class="text-text-subtle">Pihak I</dt>
            <dd class="font-medium">{{ spj.pihak1?.nama ?? 'Belum dipilih' }}</dd>
          </div>
          <div>
            <dt class="text-text-subtle">Pihak II</dt>
            <dd class="font-medium">{{ spj.pihak2?.nama ?? 'Belum dipilih' }}</dd>
          </div>
        </dl>

        <div class="pt-4 border-t border-border-soft">
          <p class="text-sm font-medium mb-2">Dokumen ({{ dokumenTerupload }}/{{ dokumenUrut.length }})</p>
          <ul class="text-sm space-y-1">
            <li v-for="d in dokumenUrut" :key="d.id" class="flex items-center justify-between">
              <span>{{ d.dokumen_wajib?.nama_dokumen }}</span>
              <StatusBadge :status="d.status === 'sudah' ? 'terverifikasi' : 'belum_lengkap'" />
            </li>
          </ul>
        </div>

        <div class="pt-4 border-t border-border-soft grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-text-subtle mb-1">Tanda Tangan Pihak I</p>
            <StatusBadge :status="spj.ttd_pihak1_path ? 'terverifikasi' : 'belum_lengkap'" />
          </div>
          <div>
            <p class="text-text-subtle mb-1">Tanda Tangan Pihak II</p>
            <StatusBadge :status="spj.ttd_pihak2_path ? 'terverifikasi' : 'belum_lengkap'" />
          </div>
        </div>

        <div class="flex justify-between pt-4 border-t border-border-soft">
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT border border-border hover:bg-surface-disabled"
            @click="gotoStep(3)"
          >
            ← Kembali
          </button>
          <button
            type="button"
            class="text-sm font-medium px-5 py-2 rounded-DEFAULT bg-primary text-white hover:bg-primary-hover"
            @click="selesai"
          >
            Selesai, Lihat Detail SPJ
          </button>
        </div>
      </div>
    </template>

    <ResponsibleFormModal
      :show="showModalResponsible"
      :kategori-default="modalKategori"
      :lock-kategori="true"
      @close="showModalResponsible = false"
      @saved="onResponsibleSaved"
    />
  </div>
</template>
