<?php

namespace App\Services;

use App\Events\PaymentChanged;
use App\Events\ReturnableChanged;
use App\Models\CollectionVisit;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\ReturnableMovement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

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
     * Helper to reconstruct the result array from an existing CollectionVisit record.
     */
    protected function formatVisitResult(CollectionVisit $visit): array
    {
        $payment = $visit->payment_id ? Payment::find($visit->payment_id) : null;
        $returnables = $visit->return_batch_token
            ? ReturnableMovement::where('batch_token', $visit->return_batch_token)->get()->all()
            : [];

        return [
            'visit' => $visit,
            'payment' => $payment,
            'returnables' => $returnables,
        ];
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
        // 1. Pre-transaction idempotency check
        $existingVisit = CollectionVisit::where('submission_token', $submissionToken)->first();
        if ($existingVisit) {
            return $this->formatVisitResult($existingVisit);
        }

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

        // Derive deterministic UUID v5 tokens from submissionToken
        $paymentToken = Uuid::uuid5(Uuid::NAMESPACE_OID, 'collection-payment:' . $submissionToken)->toString();
        $returnBatchToken = Uuid::uuid5(Uuid::NAMESPACE_OID, 'collection-return:' . $submissionToken)->toString();

        $paymentResult = null;
        $returnResult = [];
        $visitResult = null;

        $orderIdsToBroadcast = [];
        $returnTotalQty = 0;
        $returnTypeIds = [];

        try {
            DB::transaction(function () use (
                $customer,
                $paymentData,
                $returnData,
                $user,
                $submissionToken,
                $hasPayment,
                $hasReturn,
                $paymentToken,
                $returnBatchToken,
                &$paymentResult,
                &$returnResult,
                &$visitResult,
                &$orderIdsToBroadcast,
                &$returnTotalQty,
                &$returnTypeIds
            ) {
                // Secondary check inside lock
                $existingInside = CollectionVisit::where('submission_token', $submissionToken)->lockForUpdate()->first();
                if ($existingInside) {
                    $visitResult = $existingInside;
                    return;
                }

                // Lock customer pesimistically first for atomic consistency
                Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

                // 1. Process payment if provided (without sub-transaction or broadcast)
                if ($hasPayment) {
                    $formattedAmount = (string)$paymentData['amount'];
                    $paymentResult = $this->paymentService->recordCustomerPaymentInternal(
                        $customer,
                        $formattedAmount,
                        $paymentData['method'],
                        $paymentData['reference'] ?? null,
                        $paymentData['notes'] ?? null,
                        $user,
                        $paymentToken,
                        $orderIdsToBroadcast
                    );
                }

                // 2. Process returnables if provided (without sub-transaction or broadcast)
                if ($hasReturn) {
                    $returnResult = $this->returnableService->recordReturnBatchInternal(
                        $customer,
                        $returnData['items'],
                        $user,
                        $returnBatchToken,
                        null,
                        $returnData['notes'] ?? null,
                        $returnTotalQty,
                        $returnTypeIds
                    );
                }

                // 3. Create CollectionVisit audit record
                $visitResult = CollectionVisit::create([
                    'submission_token' => $submissionToken,
                    'customer_id' => $customer->id,
                    'payment_id' => $paymentResult?->id,
                    'return_batch_token' => $hasReturn ? $returnBatchToken : null,
                    'created_by' => $user->id,
                ]);
            });
        } catch (QueryException $e) {
            // Concurrent race recovery for duplicate submission_token (SQLSTATE 23000)
            if ($e->getCode() == '23000' || ($e->errorInfo[1] ?? null) == 1062) {
                $existing = CollectionVisit::where('submission_token', $submissionToken)->first();
                if ($existing) {
                    return $this->formatVisitResult($existing);
                }
            }
            throw $e;
        }

        // Post-commit broadcasts ONLY
        if ($paymentResult) {
            try {
                event(new PaymentChanged(
                    $paymentResult->id,
                    $customer->id,
                    $paymentResult->amount,
                    $paymentResult->method->value,
                    $orderIdsToBroadcast,
                    'CREATED'
                ));
            } catch (\Throwable $e) {
                Log::warning('PaymentChanged broadcast failed in visit: ' . $e->getMessage());
            }
        }

        if ($hasReturn && !empty($returnResult)) {
            try {
                event(new ReturnableChanged(
                    $customer->id,
                    null,
                    \App\Enums\ReturnableMovementType::RETURN->value,
                    $returnTypeIds,
                    $returnTotalQty,
                    'CREATED'
                ));
            } catch (\Throwable $e) {
                Log::warning('ReturnableChanged broadcast failed in visit: ' . $e->getMessage());
            }
        }

        return [
            'visit' => $visitResult,
            'payment' => $paymentResult,
            'returnables' => $returnResult,
        ];
    }
}
