<?php

namespace App\Notifications;

use App\Models\Spj;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpjSubmittedForVerification extends Notification
{
    use Queueable;

    public function __construct(public Spj $spj, public bool $ulang = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $nomor = $this->spj->nomor_spj;

        return [
            'type' => $this->ulang ? 'spj_resubmitted' : 'spj_submitted',
            'title' => $this->ulang ? 'SPJ Diajukan Kembali' : 'SPJ Baru Siap Diverifikasi',
            'message' => $this->ulang
                ? "SPJ {$nomor} sudah diperbaiki dan diajukan kembali untuk diverifikasi."
                : "SPJ {$nomor} telah dilengkapi dan siap diverifikasi.",
            'spj_id' => $this->spj->id,
            'nomor_spj' => $this->spj->nomor_spj,
            'url' => "/spj/{$this->spj->slug}",
            'icon' => $this->ulang ? 'refresh' : 'verified_user',
            'color' => 'info',
        ];
    }
}