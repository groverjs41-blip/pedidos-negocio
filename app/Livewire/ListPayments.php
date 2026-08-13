<?php

namespace App\Livewire;

use App\Models\Payment;
use App\Services\PaymentService;
use Livewire\Component;

class ListPayments extends Component
{
    public string $search = '';
    public ?int $selectedPaymentId = null;

    // Void Modal State
    public bool $showVoidModal = false;
    public string $voidReason = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            abort(403, 'No tiene permiso para acceder a Cobranza.');
        }

        if (request()->has('search')) {
            $this->search = (string) request()->get('search');
        }
    }

    public function viewPayment(int $id)
    {
        $this->selectedPaymentId = $id;
        $this->errorMessage = null;
    }

    public function closeModal()
    {
        $this->selectedPaymentId = null;
        $this->showVoidModal = false;
        $this->voidReason = '';
        $this->errorMessage = null;
    }

    public function openVoidModal()
    {
        $this->voidReason = '';
        $this->errorMessage = null;
        $this->showVoidModal = true;
    }

    public function voidPayment(PaymentService $paymentService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $this->validate([
            'voidReason' => 'required|string|min:3|max:500',
        ], [
            'voidReason.required' => 'Debe ingresar un motivo obligatorio para anular el pago.',
            'voidReason.min' => 'El motivo debe tener al menos 3 caracteres.',
        ]);

        if (!$this->selectedPaymentId) {
            return;
        }

        try {
            $payment = Payment::findOrFail($this->selectedPaymentId);
            $paymentService->voidPayment($payment, $this->voidReason, auth()->user());

            $this->showVoidModal = false;
            $this->successMessage = 'Pago anulado correctamente. Las asignaciones de balance han sido revertidas.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $query = Payment::with(['customer', 'creator', 'voidedBy', 'allocations.order'])
            ->orderBy('paid_at', 'desc');

        if (!empty(trim($this->search))) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('reference', 'like', "%{$s}%")
                  ->orWhereHas('customer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $payments = $query->take(50)->get();

        $selectedPayment = null;
        if ($this->selectedPaymentId) {
            $selectedPayment = Payment::with(['customer', 'creator', 'voidedBy', 'allocations.order'])
                ->find($this->selectedPaymentId);
        }

        return view('livewire.list-payments', [
            'payments' => $payments,
            'selectedPayment' => $selectedPayment,
        ])->layout('layouts.app', ['title' => 'Historial de Pagos - Pedidos Negocio']);
    }
}
