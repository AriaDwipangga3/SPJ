<?php

namespace App\Console\Commands;

use App\Models\Spj;
use App\Models\SpjDokumen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BersihkanFileYatim extends Command
{
    protected $signature = 'spj:bersihkan-file-yatim {--hapus : Benar-benar hapus (tanpa opsi ini hanya menampilkan daftar)}';

    protected $description = 'Cari (dan hapus) file di storage/app/public/spj yang tidak dipakai SPJ mana pun';

    public function handle(): int
    {
        $disk = Storage::disk('public');

        // Kumpulkan semua path yang masih dipakai.
        $dipakai = SpjDokumen::whereNotNull('file_path')->pluck('file_path')
            ->merge(Spj::whereNotNull('ttd_pihak1_path')->pluck('ttd_pihak1_path'))
            ->merge(Spj::whereNotNull('ttd_pihak2_path')->pluck('ttd_pihak2_path'))
            ->flip();

        $yatim = collect($disk->allFiles('spj'))
            ->reject(fn ($path) => $dipakai->has($path))
            ->values();

        if ($yatim->isEmpty()) {
            $this->info('Tidak ada file yatim.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($yatim as $path) {
            $total += $disk->size($path);
            $this->line($path);
        }

        $ukuran = round($total / 1024 / 1024, 2);
        $this->info("{$yatim->count()} file yatim ({$ukuran} MB).");

        if (! $this->option('hapus')) {
            $this->warn('Belum ada yang dihapus. Jalankan lagi dengan --hapus untuk menghapusnya.');

            return self::SUCCESS;
        }

        foreach ($yatim as $path) {
            $disk->delete($path);
        }
        $this->info('Selesai, file yatim dihapus.');

        return self::SUCCESS;
    }
}
