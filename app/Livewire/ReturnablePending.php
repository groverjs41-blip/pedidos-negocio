<?php

namespace App\Livewire;

use App\Services\ReturnableService;
use Livewire\Component;

class ReturnablePending extends Component
{
    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja', 'reparto'])) {
            abort(403, 'No tiene permiso para acceder a envases por recoger.');
        }
    }

    public function render(ReturnableService $returnableService)
    {
        $outstandingCustomers = $returnableService->getOutstandingCustomers();

        return view('livewire.returnable-pending', [
            'customers' => $outstandingCustomers,
            'returnableService' => $returnableService,
        ])->layout('layouts.app', ['title' => 'Envases por Recoger - Pedidos Negocio']);
    }
}
