<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CollectionVisitService
{
    protected PaymentService $paymentService;
    protected ReturnableService $returnableService;

    public function __construct(PaymentService $paymentService, ReturnableService $returnableService)
    {
        $this->paymentService = $paymentService;
        $this->returnableService = $returnableService;
    }

    /**
     * Record a combined collection visit (Payment + Container Return) in a single atomic transaction.
     *
     * @param array|null $paymentData Structure: ['amount' => string, 'method' => PaymentMethod, 'reference' => ?string, 'notes' => ?string]
     * @param array|null $returnData Structure: ['items' => [['returnable_type_id' => int, 'quantity' => int], ...], 'notes' => ?string]
     */
    public function recordVisit(
        Customer $customer,
        ?array $paymentData,
        ?array $returnData,
        User $user,
        string $submissionToken
    ): array {
        $hasPayment = !empty($paymentData['amount']) && bccomp((string)$paymentData['amount'], '0.00', 2) > 0;

        $hasReturn = false;
        if (!empty($returnData['items'])) {
            foreach ($returnData['items'] as $item) {
                if ((int)($item['quantity'] ?? 0) > 0) {
                    $hasReturn = true;
                    break;
                }
            }
        }

        if (!$hasPayment && !$hasReturn) {
            throw new InvalidArgumentException('Debe registrar al menos un cobro o una devolución de envases para la visita.');
        }

        $paymentResult = null;
        $returnResult = [];

        DB::transaction(function () use (
            $customer,
            $paymentData,
            $returnData,
            $user,
            $submissionToken,
            $hasPayment,
            $hasReturn,
            &$paymentResult,
            &$returnResult
        ) {
            // Lock customer pesimistically first for atomic consistency
            Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            // 1. Process payment if provided
            if ($hasPayment) {
                $paymentToken = (string) \Illuminate\Support\Str::uuid();
                $paymentResult = $this->paymentService->recordCustomerPayment(
                    $customer,
                    (string)$paymentData['amount'],
                    $paymentData['method'],
                    $paymentData['reference'] ?? null,
                    $paymentData['notes'] ?? null,
                    $user,
                    $paymentToken
                );
            }

            // 2. Process returnables if provided
            if ($hasReturn) {
                $returnToken = (string) \Illuminate\Support\Str::uuid();
                $returnResult = $this->returnableService->recordReturnBatch(
                    $customer,
                    $returnData['items'],
                    $user,
                    $returnToken,
                    null,
                    $returnData['notes'] ?? null
                );
            }
        });

        return [
            'payment' => $paymentResult,
            'returnables' => $returnResult,
        ];
    }
}
