<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Sinyal ringan "ada perubahan data SPJ".
 *
 * Sengaja TIDAK membawa data SPJ. Frontend cukup memuat ulang lewat API,
 * sehingga setiap user tetap hanya melihat SPJ yang memang boleh ia lihat.
 */
class SpjChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public string $action)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('spj-updates')];
    }

    public function broadcastAs(): string
    {
        return 'SpjChanged';
    }

    public function broadcastWith(): array
    {
        return ['action' => $this->action];
    }
}
