<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResponsibleChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public string $action;   // 'created' | 'updated' | 'deleted'
    public ?int $id;

    public function __construct(string $action, ?int $id = null)
    {
        $this->action = $action;
        $this->id = $id;
    }

    public function broadcastOn(): array
    {
        return [new Channel('responsible-updates')];
    }

    public function broadcastAs(): string
    {
        return 'ResponsibleChanged';
    }
}