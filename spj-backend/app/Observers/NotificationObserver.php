<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

class NotificationObserver
{
    public function created(DatabaseNotification $notification): void
    {
        // notifiable_id = user_id penerima
        $userId = $notification->notifiable_id;

        try {
            broadcast(new NotificationCreated((int) $userId, [
                'id' => $notification->id,
                'type' => $notification->data['type'] ?? 'info',
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'message' => $notification->data['message'] ?? '',
                'catatan' => $notification->data['catatan'] ?? null,
                'spj_id' => $notification->data['spj_id'] ?? null,
                'nomor_spj' => $notification->data['nomor_spj'] ?? null,
                'url' => $notification->data['url'] ?? null,
                'icon' => $notification->data['icon'] ?? 'notifications',
                'color' => $notification->data['color'] ?? 'info',
                'created_at_human' => $notification->created_at->diffForHumans(),
                'read' => false,
            ]));
        } catch (\Throwable $e) {
            // Realtime hanya pelengkap. Notifikasi sudah tersimpan di database
            // (muncul lewat polling 30 detik) dan aksi utama harus tetap berhasil.
            Log::warning('Broadcast NotificationCreated gagal (Reverb mati?): ' . $e->getMessage());
        }
    }
}
