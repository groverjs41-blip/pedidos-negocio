<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Services\ReturnableService;
use Livewire\Component;
use Livewire\WithPagination;

class ManageCustomers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $activeFilter = '';
    public ?string $successMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'No tiene permiso para gestionar clientes.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActiveFilter()
    {
        $this->resetPage();
    }

    public function toggleActive(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);
        $customer->update(['active' => !$customer->active]);
        $this->successMessage = "Estado del cliente '{$customer->name}' actualizado correctamente.";
    }

    public function render(ReturnableService $returnableService)
    {
        $query = Customer::orderBy('name', 'asc');

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('phone', 'like', $s);
            });
        }

        if ($this->activeFilter !== '') {
            $query->where('active', (bool)$this->activeFilter);
        }

        $customers = $query->paginate(15);

        // Precalculate balance & containers
        $balances = [];
        $containers = [];
        foreach ($customers as $c) {
            $balances[$c->id] = $c->outstandingBalance();
            $containers[$c->id] = $returnableService->getCustomerSummary($c);
        }

        return view('livewire.manage-customers', [
            'customers' => $customers,
            'balances' => $balances,
            'containers' => $containers,
        ])->layout('layouts.app', ['title' => 'Gestión de Clientes - Pedidos Negocio']);
    }
}
