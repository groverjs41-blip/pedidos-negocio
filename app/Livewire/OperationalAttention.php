<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OperationalNotificationPreferenceService;
use Livewire\Attributes\On;
use Livewire\Component;

class OperationalAttention extends Component
{
    public array $knownReadyOrderIds = [];
    public array $knownNewOrderIds = [];

    public function mount(): void
    {
        $this->knownReadyOrderIds = Order::where('status', OrderStatus::READY)
            ->pluck('id')
            ->toArray();

        $this->knownNewOrderIds = Order::where('status', OrderStatus::NEW)
            ->pluck('id')
            ->toArray();
    }

    #[On('order-changed-realtime')]
    #[On('payment-changed-realtime')]
    public function refreshCounts(): void
    {
        // Triggers re-render on events
    }

    /**
     * Global polling fallback respecting authenticated user's operational notification preferences.
     */
    public function refreshOperationalOrders(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        /** @var OperationalNotificationPreferenceService $prefService */
        $prefService = app(OperationalNotificationPreferenceService::class);

        // 1. Fallback for READY orders
        $readyOrders = Order::where('status', OrderStatus::READY)
            ->with(['items', 'customer'])
            ->orderBy('ready_at', 'asc')
            ->get();

        $currentReadyIds = $readyOrders->pluck('id')->toArray();
        $newReadyIds = array_diff($currentReadyIds, $this->knownReadyOrderIds);

        if (!empty($newReadyIds)) {
            $shouldReceive = $prefService->shouldReceiveInApp($user, 'READY');
            if ($shouldReceive) {
                $shouldSound = $prefService->shouldPlaySound($user, 'READY');
                $shouldBrowser = $prefService->shouldSendBrowser($user, 'READY');

                foreach ($readyOrders as $order) {
                    if (in_array($order->id, $newReadyIds)) {
                        $itemsSummary = $order->items->map(fn($i) => "{$i->quantity}x {$i->product_name}")->implode(', ');
                        $this->dispatch('operational-fallback-event', [
                            'orderId' => $order->id,
                            'orderNumber' => ltrim($order->number, '#'),
                            'action' => 'READY',
                            'soundType' => 'delivery',
                            'targetUserIds' => [$user->id],
                            'soundUserIds' => $shouldSound ? [$user->id] : [],
                            'browserUserIds' => $shouldBrowser ? [$user->id] : [],
                            'originUserId' => null,
                            'customerName' => $order->customer_name_snapshot ?? 'Cliente',
                            'itemsSummary' => $itemsSummary,
                        ]);
                    }
                }
            }
        }
        $this->knownReadyOrderIds = $currentReadyIds;

        // 2. Fallback for NEW orders (ORDER_CREATED)
        $newOrders = Order::where('status', OrderStatus::NEW)
            ->with(['items', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get();

        $currentNewIds = $newOrders->pluck('id')->toArray();
        $newCreatedIds = array_diff($currentNewIds, $this->knownNewOrderIds);

        if (!empty($newCreatedIds)) {
            $shouldReceive = $prefService->shouldReceiveInApp($user, 'ORDER_CREATED');
            if ($shouldReceive) {
                $shouldSound = $prefService->shouldPlaySound($user, 'ORDER_CREATED');
                $shouldBrowser = $prefService->shouldSendBrowser($user, 'ORDER_CREATED');

                foreach ($newOrders as $order) {
                    if (in_array($order->id, $newCreatedIds)) {
                        $itemsSummary = $order->items->map(fn($i) => "{$i->quantity}x {$i->product_name}")->implode(', ');
                        $this->dispatch('operational-fallback-event', [
                            'orderId' => $order->id,
                            'orderNumber' => ltrim($order->number, '#'),
                            'action' => 'ORDER_CREATED',
                            'soundType' => 'kitchen',
                            'targetUserIds' => [$user->id],
                            'soundUserIds' => $shouldSound ? [$user->id] : [],
                            'browserUserIds' => $shouldBrowser ? [$user->id] : [],
                            'originUserId' => null,
                            'customerName' => $order->customer_name_snapshot ?? 'Venta Mostrador',
                            'itemsSummary' => $itemsSummary,
                        ]);
                    }
                }
            }
        }
        $this->knownNewOrderIds = $currentNewIds;
    }

    public function render()
    {
        $user = auth()->user();
        if (!$user) {
            return view('livewire.operational-attention', [
                'totalCount' => 0,
                'kitchenOrders' => collect(),
                'deliveryOrders' => collect(),
                'cashierOrders' => collect(),
            ]);
        }

        $canKitchen = $user->hasRole('cocina') || $user->hasRole('admin');
        $canDelivery = $user->hasRole('reparto') || $user->hasRole('admin');
        $canCashier = $user->hasRole('caja') || $user->hasRole('admin');

        $kitchenOrders = $canKitchen
            ? Order::where('status', OrderStatus::NEW)->with('customer')->orderBy('created_at')->take(5)->get()
            : collect();

        $deliveryOrders = $canDelivery
            ? Order::where('status', OrderStatus::READY)->with('customer')->orderBy('updated_at')->take(5)->get()
            : collect();

        $cashierOrders = $canCashier
            ? Order::where('status', OrderStatus::DELIVERED)
                ->with(['customer', 'paymentAllocations.payment'])
                ->orderBy('updated_at')
                ->take(10)
                ->get()
                ->filter(fn($o) => $o->outstandingBalance() > 0)
                ->take(5)
            : collect();

        $totalCount = count($kitchenOrders) + count($deliveryOrders) + count($cashierOrders);

        return view('livewire.operational-attention', [
            'totalCount' => $totalCount,
            'kitchenOrders' => $kitchenOrders,
            'deliveryOrders' => $deliveryOrders,
            'cashierOrders' => $cashierOrders,
        ]);
    }
}
