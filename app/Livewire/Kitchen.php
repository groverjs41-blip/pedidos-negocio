<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Models\Order;
use App\Services\OrderService;
use Livewire\Component;

class Kitchen extends Component
{
    public array $knownOrderIds = [];
    public array $selectedOrderIds = [];

    public function mount(): void
    {
        $this->knownOrderIds = Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->pluck('id')
            ->toArray();
    }

    /**
     * Listen to Echo OrderChanged broadcasts.
     */
    public function getListeners(): array
    {
        return [
            "echo-private:orders.operations,OrderChanged" => '$refresh',
        ];
    }

    /**
     * Get active kitchen orders (NEW or PREPARING), oldest first.
     */
    public function getOrdersProperty()
    {
        return Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('ordered_at', 'asc')
            ->get();
    }

    /**
     * Toggle selection for a NEW kitchen order.
     */
    public function toggleOrderSelection(int $orderId): void
    {
        if (in_array($orderId, $this->selectedOrderIds)) {
            $this->selectedOrderIds = array_values(array_diff($this->selectedOrderIds, [$orderId]));
        } else {
            $order = Order::where('id', $orderId)
                ->where('service_mode', ServiceMode::KITCHEN)
                ->where('status', OrderStatus::NEW)
                ->first();

            if ($order) {
                $this->selectedOrderIds[] = $orderId;
                $this->selectedOrderIds = array_values(array_unique($this->selectedOrderIds));
            }
        }
    }

    /**
     * Select all currently visible NEW KITCHEN orders.
     */
    public function selectAllNew(): void
    {
        $newIds = $this->orders
            ->filter(fn($o) => $o->status === OrderStatus::NEW)
            ->pluck('id')
            ->toArray();

        $this->selectedOrderIds = array_values(array_unique(array_merge($this->selectedOrderIds, $newIds)));
    }

    /**
     * Clear current batch selection.
     */
    public function clearSelection(): void
    {
        $this->selectedOrderIds = [];
    }

    /**
     * Calculate smart summary for currently selected NEW orders.
     */
    public function getBatchSummaryProperty(): array
    {
        $selectedIds = array_map('intval', $this->selectedOrderIds);
        if (empty($selectedIds)) {
            return [
                'count' => 0,
                'items' => [],
                'notes' => [],
                'oldest_order_time' => null,
            ];
        }

        $selectedOrders = Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::NEW)
            ->whereIn('id', $selectedIds)
            ->with(['items'])
            ->orderBy('ordered_at', 'asc')
            ->get();

        if ($selectedOrders->isEmpty()) {
            return [
                'count' => 0,
                'items' => [],
                'notes' => [],
                'oldest_order_time' => null,
            ];
        }

        $aggregatedItems = [];
        $notes = [];

        foreach ($selectedOrders as $order) {
            foreach ($order->items as $item) {
                $name = $item->product_name;
                $qty = (int) $item->quantity;
                $aggregatedItems[$name] = ($aggregatedItems[$name] ?? 0) + $qty;
            }

            if (!empty(trim($order->notes ?? ''))) {
                $notes[] = [
                    'number' => $order->number,
                    'note' => trim($order->notes),
                ];
            }
        }

        $itemsSummary = [];
        foreach ($aggregatedItems as $name => $quantity) {
            $itemsSummary[] = [
                'name' => $name,
                'quantity' => $quantity,
            ];
        }

        $oldestOrder = $selectedOrders->first();
        $oldestTime = $oldestOrder && $oldestOrder->ordered_at
            ? $oldestOrder->ordered_at->diffForHumans()
            : null;

        return [
            'count' => $selectedOrders->count(),
            'items' => $itemsSummary,
            'notes' => $notes,
            'oldest_order_time' => $oldestTime,
        ];
    }

    /**
     * Start batch preparation for selected orders.
     */
    public function startBatchPreparing(OrderService $orderService): void
    {
        if (empty($this->selectedOrderIds)) {
            $this->dispatch('notify-toast', type: 'warning', title: 'Sin Selección', message: 'Debe seleccionar al menos un pedido nuevo.');
            return;
        }

        try {
            $preparedOrders = $orderService->startPreparingBatch($this->selectedOrderIds, auth()->user());
            $count = $preparedOrders->count();
            $this->selectedOrderIds = [];
            $this->dispatch('notify-toast', type: 'info', title: 'Lote en Preparación', message: "Se inició la preparación de {$count} pedidos.");
        } catch (\InvalidArgumentException $e) {
            $this->selectedOrderIds = [];
            $this->dispatch('notify-toast', type: 'error', title: 'Lote no válido', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo iniciar el lote de preparación.');
        }
    }

    /**
     * Polling fallback method to detect new orders and dispatch operational sound events.
     * Uses named arguments for Livewire 4.
     */
    public function refreshOperationalOrders(): void
    {
        $user = auth()->user();
        if (!$user) return;

        /** @var \App\Services\OperationalNotificationPreferenceService $prefService */
        $prefService = app(\App\Services\OperationalNotificationPreferenceService::class);
        $shouldReceive = $prefService->shouldReceiveInApp($user, 'ORDER_CREATED');

        $currentOrders = Order::where('service_mode', ServiceMode::KITCHEN)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING])
            ->with(['items'])
            ->orderBy('ordered_at', 'asc')
            ->get();

        $currentIds = $currentOrders->pluck('id')->toArray();

        // Prune stale IDs from selection if they left NEW state
        $validNewIds = $currentOrders->filter(fn($o) => $o->status === OrderStatus::NEW)->pluck('id')->toArray();
        $this->selectedOrderIds = array_values(array_intersect($this->selectedOrderIds, $validNewIds));

        $newIds = array_diff($currentIds, $this->knownOrderIds);

        if (!empty($newIds) && $shouldReceive) {
            $shouldSound = $prefService->shouldPlaySound($user, 'ORDER_CREATED');
            $shouldBrowser = $prefService->shouldSendBrowser($user, 'ORDER_CREATED');

            foreach ($currentOrders as $order) {
                if (in_array($order->id, $newIds) && $order->status === OrderStatus::NEW) {
                    $itemsSummary = $order->items->map(fn($i) => "{$i->quantity}x {$i->product_name}")->implode(', ');
                    $this->dispatch(
                        'operational-fallback-event',
                        orderId: (string) $order->id,
                        orderNumber: ltrim($order->number, '#'),
                        action: 'ORDER_CREATED',
                        soundType: 'kitchen',
                        targetUserIds: [(int) $user->id],
                        soundUserIds: $shouldSound ? [(int) $user->id] : [],
                        browserUserIds: $shouldBrowser ? [(int) $user->id] : [],
                        originUserId: null,
                        customerName: $order->customer_name_snapshot ?? 'Venta Mostrador',
                        itemsSummary: $itemsSummary
                    );
                }
            }
        }

        $this->knownOrderIds = $currentIds;
    }

    /**
     * Transition NEW -> PREPARING.
     */
    public function startPreparingOrder(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->startPreparing($order, auth()->user());
            $this->selectedOrderIds = array_values(array_diff($this->selectedOrderIds, [$orderId]));
            $this->dispatch('notify-toast', type: 'info', title: 'En Preparación', message: "Pedido #" . ltrim($order->number, '#') . " en preparación.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo iniciar preparación del pedido.');
        }
    }

    /**
     * Transition PREPARING -> READY.
     */
    public function markOrderReady(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->markReady($order, auth()->user());
            $this->dispatch('notify-toast', type: 'success', title: 'Pedido Listo', message: "Pedido #" . ltrim($order->number, '#') . " listo para entrega.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo marcar el pedido como listo.');
        }
    }

    public function render()
    {
        $orders = $this->orders;
        $newCount = $orders->filter(fn($o) => $o->status === OrderStatus::NEW)->count();
        $preparingCount = $orders->filter(fn($o) => $o->status === OrderStatus::PREPARING)->count();

        return view('livewire.kitchen', [
            'orders' => $orders,
            'newCount' => $newCount,
            'preparingCount' => $preparingCount,
        ])->title('Cocina');
    }
}
