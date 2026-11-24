<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use App\Models\SpjDokumen;
use Illuminate\Support\Facades\Storage;

/**
 * Menyajikan file dokumen & tanda tangan SPJ.
 *
 * File TIDAK lagi dibuka lewat /storage/... (itu bisa diakses siapa saja tanpa login).
 * Aplikasi memberi URL bertanda tangan berumur pendek (lihat accessor file_url dan
 * ttd_pihakN_url di model), dan route ini memakai middleware `signed`, jadi URL yang
 * tidak valid atau sudah kedaluwarsa ditolak 403.
 */
class BerkasController extends Controller
{
    public function dokumen(SpjDokumen $dokumen)
    {
        return $this->kirim($dokumen->file_path, $dokumen->file_nama_asli);
    }

    public function ttd(Spj $spj, string $pihak)
    {
        abort_unless(in_array($pihak, ['1', '2'], true), 404);

        return $this->kirim($spj->{"ttd_pihak{$pihak}_path"}, "ttd-pihak{$pihak}.png");
    }

    private function kirim(?string $path, ?string $nama)
    {
        // Hanya file di bawah folder spj/ yang boleh disajikan.
        abort_if(! $path || ! str_starts_with($path, 'spj/') || str_contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404, 'File tidak ditemukan.');

        return $disk->response($path, $nama ?: basename($path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
