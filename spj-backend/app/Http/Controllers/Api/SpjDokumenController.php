<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use App\Models\SpjDokumen;
use App\Notifications\SpjDiperbarui;
use App\Services\ActivityLogger;
use App\Services\DokumenImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpjDokumenController extends Controller
{
    public function store(
        Request $request,
        Spj $spj,
        SpjDokumen $dokumen,
        DokumenImageProcessor $processor
    ) {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak bisa mengunggah dokumen.');
        abort_if($spj->dibuat_oleh !== $request->user()->id, 403, 'Anda bukan pemilik SPJ ini.');
        abort_if($dokumen->spj_id !== $spj->id, 404);
        abort_if($spj->terkunci(), 403, 'SPJ yang sudah terverifikasi tidak bisa diubah lagi. Hubungi admin.');

        $request->validate([
            // mimes: dicek dari ISI file (bukan hanya ekstensi), jadi file .php/.html
            // yang diganti nama jadi .jpg tetap ditolak.
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'sumber_file' => ['required', 'in:upload,kamera'],
        ], [
            'file.required' => 'File wajib dipilih.',
            'file.uploaded' => 'Upload gagal. Ukuran file kemungkinan melebihi batas server.',
            'file.mimes' => 'File harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $originalSize = $file->getSize();

        $processed = $processor->compress($file);

        if ($processed) {
            $extension = $processed['extension'];
            $filename = pathinfo($originalName, PATHINFO_FILENAME) . '.' . $extension;
            // Nama di disk dibersihkan dari karakter aneh; nama asli tetap disimpan di DB untuk tampilan.
            $safeName = preg_replace('/[^A-Za-z0-9._\-() ]+/', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;
            $storagePath = "spj/{$spj->id}/dokumen/" . uniqid() . '_' . $safeName;

            Storage::disk('public')->put($storagePath, $processed['content']);
            $fileSizeKb = intdiv(strlen($processed['content']), 1024);

            \Log::info('Dokumen dikompres', [
                'spj_id' => $spj->id,
                'original' => $originalName,
                'original_kb' => intdiv($originalSize, 1024),
                'compressed_kb' => $fileSizeKb,
                'hemat_persen' => $processor->persenHemat($originalSize, $fileSizeKb * 1024),
            ]);
        } else {
            $storagePath = $file->store("spj/{$spj->id}/dokumen", 'public');
            $filename = $originalName;
            $fileSizeKb = intdiv($originalSize, 1024);
        }

        $sudahPernahDiajukan = $spj->pernahDiajukan();
        $adaFileLama = $dokumen->status === 'sudah';
        $pathLama = $dokumen->file_path;

        $dokumen->update([
            'file_path' => $storagePath,
            'file_nama_asli' => $filename,
            'file_size_kb' => $fileSizeKb,
            'sumber_file' => $request->input('sumber_file'),
            'status' => 'sudah',
        ]);

        // File lama dihapus SETELAH data baru tersimpan, supaya kalau update gagal
        // dokumen lama tidak hilang. Gagal hapus tidak boleh menggagalkan upload.
        if ($pathLama && $pathLama !== $storagePath) {
            try {
                Storage::disk('public')->delete($pathLama);
            } catch (\Throwable $e) {
                \Log::warning('Gagal hapus file dokumen lama: ' . $e->getMessage(), ['path' => $pathLama]);
            }
        }

        // Hitung ulang status. Kalau SPJ baru naik ke 'proses_verifikasi'
        // (pengajuan pertama / pengajuan ulang setelah ditolak), admin diberi
        // notifikasi "diajukan". Kalau tidak, tapi SPJ sudah masuk alur
        // verifikasi, admin diberi tahu ada dokumen diunggah/diganti.
        $notifPengajuan = $spj->refreshStatusDanNotifikasi();

        if (! $notifPengajuan && $sudahPernahDiajukan) {
            $spj->notifyAdmins(new SpjDiperbarui(
                $spj,
                $adaFileLama ? 'ganti' : 'upload',
                $dokumen->dokumenWajib?->nama_dokumen ?? $filename
            ));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'dokumen_upload',
            'spj',
            "Upload dokumen '{$filename}' untuk SPJ: {$spj->nomor_spj}",
            $spj,
            [
                'nomor_spj' => $spj->nomor_spj,
                'file' => $filename,
                'sumber' => $request->input('sumber_file'),
                'size_kb' => $fileSizeKb,
            ],
            $request
        );

        return response()->json($dokumen->fresh());
    }

    public function destroy(Request $request, Spj $spj, SpjDokumen $dokumen)
    {
        abort_if($request->user()->isAdmin(), 403);
        abort_if($spj->dibuat_oleh !== $request->user()->id, 403);
        abort_if($dokumen->spj_id !== $spj->id, 404);
        abort_if($spj->terkunci(), 403, 'SPJ yang sudah terverifikasi tidak bisa diubah lagi. Hubungi admin.');

        $sudahPernahDiajukan = $spj->pernahDiajukan();
        $namaDokumen = $dokumen->dokumenWajib?->nama_dokumen ?? 'dokumen';

        if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
            Storage::disk('public')->delete($dokumen->file_path);
        }

        $dokumen->update([
            'file_path' => null,
            'file_nama_asli' => null,
            'file_size_kb' => null,
            'sumber_file' => null,
            'status' => 'belum',
        ]);

        // Dokumen wajib berkurang -> status turun lagi ke 'belum_lengkap'
        // (sebelumnya status tidak dihitung ulang setelah hapus).
        $spj->refreshStatus();

        if ($sudahPernahDiajukan) {
            $spj->notifyAdmins(new SpjDiperbarui($spj, 'hapus', $namaDokumen));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'dokumen_delete',
            'spj',
            "Hapus dokumen dari SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'dokumen_id' => $dokumen->id],
            $request
        );

        return response()->json(['message' => 'Dokumen dihapus, silakan upload ulang.']);
    }
}