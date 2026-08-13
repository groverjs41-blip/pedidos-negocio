<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReturnableChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $customerId;
    public ?int $orderId;
    public string $movementType;
    public array $typeIds;
    public int $totalQuantity;
    public string $action; // 'CREATED', 'VOIDED'
    public string $occurredAt;

    public function __construct(
        int $customerId,
        ?int $orderId,
        string $movementType,
        array $typeIds,
        int $totalQuantity,
        string $action = 'CREATED'
    ) {
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->movementType = $movementType;
        $this->typeIds = $typeIds;
        $this->totalQuantity = $totalQuantity;
        $this->action = $action;
        $this->occurredAt = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('returnables.operations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ReturnableChanged';
    }
}
