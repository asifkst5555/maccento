<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CloudImportProgressEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $sessionUuid;
    public array $metrics;

    public function __construct(string $sessionUuid, array $metrics)
    {
        $this->sessionUuid = $sessionUuid;
        $this->metrics = $metrics;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('import-session.' . $this->sessionUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress.updated';
    }
}
