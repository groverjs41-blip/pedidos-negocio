<?php

namespace App\Livewire;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CollectionVisitService;
use App\Services\PaymentService;
use App\Services\ReturnableService;
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

    // Combined Visit Modal State
    public bool $showVisitModal = false;
    public string $visitPaymentAmount = '';
    public string $visitPaymentMethod = 'CASH';
    public string $visitReference = '';
    public array $visitReturnQuantities = []; // [type_id => qty]
    public string $visitNotes = '';

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

    public function openVisitModal(ReturnableService $returnableService)
    {
        $this->visitPaymentAmount = '';
        $this->visitPaymentMethod = 'CASH';
        $this->visitReference = '';
        $this->visitNotes = '';
        $this->visitReturnQuantities = [];
        $this->submissionToken = (string) Str::uuid();
        $this->errorMessage = null;

        $balances = $returnableService->getCustomerBalances($this->customer);
        foreach ($balances as $b) {
            if ($b['outstanding'] > 0) {
                $this->visitReturnQuantities[$b['type']->id] = 0;
            }
        }

        $this->showVisitModal = true;
    }

    public function closeVisitModal()
    {
        $this->showVisitModal = false;
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

    public function processVisit(CollectionVisitService $visitService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        $paymentData = null;
        if (!empty($this->visitPaymentAmount) && bccomp($this->visitPaymentAmount, '0.00', 2) > 0) {
            $paymentData = [
                'amount' => $this->visitPaymentAmount,
                'method' => PaymentMethod::from($this->visitPaymentMethod),
                'reference' => $this->visitReference,
                'notes' => $this->visitNotes,
            ];
        }

        $returnItems = [];
        foreach ($this->visitReturnQuantities as $typeId => $qty) {
            $q = (int) $qty;
            if ($q > 0) {
                $returnItems[] = [
                    'returnable_type_id' => (int) $typeId,
                    'quantity' => $q,
                ];
            }
        }

        $returnData = !empty($returnItems) ? ['items' => $returnItems, 'notes' => $this->visitNotes] : null;

        try {
            $visitService->recordVisit(
                $this->customer,
                $paymentData,
                $returnData,
                auth()->user(),
                $this->submissionToken
            );

            $this->submissionToken = (string) Str::uuid();
            $this->showVisitModal = false;
            $this->successMessage = 'Visita (cobro y/o devolución de envases) registrada exitosamente.';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(ReturnableService $returnableService)
    {
        $deliveredOrders = $this->customer->orders()
            ->where('status', \App\Enums\OrderStatus::DELIVERED)
            ->orderBy('ordered_at', 'asc')
            ->get();

        $paymentsHistory = $this->customer->payments()
            ->orderBy('paid_at', 'desc')
            ->get();

        $containerBalances = $returnableService->getCustomerBalances($this->customer);
        $totalOutstandingContainers = $returnableService->getCustomerTotalOutstanding($this->customer);

        return view('livewire.customer-account', [
            'deliveredOrders' => $deliveredOrders,
            'paymentsHistory' => $paymentsHistory,
            'outstandingBalance' => $this->customer->outstandingBalance(),
            'containerBalances' => $containerBalances,
            'totalOutstandingContainers' => $totalOutstandingContainers,
        ])->layout('layouts.app', ['title' => 'Estado de Cuenta - ' . $this->customer->name]);
    }
}
