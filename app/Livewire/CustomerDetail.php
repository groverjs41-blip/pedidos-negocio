<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ReturnableService;
use Livewire\Component;

class CustomerDetail extends Component
{
    public Customer $customer;

    public function mount(Customer $customer)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para ver detalles del cliente.');
        }

        $this->customer = $customer;
    }

    public function render(ReturnableService $returnableService)
    {
        $debt = $this->customer->outstandingBalance();
        $containerSummary = $returnableService->getCustomerBalances($this->customer);

        $recentOrders = Order::where('customer_id', $this->customer->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $recentPayments = Payment::where('customer_id', $this->customer->id)
            ->whereNull('voided_at')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('livewire.customer-detail', [
            'debt' => $debt,
            'containerSummary' => $containerSummary,
            'recentOrders' => $recentOrders,
            'recentPayments' => $recentPayments,
        ])->layout('layouts.app', ['title' => 'Detalle de Cliente - Pedidos Negocio']);
    }
}
