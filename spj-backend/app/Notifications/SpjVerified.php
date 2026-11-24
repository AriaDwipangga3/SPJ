<?php

namespace App\Notifications;

use App\Models\Spj;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpjVerified extends Notification
{
    use Queueable;

    public function __construct(public Spj $spj) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'spj_verified',
            'title' => 'SPJ Terverifikasi',
            'message' => "Selamat! SPJ {$this->spj->nomor_spj} telah diverifikasi.",
            'spj_id' => $this->spj->id,
            'nomor_spj' => $this->spj->nomor_spj,
            'url' => "/spj/{$this->spj->slug}",
            'icon' => 'check_circle',
            'color' => 'success',
        ];
    }
}