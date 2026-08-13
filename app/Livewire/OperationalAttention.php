<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\On;
use Livewire\Component;

class OperationalAttention extends Component
{
    #[On('order-changed-realtime')]
    #[On('payment-changed-realtime')]
    public function refreshCounts(): void
    {
        // Triggers re-render on events
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
