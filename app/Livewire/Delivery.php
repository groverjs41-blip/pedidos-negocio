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
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    // Post-delivery Returnables Prompt State
    public bool $showReturnablePrompt = false;
    public ?Order $promptOrder = null;
    public array $outQuantities = []; // [type_id => qty]
    public string $batchToken = '';

    public function getListeners(): array
    {
        return [
            "echo-private:orders.operations,OrderChanged" => '$refresh',
        ];
    }

    public function getReadyOrdersProperty()
    {
        return Order::where('status', OrderStatus::READY)
            ->with(['items'])
            ->orderBy('ready_at', 'asc')
            ->get();
    }

    public function getMyDeliveriesProperty()
    {
        return Order::where('status', OrderStatus::DELIVERING)
            ->where('delivery_user_id', auth()->id())
            ->with(['items'])
            ->orderBy('delivering_at', 'asc')
            ->get();
    }

    public function claimOrder(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->claimForDelivery($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} asignado a tus entregas.";
            $this->errorMessage = null;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function markOrderDelivered(int $orderId, OrderService $orderService): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            $this->errorMessage = 'El pedido no existe.';
            return;
        }

        try {
            $orderService->markDelivered($order, auth()->user());
            $this->successMessage = "Pedido {$order->number} marcado como entregado.";
            $this->errorMessage = null;

            // Prompt if order has a customer
            if ($order->customer_id) {
                $this->promptOrder = $order;
                $this->batchToken = (string) Str::uuid();
                $this->outQuantities = [];
                $activeTypes = ReturnableType::where('active', true)->orderBy('sort_order', 'asc')->get();
                foreach ($activeTypes as $t) {
                    $this->outQuantities[$t->id] = 0;
                }
                $this->showReturnablePrompt = true;
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
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

            $this->successMessage = 'Envases dejados registrados correctamente para el cliente.';
            $this->closePrompt();
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
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
