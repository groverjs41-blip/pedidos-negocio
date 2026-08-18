<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Models\Order;
use App\Models\ReturnableType;
use App\Services\OrderService;
use App\Services\ReturnableService;
use Illuminate\Support\Str;
use Livewire\Component;

class Delivery extends Component
{
    // Post-delivery Returnables Prompt State
    public bool $showReturnablePrompt = false;
    public ?Order $promptOrder = null;
    public array $outQuantities = []; // [type_id => qty]
    public string $batchToken = '';

    public array $knownReadyOrderIds = [];
    public array $selectedOrderIds = [];

    public function mount(): void
    {
        $this->knownReadyOrderIds = Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::READY)
            ->pluck('id')
            ->toArray();
    }

    public function getListeners(): array
    {
        return [
            "echo-private:orders.operations,OrderChanged" => '$refresh',
        ];
    }

    public function getReadyOrdersProperty()
    {
        return Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::READY)
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('ready_at', 'asc')
            ->get();
    }

    public function getMyDeliveriesProperty()
    {
        return Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::DELIVERING)
            ->where('delivery_user_id', auth()->id())
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('delivering_at', 'asc')
            ->orderBy('ready_at', 'asc')
            ->get();
    }

    /**
     * Toggle selection for a READY KITCHEN order.
     */
    public function toggleOrderSelection(int $orderId): void
    {
        if (in_array($orderId, $this->selectedOrderIds)) {
            $this->selectedOrderIds = array_values(array_diff($this->selectedOrderIds, [$orderId]));
        } else {
            $order = Order::where('id', $orderId)
                ->where('service_mode', ServiceMode::KITCHEN)
                ->where('status', OrderStatus::READY)
                ->first();

            if ($order) {
                $this->selectedOrderIds[] = $orderId;
                $this->selectedOrderIds = array_values(array_unique($this->selectedOrderIds));
            }
        }
    }

    /**
     * Select all currently visible READY KITCHEN orders.
     */
    public function selectAllReady(): void
    {
        $readyIds = $this->readyOrders->pluck('id')->toArray();
        $this->selectedOrderIds = array_values(array_unique(array_merge($this->selectedOrderIds, $readyIds)));
    }

    /**
     * Clear current batch selection.
     */
    public function clearSelection(): void
    {
        $this->selectedOrderIds = [];
    }

    /**
     * Calculate smart summary for currently selected READY orders.
     */
    public function getBatchSummaryProperty(): array
    {
        $selectedIds = array_map('intval', $this->selectedOrderIds);
        if (empty($selectedIds)) {
            return [
                'count' => 0,
                'total_amount' => '0.00',
                'orders' => [],
                'has_any_returnables' => false,
            ];
        }

        $selectedOrders = Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::READY)
            ->whereIn('id', $selectedIds)
            ->with(['returnablePlans'])
            ->orderBy('ready_at', 'asc')
            ->get();

        if ($selectedOrders->isEmpty()) {
            return [
                'count' => 0,
                'total_amount' => '0.00',
                'orders' => [],
                'has_any_returnables' => false,
            ];
        }

        $total = '0.00';
        $orderList = [];
        $hasReturnables = false;

        foreach ($selectedOrders as $order) {
            $total = bcadd($total, (string) $order->total, 2);
            $hasPlans = $order->returnablePlans && $order->returnablePlans->count() > 0;
            if ($hasPlans) {
                $hasReturnables = true;
            }

            $orderList[] = [
                'id' => $order->id,
                'number' => $order->number,
                'customer' => $order->customer_name_snapshot ?? 'Cliente',
                'address' => $order->delivery_address_snapshot ?? 'Sin dirección',
                'total' => $order->total,
                'has_returnables' => $hasPlans,
            ];
        }

        return [
            'count' => $selectedOrders->count(),
            'total_amount' => number_format((float) $total, 2, '.', ''),
            'orders' => $orderList,
            'has_any_returnables' => $hasReturnables,
        ];
    }

    /**
     * Summary of current driver's active delivery run (delivering orders).
     */
    public function getMyDeliverySummaryProperty(): array
    {
        $deliveries = $this->myDeliveries;
        $total = '0.00';

        foreach ($deliveries as $order) {
            $total = bcadd($total, (string) $order->total, 2);
        }

        return [
            'count' => $deliveries->count(),
            'total_pending' => number_format((float) $total, 2, '.', ''),
        ];
    }

    /**
     * Claim batch of READY orders for delivery.
     */
    public function claimDeliveryBatch(OrderService $orderService): void
    {
        if (empty($this->selectedOrderIds)) {
            $this->dispatch('notify-toast', type: 'warning', title: 'Sin Selección', message: 'Debe seleccionar al menos un pedido listo.');
            return;
        }

        try {
            $claimedOrders = $orderService->claimForDeliveryBatch($this->selectedOrderIds, auth()->user());
            $count = $claimedOrders->count();
            $this->selectedOrderIds = [];
            $this->dispatch('notify-toast', type: 'info', title: 'Salida Iniciada', message: "Se inició la salida con {$count} pedidos.");
        } catch (\InvalidArgumentException $e) {
            $this->selectedOrderIds = [];
            $this->dispatch('notify-toast', type: 'error', title: 'Error de asignación', message: $e->getMessage());
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo asignar la salida.');
        }
    }

    /**
     * Polling fallback method to detect new ready orders and dispatch delivery sound events.
     * Uses named arguments for Livewire 4.
     */
    public function refreshOperationalOrders(): void
    {
        $user = auth()->user();
        if (!$user) return;

        /** @var \App\Services\OperationalNotificationPreferenceService $prefService */
        $prefService = app(\App\Services\OperationalNotificationPreferenceService::class);
        $shouldReceive = $prefService->shouldReceiveInApp($user, 'READY');

        $readyOrders = Order::where('service_mode', ServiceMode::KITCHEN)
            ->where('status', OrderStatus::READY)
            ->with(['items'])
            ->orderBy('ready_at', 'asc')
            ->get();

        $currentIds = $readyOrders->pluck('id')->toArray();

        // Prune stale IDs from selection if they left READY state
        $this->selectedOrderIds = array_values(array_intersect($this->selectedOrderIds, $currentIds));

        $newIds = array_diff($currentIds, $this->knownReadyOrderIds);

        if (!empty($newIds) && $shouldReceive) {
            $shouldSound = $prefService->shouldPlaySound($user, 'READY');
            $shouldBrowser = $prefService->shouldSendBrowser($user, 'READY');

            foreach ($readyOrders as $order) {
                if (in_array($order->id, $newIds)) {
                    $this->dispatch(
                        'operational-fallback-event',
                        orderId: (string) $order->id,
                        orderNumber: ltrim($order->number, '#'),
                        action: 'READY',
                        soundType: 'delivery',
                        targetUserIds: [(int) $user->id],
                        soundUserIds: $shouldSound ? [(int) $user->id] : [],
                        browserUserIds: $shouldBrowser ? [(int) $user->id] : [],
                        originUserId: null,
                        customerName: $order->customer_name_snapshot ?? 'Cliente'
                    );
                }
            }
        }

        $this->knownReadyOrderIds = $currentIds;
    }

    public function claimOrder(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->claimForDelivery($order, auth()->user());
            $this->selectedOrderIds = array_values(array_diff($this->selectedOrderIds, [$orderId]));
            $this->dispatch('notify-toast', type: 'info', title: 'Pedido Tomado', message: "Pedido #" . ltrim($order->number, '#') . " asignado a tus entregas.");
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error de asignación', message: 'El pedido ya fue tomado o no se pudo asignar.');
        }
    }

    public function markOrderDelivered(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error', message: 'El pedido no existe.');
            return;
        }

        try {
            $orderService->markDelivered($order, auth()->user());
            $this->dispatch('notify-toast', type: 'success', title: 'Entregado', message: "Pedido #" . ltrim($order->number, '#') . " marcado como entregado.");

            if ($order->customer_id) {
                $this->promptOrder = $order;
                $this->batchToken = (string) Str::uuid();
                $this->outQuantities = [];
                $activeTypes = ReturnableType::where('active', true)->orderBy('sort_order', 'asc')->get();
                foreach ($activeTypes as $t) {
                    $this->outQuantities[$t->id] = 0;
                }
                $orderPlans = $order->returnablePlans()->pluck('quantity', 'returnable_type_id')->toArray();
                foreach ($orderPlans as $typeId => $planQty) {
                    if (isset($this->outQuantities[$typeId])) {
                        $this->outQuantities[$typeId] = $planQty;
                    }
                }
                $this->showReturnablePrompt = true;
            }
        } catch (\Exception $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error operativo', message: 'No se pudo marcar la entrega del pedido.');
        }
    }

    public function closePrompt()
    {
        $this->showReturnablePrompt = false;
        $this->promptOrder = null;
        $this->outQuantities = [];
    }

    public function registerLeftContainers(ReturnableService $returnableService)
    {
        if (!$this->promptOrder || !$this->promptOrder->customer) {
            $this->closePrompt();
            return;
        }

        $items = [];
        foreach ($this->outQuantities as $typeId => $qty) {
            $q = (int) $qty;
            if ($q > 0) {
                $items[] = [
                    'returnable_type_id' => (int) $typeId,
                    'quantity' => $q,
                ];
            }
        }

        if (empty($items)) {
            $this->closePrompt();
            return;
        }

        try {
            $returnableService->recordOutBatch(
                $this->promptOrder->customer,
                $items,
                auth()->user(),
                $this->batchToken,
                $this->promptOrder,
                'Envases dejados en entrega del pedido ' . $this->promptOrder->number
            );

            $this->dispatch('notify-toast', type: 'success', title: 'Envases Registrados', message: 'Envases dejados registrados correctamente para el cliente.');
            $this->closePrompt();
        } catch (\Throwable $e) {
            $this->dispatch('notify-toast', type: 'error', title: 'Error de envases', message: 'No se pudo registrar la entrega de envases.');
        }
    }

    public function render()
    {
        $activeReturnableTypes = ReturnableType::where('active', true)->orderBy('sort_order', 'asc')->get();

        return view('livewire.delivery', [
            'readyOrders' => $this->readyOrders,
            'myDeliveries' => $this->myDeliveries,
            'activeReturnableTypes' => $activeReturnableTypes,
        ])->title('Reparto');
    }
}
