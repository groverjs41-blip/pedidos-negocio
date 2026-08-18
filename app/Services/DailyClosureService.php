<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReturnableMovementType;
use App\Events\DailyClosureChanged;
use App\Models\Customer;
use App\Models\DailyClosure;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReturnableMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DailyClosureService
{
    /**
     * Get startOfDay and endOfDay in configured app timezone.
     */
    public function getDateBounds(Carbon|string $date): array
    {
        $tz = config('app.timezone', 'UTC');
        $cDate = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $cDate->setTimezone($tz);

        return [
            'start' => $cDate->copy()->startOfDay(),
            'end' => $cDate->copy()->endOfDay(),
            'date_str' => $cDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate live operational summary for a business date.
     */
    public function getDailySummary(Carbon|string $date): array
    {
        $bounds = $this->getDateBounds($date);
        $start = $bounds['start'];
        $end = $bounds['end'];
        $dateStr = $bounds['date_str'];

        $ordersQuery = Order::whereBetween('ordered_at', [$start, $end]);
        $ordersCount = (clone $ordersQuery)->count();
        $deliveredCount = (clone $ordersQuery)->where('status', OrderStatus::DELIVERED)->count();
        $cancelledCount = (clone $ordersQuery)->where('status', OrderStatus::CANCELLED)->count();

        $openOrdersCount = Order::whereBetween('ordered_at', [$start, $end])
            ->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::CANCELLED])
            ->count();

        // Gross sales = sum of totals of DELIVERED orders created on that date
        $deliveredOrders = (clone $ordersQuery)->where('status', OrderStatus::DELIVERED)->get();
        $grossSales = '0.00';
        foreach ($deliveredOrders as $ord) {
            $grossSales = bcadd($grossSales, (string)$ord->total, 2);
        }

        // Total collected = sum of non-voided payments received in date range
        $payments = Payment::whereNull('voided_at')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        $totalCollected = '0.00';
        $byMethod = [
            PaymentMethod::CASH->value => '0.00',
            PaymentMethod::CARD->value => '0.00',
            PaymentMethod::TRANSFER->value => '0.00',
            PaymentMethod::OTHER->value => '0.00',
        ];

        foreach ($payments as $pay) {
            $amt = (string)$pay->amount;
            $totalCollected = bcadd($totalCollected, $amt, 2);
            $mVal = $pay->method->value;
            $byMethod[$mVal] = bcadd($byMethod[$mVal] ?? '0.00', $amt, 2);
        }

        // Returnables on that date
        $returnableMovements = ReturnableMovement::whereNull('voided_at')
            ->whereBetween('occurred_at', [$start, $end])
            ->get();

        $containersOut = $returnableMovements->where('movement_type', ReturnableMovementType::OUT)->sum('quantity');
        $containersReturned = $returnableMovements->where('movement_type', ReturnableMovementType::RETURN)->sum('quantity');

        // Total outstanding customer debt derived from DELIVERED orders
        $allCustomers = Customer::all();
        $pendingDebtAtClosure = '0.00';
        foreach ($allCustomers as $cust) {
            $pendingDebtAtClosure = bcadd($pendingDebtAtClosure, $cust->outstandingBalance(), 2);
        }

        $closure = DailyClosure::where('business_date', $dateStr)->first();

        return [
            'business_date' => $dateStr,
            'is_closed' => $closure !== null,
            'closure' => $closure,
            'orders_count' => $ordersCount,
            'orders_delivered_count' => $deliveredCount,
            'orders_cancelled_count' => $cancelledCount,
            'open_orders_count' => $openOrdersCount,
            'gross_sales' => $grossSales,
            'total_collected' => $totalCollected,
            'collected_by_method' => $byMethod,
            'containers_out' => $containersOut,
            'containers_returned' => $containersReturned,
            'pending_debt_at_closure' => $pendingDebtAtClosure,
        ];
    }

    /**
     * Perform daily closure for a business date.
     */
    public function closeDay(
        Carbon|string $date,
        User $user,
        bool $forced = false,
        ?string $forceReason = null,
        ?string $notes = null
    ): DailyClosure {
        $bounds = $this->getDateBounds($date);
        $dateStr = $bounds['date_str'];

        // Pre-check if already closed
        $existing = DailyClosure::whereDate('business_date', $dateStr)->first();
        if ($existing) {
            throw new InvalidArgumentException("El día '{$dateStr}' ya ha sido cerrado previamente.");
        }

        $summary = $this->getDailySummary($date);
        $openOrdersCount = $summary['open_orders_count'];

        if ($openOrdersCount > 0 && !$forced) {
            throw new InvalidArgumentException("Existen {$openOrdersCount} pedidos pendientes de entrega o cancelación. Para realizar un cierre forzado debe confirmarlo e ingresar un motivo obligatorio.");
        }

        if ($forced && empty(trim($forceReason ?? ''))) {
            throw new InvalidArgumentException('Debe ingresar un motivo obligatorio para realizar el cierre forzado del día.');
        }

        $createdClosure = null;

        DB::transaction(function () use (
            $dateStr,
            $summary,
            $user,
            $forced,
            $forceReason,
            $notes,
            &$createdClosure
        ) {
            // Check inside lock
            $existingInside = DailyClosure::whereDate('business_date', $dateStr)->lockForUpdate()->first();
            if ($existingInside) {
                throw new InvalidArgumentException("El día '{$dateStr}' ya ha sido cerrado previamente.");
            }

            $snapshot = [
                'business_date' => $dateStr,
                'orders_count' => $summary['orders_count'],
                'orders_delivered_count' => $summary['orders_delivered_count'],
                'orders_cancelled_count' => $summary['orders_cancelled_count'],
                'open_orders_count' => $summary['open_orders_count'],
                'gross_sales' => $summary['gross_sales'],
                'total_collected' => $summary['total_collected'],
                'collected_by_method' => $summary['collected_by_method'],
                'containers_out' => $summary['containers_out'],
                'containers_returned' => $summary['containers_returned'],
                'pending_debt_at_closure' => $summary['pending_debt_at_closure'],
            ];

            $createdClosure = DailyClosure::create([
                'business_date' => $dateStr,
                'closed_at' => now(),
                'closed_by' => $user->id,
                'forced' => $forced,
                'force_reason' => $forced ? trim($forceReason) : null,
                'notes' => trim($notes ?? '') ?: null,
                'snapshot' => $snapshot,
            ]);
        });

        // Broadcast post-commit
        try {
            event(new DailyClosureChanged(
                $createdClosure->business_date->format('Y-m-d'),
                $createdClosure->closed_at->toIso8601String(),
                $createdClosure->closed_by,
                $createdClosure->forced,
                'CLOSED'
            ));
        } catch (\Throwable $e) {
            Log::warning('DailyClosureChanged broadcast failed: ' . $e->getMessage());
        }

        return $createdClosure;
    }

    /**
     * Get closure for a date if it exists.
     */
    public function getClosure(Carbon|string $date): ?DailyClosure
    {
        $bounds = $this->getDateBounds($date);
        return DailyClosure::whereDate('business_date', $bounds['date_str'])->first();
    }

    /**
     * Check if a business date is closed.
     */
    public function isClosed(Carbon|string $date): bool
    {
        return $this->getClosure($date) !== null;
    }
}
