<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Str;
use Livewire\Component;

class PayOrder extends Component
{
    public Order $order;

    public string $paymentAmount = '';
    public string $paymentMethod = 'CASH';
    public string $reference = '';
    public string $notes = '';
    public string $submissionToken = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Order $order)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            abort(403, 'No tiene permiso para acceder a Cobranza.');
        }

        $this->order = $order;
        $this->paymentAmount = $order->outstandingBalance();
        $this->submissionToken = (string) Str::uuid();
    }

    public function processPayment(PaymentService $paymentService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethod' => 'required',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $methodEnum = PaymentMethod::from($this->paymentMethod);
            $user = auth()->user();

            $paymentService->recordOrderPayment(
                $this->order,
                $this->paymentAmount,
                $methodEnum,
                $this->reference,
                $this->notes,
                $user,
                $this->submissionToken
            );

            $this->submissionToken = (string) Str::uuid();
            $this->order->refresh();
            $this->paymentAmount = $this->order->outstandingBalance();
            $this->successMessage = 'Pago de pedido registrado exitosamente.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.pay-order', [
            'paidAmount' => $this->order->paidAmount(),
            'outstandingBalance' => $this->order->outstandingBalance(),
            'paymentStatus' => $this->order->paymentStatus(),
        ])->layout('layouts.app', ['title' => 'Cobrar Pedido ' . $this->order->number]);
    }
}
