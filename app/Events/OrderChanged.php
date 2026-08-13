<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class OrderChanged implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public Order $order;
    public string $orderId;
    public string $orderNumber;
    public string $status;
    public ?string $previousStatus;
    public string $action;
    public ?string $soundType;
    public array $targetRoles;
    public array $targetUserIds;
    public array $soundUserIds;
    public array $browserUserIds;
    public ?int $originUserId;
    public string $customerName;
    public string $itemsSummary;
    public string $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Order $order,
        ?string $previousStatus = null,
        string $action = 'STATUS_CHANGED',
        ?string $soundType = null,
        array $targetRoles = [],
        array $targetUserIds = [],
        array $soundUserIds = [],
        array $browserUserIds = [],
        ?int $originUserId = null
    ) {
        $this->order = $order;
        $this->orderId = (string) $order->id;
        $this->orderNumber = $order->number;
        $this->status = $order->status->value;
        $this->previousStatus = $previousStatus;
        $this->action = $action;
        $this->soundType = $soundType;
        $this->targetRoles = $targetRoles;
        $this->targetUserIds = $targetUserIds;
        $this->soundUserIds = $soundUserIds;
        $this->browserUserIds = $browserUserIds;
        $this->originUserId = $originUserId ?? auth()->id();
        $this->customerName = $order->customer_name_snapshot ?? 'Venta Mostrador';
        
        $summaryParts = [];
        if ($order->relationLoaded('items') || $order->items()->exists()) {
            foreach ($order->items as $item) {
                $summaryParts[] = "{$item->quantity} × {$item->product_name}";
            }
        }
        $this->itemsSummary = implode(', ', $summaryParts);
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
            'action' => $this->action,
            'soundType' => $this->soundType,
            'targetRoles' => $this->targetRoles,
            'targetUserIds' => $this->targetUserIds,
            'soundUserIds' => $this->soundUserIds,
            'browserUserIds' => $this->browserUserIds,
            'originUserId' => $this->originUserId,
            'customerName' => $this->customerName,
            'itemsSummary' => $this->itemsSummary,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
