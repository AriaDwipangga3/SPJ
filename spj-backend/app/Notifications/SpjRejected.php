<?php

namespace App\Notifications;

use App\Models\Spj;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpjRejected extends Notification
{
    use Queueable;

    public function __construct(
        public Spj $spj,
        public string $catatan = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'spj_rejected',
            'title' => 'SPJ Perlu Revisi',
            'message' => "SPJ {$this->spj->nomor_spj} perlu diperbaiki.",
            'catatan' => $this->catatan,
            'spj_id' => $this->spj->id,
            'nomor_spj' => $this->spj->nomor_spj,
            'url' => "/spj/{$this->spj->slug}",
            'icon' => 'error_outline',
            'color' => 'warning',
        ];
    }
}