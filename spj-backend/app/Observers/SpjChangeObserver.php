<?php

namespace App\Observers;

use App\Events\SpjChanged;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Dipasang di model Spj dan SpjDokumen.
 * Setiap ada perubahan, kirim sinyal realtime ke frontend.
 *
 * ShouldHandleEventsAfterCommit: sinyal baru dikirim setelah transaksi DB
 * selesai, supaya frontend tidak memuat ulang data yang belum tersimpan.
 */
class SpjChangeObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->kirim('created');
    }

    public function updated(Model $model): void
    {
        $this->kirim('updated');
    }

    public function deleted(Model $model): void
    {
        $this->kirim('deleted');
    }

    private function kirim(string $action): void
    {
        try {
            broadcast(new SpjChanged($action));
        } catch (\Throwable $e) {
            // Realtime hanya pelengkap. Kalau Reverb mati, simpan data tetap harus berhasil.
            Log::warning('Broadcast SpjChanged gagal (Reverb mati?): ' . $e->getMessage());
        }
    }
}
