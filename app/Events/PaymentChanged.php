<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $paymentId;
    public ?int $customerId;
    public array $orderIds;
    public string $amount;
    public string $action; // 'CREATED', 'VOIDED'
    public string $occurredAt;

    public function __construct(
        int $paymentId,
        ?int $customerId,
        array $orderIds,
        string $amount,
        string $action = 'CREATED'
    ) {
        $this->paymentId = $paymentId;
        $this->customerId = $customerId;
        $this->orderIds = $orderIds;
        $this->amount = $amount;
        $this->action = $action;
        $this->occurredAt = now()->toIso8601String();
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payments.operations'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PaymentChanged';
    }
}
