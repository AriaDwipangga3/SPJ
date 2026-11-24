<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use App\Notifications\SpjDiperbarui;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SignatureController extends Controller
{
    public function store(Request $request, Spj $spj, string $pihak)
    {
        abort_if($request->user()->isAdmin(), 403, 'Admin tidak bisa menandatangani SPJ.');
        abort_if($spj->dibuat_oleh !== $request->user()->id, 403, 'Anda bukan pemilik SPJ ini.');
        abort_unless(in_array($pihak, ['1', '2']), 404);
        abort_if($spj->terkunci(), 403, 'SPJ yang sudah terverifikasi tidak bisa diubah lagi. Hubungi admin.');

        $data = $request->validate([
            'metode' => ['required', 'in:gambar,upload'],
            'signature_base64' => ['required_if:metode,gambar', 'string', 'nullable', 'max:3000000'],
            'file' => ['required_if:metode,upload', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'signature_base64.max' => 'Data tanda tangan terlalu besar.',
            'file.mimes' => 'Foto tanda tangan harus berformat PNG atau JPG.',
            'file.max' => 'Ukuran foto tanda tangan maksimal 2 MB.',
            'file.uploaded' => 'Upload gagal. Ukuran file kemungkinan melebihi batas server.',
        ]);

        if ($data['metode'] === 'gambar') {
            $path = $this->simpanBase64($request->input('signature_base64'), $spj->id, $pihak);
        } else {
            $path = $request->file('file')->store("spj/{$spj->id}/ttd", 'public');
        }

        $sudahPernahDiajukan = $spj->pernahDiajukan();
        $pathLama = $spj->{"ttd_pihak{$pihak}_path"};
        $adaTtdLama = ! is_null($pathLama);

        $spj->update([
            "ttd_pihak{$pihak}_path" => $path,
            "ttd_pihak{$pihak}_metode" => $data['metode'],
            "ttd_pihak{$pihak}_at" => now(),
        ]);

        // Hapus file TTD lama setelah yang baru tersimpan. Gagal hapus tidak
        // boleh menggagalkan simpan TTD.
        if ($pathLama && $pathLama !== $path) {
            try {
                Storage::disk('public')->delete($pathLama);
            } catch (\Throwable $e) {
                \Log::warning('Gagal hapus file TTD lama: ' . $e->getMessage(), ['path' => $pathLama]);
            }
        }

        // Naik ke 'proses_verifikasi' -> admin diberi notifikasi pengajuan
        // (termasuk pengajuan ulang setelah ditolak).
        $notifPengajuan = $spj->refreshStatusDanNotifikasi();

        if (! $notifPengajuan && $sudahPernahDiajukan) {
            $spj->notifyAdmins(new SpjDiperbarui(
                $spj,
                'ttd',
                ($adaTtdLama ? 'tanda tangan Pihak ' : 'tanda tangan baru Pihak ') . $pihak
            ));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'ttd_save',
            'spj',
            "Simpan TTD Pihak {$pihak} untuk SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'pihak' => $pihak, 'metode' => $data['metode']],
            $request
        );

        return response()->json($spj->fresh([
            'jenisSpj',
            'pihak1',
            'pihak2',
            'dokumen.dokumenWajib',
        ]));
    }

    private function simpanBase64(string $base64, int $spjId, string $pihak): string
    {
        $encoded = $base64;
        if (str_contains($base64, ',')) {
            [, $encoded] = explode(',', $base64, 2);
        }

        // Decode ketat, lalu pastikan hasilnya benar-benar gambar PNG dengan ukuran wajar.
        $binary = base64_decode($encoded, true);
        $info = $binary !== false ? @getimagesizefromstring($binary) : false;

        if (
            $binary === false
            || $info === false
            || $info[2] !== IMAGETYPE_PNG
            || strlen($binary) > 2 * 1024 * 1024
            || $info[0] > 4000
            || $info[1] > 4000
        ) {
            throw ValidationException::withMessages([
                'signature_base64' => ['Data tanda tangan tidak valid. Gunakan gambar PNG maksimal 2 MB.'],
            ]);
        }

        $filename = "spj/{$spjId}/ttd/pihak{$pihak}-" . Str::random(8) . '.png';

        Storage::disk('public')->put($filename, $binary);

        return $filename;
    }
}