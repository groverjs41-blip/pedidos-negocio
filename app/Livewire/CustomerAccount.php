<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Str;
use Livewire\Component;

class CustomerAccount extends Component
{
    public Customer $customer;

    // Payment Form Modal State
    public bool $showPaymentModal = false;
    public string $paymentType = 'FULL'; // 'FULL' or 'PARTIAL'
    public string $paymentAmount = '';
    public string $paymentMethod = 'CASH';
    public string $reference = '';
    public string $notes = '';
    public string $submissionToken = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(Customer $customer)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            abort(403, 'No tiene permiso para acceder a Cobranza.');
        }

        $this->customer = $customer;
        $this->submissionToken = (string) Str::uuid();
    }

    public function openPaymentModal(string $type = 'FULL')
    {
        $this->paymentType = $type;
        $this->paymentMethod = 'CASH';
        $this->reference = '';
        $this->notes = '';
        $this->submissionToken = (string) Str::uuid();
        $this->errorMessage = null;

        $balance = $this->customer->outstandingBalance();
        if ($type === 'FULL') {
            $this->paymentAmount = $balance;
        } else {
            $this->paymentAmount = '';
        }

        $this->showPaymentModal = true;
    }

    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->errorMessage = null;
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

            $paymentService->recordCustomerPayment(
                $this->customer,
                $this->paymentAmount,
                $methodEnum,
                $this->reference,
                $this->notes,
                $user,
                $this->submissionToken
            );

            $this->submissionToken = (string) Str::uuid();
            $this->showPaymentModal = false;
            $this->successMessage = 'Pago registrado exitosamente.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $deliveredOrders = $this->customer->orders()
            ->where('status', \App\Enums\OrderStatus::DELIVERED)
            ->orderBy('ordered_at', 'asc')
            ->get();

        $paymentsHistory = $this->customer->payments()
            ->orderBy('paid_at', 'desc')
            ->get();

        return view('livewire.customer-account', [
            'deliveredOrders' => $deliveredOrders,
            'paymentsHistory' => $paymentsHistory,
            'outstandingBalance' => $this->customer->outstandingBalance(),
        ])->layout('layouts.app', ['title' => 'Estado de Cuenta - ' . $this->customer->name]);
    }
}
