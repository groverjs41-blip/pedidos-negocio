<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class OrderChanged implements ShouldBroadcastNow
{
    use SerializesModels;

    public Order $order;
    public string $orderId;
    public string $orderNumber;
    public string $status;
    public ?string $previousStatus;
    public string $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?string $previousStatus = null)
    {
        $this->order = $order;
        $this->orderId = (string) $order->id;
        $this->orderNumber = $order->number;
        $this->status = $order->status->value;
        $this->previousStatus = $previousStatus;
        $this->occurredAt = now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.operations'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'orderId' => $this->orderId,
            'orderNumber' => $this->orderNumber,
            'status' => $this->status,
            'previousStatus' => $this->previousStatus,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
