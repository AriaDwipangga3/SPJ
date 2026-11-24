<?php

namespace App\Observers;

use App\Events\ResponsibleChanged;
use App\Models\Responsible;
use Illuminate\Support\Facades\Log;

class ResponsibleObserver
{
    public function created(Responsible $responsible): void
    {
        $this->kirim('created', $responsible);
    }

    public function updated(Responsible $responsible): void
    {
        $this->kirim('updated', $responsible);
    }

    public function deleted(Responsible $responsible): void
    {
        $this->kirim('deleted', $responsible);
    }

    private function kirim(string $action, Responsible $responsible): void
    {
        try {
            broadcast(new ResponsibleChanged($action, $this->slugOf($responsible)));
        } catch (\Throwable $e) {
            // Realtime hanya pelengkap. Kalau Reverb mati, simpan data tetap harus berhasil.
            Log::warning('Broadcast ResponsibleChanged gagal (Reverb mati?): ' . $e->getMessage());
        }
    }

    private function slugOf(Responsible $responsible): ?string
    {
        // pakai slug kalau ada, fallback ke id sebagai string
        return $responsible->slug ?? (string) $responsible->id;
    }
}
