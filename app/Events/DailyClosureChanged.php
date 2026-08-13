<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyClosureChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $businessDate;
    public string $closedAt;
    public int $closedById;
    public bool $forced;
    public string $action; // 'CLOSED'

    public function __construct(
        string $businessDate,
        string $closedAt,
        int $closedById,
        bool $forced = false,
        string $action = 'CLOSED'
    ) {
        $this->businessDate = $businessDate;
        $this->closedAt = $closedAt;
        $this->closedById = $closedById;
        $this->forced = $forced;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('closures.operations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'DailyClosureChanged';
    }
}
