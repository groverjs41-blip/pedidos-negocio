<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\ReturnableMovement;
use App\Models\ReturnableType;
use App\Services\ReturnableService;
use Illuminate\Support\Str;
use Livewire\Component;

class CustomerReturnables extends Component
{
    public Customer $customer;

    // Return Modal State
    public bool $showReturnModal = false;
    public array $returnQuantities = []; // [type_id => qty]
    public string $returnNotes = '';
    public string $batchToken = '';

    // Void Modal State
    public bool $showVoidModal = false;
    public ?int $selectedMovementId = null;
    public string $voidReason = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Customer $customer)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja', 'reparto'])) {
            abort(403, 'No tiene permiso para acceder a envases del cliente.');
        }

        $this->customer = $customer;
        $this->batchToken = (string) Str::uuid();
    }

    public function openReturnModal()
    {
        $this->returnQuantities = [];
        $this->returnNotes = '';
        $this->batchToken = (string) Str::uuid();
        $this->errorMessage = null;

        $service = app(ReturnableService::class);
        $balances = $service->getCustomerBalances($this->customer);

        foreach ($balances as $b) {
            if ($b['outstanding'] > 0) {
                $this->returnQuantities[$b['type']->id] = 0;
            }
        }

        $this->showReturnModal = true;
    }

    public function closeReturnModal()
    {
        $this->showReturnModal = false;
        $this->errorMessage = null;
    }

    public function processReturn(ReturnableService $returnableService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $items = [];
        foreach ($this->returnQuantities as $typeId => $qty) {
            $q = (int) $qty;
            if ($q > 0) {
                $items[] = [
                    'returnable_type_id' => (int) $typeId,
                    'quantity' => $q,
                ];
            }
        }

        if (empty($items)) {
            $this->errorMessage = 'Debe indicar al menos una cantidad a devolver mayor a cero.';
            return;
        }

        try {
            $returnableService->recordReturnBatch(
                $this->customer,
                $items,
                auth()->user(),
                $this->batchToken,
                null,
                $this->returnNotes
            );

            $this->batchToken = (string) Str::uuid();
            $this->showReturnModal = false;
            $this->successMessage = 'Devolución de envases registrada exitosamente.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openVoidModal(int $movementId)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            $this->errorMessage = 'Solo administradores y personal de caja pueden anular movimientos.';
            return;
        }

        $this->selectedMovementId = $movementId;
        $this->voidReason = '';
        $this->errorMessage = null;
        $this->showVoidModal = true;
    }

    public function closeVoidModal()
    {
        $this->showVoidModal = false;
        $this->selectedMovementId = null;
        $this->voidReason = '';
        $this->errorMessage = null;
    }

    public function voidMovement(ReturnableService $returnableService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $this->validate([
            'voidReason' => 'required|string|min:3|max:500',
        ], [
            'voidReason.required' => 'Debe ingresar un motivo obligatorio para anular el movimiento.',
        ]);

        if (!$this->selectedMovementId) return;

        try {
            $movement = ReturnableMovement::findOrFail($this->selectedMovementId);
            $returnableService->voidMovement($movement, $this->voidReason, auth()->user());

            $this->showVoidModal = false;
            $this->successMessage = 'Movimiento de envase anulado correctamente.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(ReturnableService $returnableService)
    {
        $balances = $returnableService->getCustomerBalances($this->customer);
        $totalOutstanding = $returnableService->getCustomerTotalOutstanding($this->customer);

        $movements = $this->customer->returnableMovements()
            ->with(['type', 'user', 'voidedBy', 'order'])
            ->orderBy('occurred_at', 'desc')
            ->get();

        return view('livewire.customer-returnables', [
            'balances' => $balances,
            'totalOutstanding' => $totalOutstanding,
            'movements' => $movements,
        ])->layout('layouts.app', ['title' => 'Envases - ' . $this->customer->name]);
    }
}
