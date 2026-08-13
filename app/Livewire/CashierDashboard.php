<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Component;

class CashierDashboard extends Component
{
    public string $searchQuery = '';

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            abort(403, 'No tiene permiso para acceder a Cobranza.');
        }
    }

    public function render()
    {
        // 1. Cobrado Hoy (non-voided payments paid today)
        $todayPayments = Payment::whereNull('voided_at')
            ->whereDate('paid_at', today())
            ->get();

        $todayCollected = '0.00';
        foreach ($todayPayments as $p) {
            $todayCollected = bcadd($todayCollected, number_format((float)$p->amount, 2, '.', ''), 2);
        }

        // 2. Saldo Pendiente (sum of balances of DELIVERED orders)
        $deliveredOrders = Order::where('status', OrderStatus::DELIVERED)->get();
        $totalOutstanding = '0.00';
        $unpaidDeliveredCount = 0;

        foreach ($deliveredOrders as $ord) {
            $bal = $ord->outstandingBalance();
            if (bccomp($bal, '0.00', 2) > 0) {
                $totalOutstanding = bcadd($totalOutstanding, $bal, 2);
                $unpaidDeliveredCount++;
            }
        }

        // 3. Clientes con Deuda (customers with DELIVERED outstanding balance > 0)
        $debtorCustomerIds = [];
        $customersWithOrders = Customer::with(['orders' => function ($q) {
            $q->where('status', OrderStatus::DELIVERED);
        }])->get();

        foreach ($customersWithOrders as $cust) {
            if (bccomp($cust->outstandingBalance(), '0.00', 2) > 0) {
                $debtorCustomerIds[] = $cust->id;
            }
        }

        // 4. Search Results
        $searchResults = collect();
        if (strlen(trim($this->searchQuery)) >= 2) {
            $query = trim($this->searchQuery);
            $searchResults = Customer::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })->take(15)->get();
        }

        return view('livewire.cashier-dashboard', [
            'todayCollected' => $todayCollected,
            'totalOutstanding' => $totalOutstanding,
            'debtorsCount' => count($debtorCustomerIds),
            'unpaidDeliveredCount' => $unpaidDeliveredCount,
            'searchResults' => $searchResults,
        ])->layout('layouts.app', ['title' => 'Cobranza - Pedidos Negocio']);
    }
}
