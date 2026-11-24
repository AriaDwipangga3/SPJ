<?php

namespace App\Notifications;

use App\Models\Spj;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke admin saat pembuat SPJ mengubah sesuatu pada SPJ yang
 * sudah pernah diajukan / pernah ditolak (upload, ganti, hapus dokumen,
 * ubah tanda tangan, atau edit data SPJ).
 */
class SpjDiperbarui extends Notification
{
    use Queueable;

    /**
     * @param string      $aksi   upload | ganti | hapus | ttd | edit
     * @param string|null $detail nama dokumen / keterangan singkat
     */
    public function __construct(
        public Spj $spj,
        public string $aksi,
        public ?string $detail = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $oleh = $this->spj->pembuat?->name ?? 'Pembuat SPJ';
        $nomor = $this->spj->nomor_spj;
        $detail = $this->detail ?? '';

        [$title, $message, $icon, $color] = match ($this->aksi) {
            'upload' => ['Dokumen Baru Diunggah', "{$oleh} mengunggah dokumen \"{$detail}\" pada SPJ {$nomor}.", 'cloud_upload', 'info'],
            'ganti' => ['Dokumen Diganti', "{$oleh} mengganti dokumen \"{$detail}\" pada SPJ {$nomor}.", 'swap_horiz', 'info'],
            'hapus' => ['Dokumen Dihapus', "{$oleh} menghapus dokumen \"{$detail}\" dari SPJ {$nomor}.", 'delete', 'warning'],
            'ttd' => ['Tanda Tangan Diperbarui', "{$oleh} memperbarui {$detail} pada SPJ {$nomor}.", 'draw', 'info'],
            default => ['Data SPJ Diubah', "{$oleh} mengubah data SPJ {$nomor}.", 'edit', 'info'],
        };

        return [
            'type' => 'spj_updated',
            'title' => $title,
            'message' => $message,
            'spj_id' => $this->spj->id,
            'nomor_spj' => $nomor,
            'url' => "/spj/{$this->spj->slug}",
            'icon' => $icon,
            'color' => $color,
        ];
    }
}
