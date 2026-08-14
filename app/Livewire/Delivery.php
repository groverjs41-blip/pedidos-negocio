<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
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

    public function mount(): void
    {
        $this->knownReadyOrderIds = Order::where('status', OrderStatus::READY)
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
        return Order::where('status', OrderStatus::READY)
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('ready_at', 'asc')
            ->get();
    }

    public function getMyDeliveriesProperty()
    {
        return Order::where('status', OrderStatus::DELIVERING)
            ->where('delivery_user_id', auth()->id())
            ->with(['items', 'returnablePlans.returnableType'])
            ->orderBy('delivering_at', 'asc')
            ->get();
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

        $readyOrders = Order::where('status', OrderStatus::READY)
            ->with(['items'])
            ->orderBy('ready_at', 'asc')
            ->get();

        $currentIds = $readyOrders->pluck('id')->toArray();
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
