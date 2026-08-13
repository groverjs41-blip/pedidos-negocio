<?php

namespace App\Livewire;

use App\Enums\ReturnableMovementType;
use App\Models\Customer;
use App\Models\ReturnableMovement;
use App\Services\ReturnableService;
use Livewire\Component;

class ReturnableDashboard extends Component
{
    public string $searchQuery = '';

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja', 'reparto'])) {
            abort(403, 'No tiene permiso para acceder al módulo de envases retornables.');
        }
    }

    public function render(ReturnableService $returnableService)
    {
        // 1. Envases Fuera (total outstanding units across all customers)
        $allCustomers = Customer::all();
        $totalOutstandingUnits = 0;
        $debtorCustomersCount = 0;

        foreach ($allCustomers as $cust) {
            $custTotal = $returnableService->getCustomerTotalOutstanding($cust);
            if ($custTotal > 0) {
                $totalOutstandingUnits += $custTotal;
                $debtorCustomersCount++;
            }
        }

        // 2. Recuperados hoy (sum RETURN today non-voided)
        $todayRecovered = ReturnableMovement::where('movement_type', ReturnableMovementType::RETURN)
            ->whereNull('voided_at')
            ->whereDate('occurred_at', today())
            ->sum('quantity');

        // 3. Salidas hoy (sum OUT today non-voided)
        $todayOut = ReturnableMovement::where('movement_type', ReturnableMovementType::OUT)
            ->whereNull('voided_at')
            ->whereDate('occurred_at', today())
            ->sum('quantity');

        // 4. Search Results
        $searchResults = collect();
        if (strlen(trim($this->searchQuery)) >= 2) {
            $query = trim($this->searchQuery);
            $searchResults = Customer::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })->take(15)->get();
        }

        return view('livewire.returnable-dashboard', [
            'totalOutstandingUnits' => $totalOutstandingUnits,
            'debtorCustomersCount' => $debtorCustomersCount,
            'todayRecovered' => $todayRecovered,
            'todayOut' => $todayOut,
            'searchResults' => $searchResults,
        ])->layout('layouts.app', ['title' => 'Envases Retornables - Pedidos Negocio']);
    }
}
