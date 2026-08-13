<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\PaymentChanged;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a payment for a specific order.
     */
    public function recordOrderPayment(
        Order $order,
        string $amount,
        PaymentMethod $method,
        ?string $reference,
        ?string $notes,
        User $user,
        string $submissionToken
    ): Payment {
        // Idempotency check
        $existing = Payment::where('submission_token', $submissionToken)->first();
        if ($existing) {
            return $existing;
        }

        $formattedAmount = number_format((float)$amount, 2, '.', '');
        if (bccomp($formattedAmount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }

        $paymentToBroadcast = null;
        $orderIdsToBroadcast = [];

        DB::transaction(function () use (
            $order,
            $formattedAmount,
            $method,
            $reference,
            $notes,
            $user,
            $submissionToken,
            &$paymentToBroadcast,
            &$orderIdsToBroadcast
        ) {
            // Lock order for update
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                throw new InvalidArgumentException('No se pueden registrar pagos en un pedido cancelado.');
            }

            $currentBalance = $lockedOrder->outstandingBalance();
            if (bccomp($formattedAmount, $currentBalance, 2) > 0) {
                throw new InvalidArgumentException("El monto ({$formattedAmount}) excede el saldo pendiente del pedido ({$currentBalance}).");
            }

            $payment = Payment::create([
                'submission_token' => $submissionToken,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => $formattedAmount,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => now(),
                'created_by' => $user->id,
                'notes' => $notes,
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'order_id' => $lockedOrder->id,
                'amount' => $formattedAmount,
            ]);

            $paymentToBroadcast = $payment;
            $orderIdsToBroadcast = [$lockedOrder->id];
        });

        $this->safeBroadcast($paymentToBroadcast, $orderIdsToBroadcast, 'CREATED');

        return $paymentToBroadcast;
    }

    /**
     * Record a lump-sum payment for a customer, distributing oldest-first across DELIVERED orders.
     */
    public function recordCustomerPayment(
        Customer $customer,
        string $amount,
        PaymentMethod $method,
        ?string $reference,
        ?string $notes,
        User $user,
        string $submissionToken
    ): Payment {
        // Idempotency check
        $existing = Payment::where('submission_token', $submissionToken)->first();
        if ($existing) {
            return $existing;
        }

        $formattedAmount = number_format((float)$amount, 2, '.', '');
        if (bccomp($formattedAmount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('El monto del abono debe ser mayor a cero.');
        }

        $paymentToBroadcast = null;
        $orderIdsToBroadcast = [];

        DB::transaction(function () use (
            $customer,
            $formattedAmount,
            $method,
            $reference,
            $notes,
            $user,
            $submissionToken,
            &$paymentToBroadcast,
            &$orderIdsToBroadcast
        ) {
            // Lock all DELIVERED orders for this customer ordered_at ASC, id ASC
            $candidateOrders = Order::where('customer_id', $customer->id)
                ->where('status', OrderStatus::DELIVERED)
                ->orderBy('ordered_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            // Calculate total debt inside lock
            $totalDebt = '0.00';
            $ordersToAllocate = [];

            foreach ($candidateOrders as $ord) {
                $bal = $ord->outstandingBalance();
                if (bccomp($bal, '0.00', 2) > 0) {
                    $totalDebt = bcadd($totalDebt, $bal, 2);
                    $ordersToAllocate[] = [
                        'order' => $ord,
                        'balance' => $bal,
                    ];
                }
            }

            if (bccomp($formattedAmount, $totalDebt, 2) > 0) {
                throw new InvalidArgumentException("El monto excede el saldo pendiente total del cliente ({$totalDebt}).");
            }

            $payment = Payment::create([
                'submission_token' => $submissionToken,
                'customer_id' => $customer->id,
                'amount' => $formattedAmount,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => now(),
                'created_by' => $user->id,
                'notes' => $notes,
            ]);

            $remainingToAllocate = $formattedAmount;

            foreach ($ordersToAllocate as $item) {
                if (bccomp($remainingToAllocate, '0.00', 2) <= 0) {
                    break;
                }

                $ord = $item['order'];
                $bal = $item['balance'];

                // Allocate min(remainingToAllocate, bal)
                $allocAmount = bccomp($remainingToAllocate, $bal, 2) >= 0 ? $bal : $remainingToAllocate;

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'order_id' => $ord->id,
                    'amount' => $allocAmount,
                ]);

                $remainingToAllocate = bcsub($remainingToAllocate, $allocAmount, 2);
                $orderIdsToBroadcast[] = $ord->id;
            }

            $paymentToBroadcast = $payment;
        });

        $this->safeBroadcast($paymentToBroadcast, $orderIdsToBroadcast, 'CREATED');

        return $paymentToBroadcast;
    }

    /**
     * Pay full customer debt.
     */
    public function payCustomerBalance(
        Customer $customer,
        PaymentMethod $method,
        ?string $reference,
        ?string $notes,
        User $user,
        string $submissionToken
    ): Payment {
        $balance = $customer->outstandingBalance();
        if (bccomp($balance, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('El cliente no tiene saldo pendiente por cobrar.');
        }

        return $this->recordCustomerPayment(
            $customer,
            $balance,
            $method,
            $reference,
            $notes,
            $user,
            $submissionToken
        );
    }

    /**
     * Void a payment with a required reason.
     */
    public function voidPayment(Payment $payment, string $reason, User $user): Payment
    {
        if ($payment->isVoided()) {
            throw new InvalidArgumentException('Este pago ya se encuentra anulado.');
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('Debe proporcionar un motivo para anular el pago.');
        }

        $orderIds = $payment->allocations->pluck('order_id')->toArray();

        DB::transaction(function () use ($payment, $reason, $user) {
            $payment->update([
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);
        });

        $this->safeBroadcast($payment, $orderIds, 'VOIDED');

        return $payment->fresh();
    }

    public function getOrderPaidAmount(Order $order): string
    {
        return $order->paidAmount();
    }

    public function getOrderBalance(Order $order): string
    {
        return $order->outstandingBalance();
    }

    public function getCustomerBalance(Customer $customer): string
    {
        return $customer->outstandingBalance();
    }

    /**
     * Safely dispatch broadcast event post-commit.
     */
    protected function safeBroadcast(?Payment $payment, array $orderIds, string $action): void
    {
        if (!$payment) return;

        try {
            event(new PaymentChanged(
                $payment->id,
                $payment->customer_id,
                $orderIds,
                (string)$payment->amount,
                $action
            ));
        } catch (\Throwable $e) {
            Log::warning('PaymentChanged broadcast failed: ' . $e->getMessage());
        }
    }
}
