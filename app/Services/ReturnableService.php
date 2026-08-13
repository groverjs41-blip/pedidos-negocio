<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ReturnableMovementType;
use App\Events\ReturnableChanged;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ReturnableMovement;
use App\Models\ReturnableType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ReturnableService
{
    /**
     * Calculate balance for a customer and optional specific returnable type.
     */
    public function getCustomerBalance(Customer $customer, ?ReturnableType $type = null): int
    {
        $query = ReturnableMovement::where('customer_id', $customer->id)
            ->whereNull('voided_at');

        if ($type) {
            $query->where('returnable_type_id', $type->id);
        }

        $movements = $query->get();

        $out = $movements->where('movement_type', ReturnableMovementType::OUT)->sum('quantity');
        $return = $movements->where('movement_type', ReturnableMovementType::RETURN)->sum('quantity');

        $balance = $out - $return;

        if ($balance < 0) {
            Log::error("Corrupción de saldo de envases detectada para cliente ID {$customer->id}: saldo negativo ({$balance}).");
            throw new \LogicException("Inconsistencia en el saldo de envases para el cliente ID {$customer->id}.");
        }

        return $balance;
    }

    /**
     * Get per-type balances for a customer.
     * Includes active types AND inactive types with outstanding > 0.
     */
    public function getCustomerBalances(Customer $customer): array
    {
        $allTypes = ReturnableType::orderBy('sort_order', 'asc')->get();
        $result = [];

        foreach ($allTypes as $type) {
            $balance = $this->getCustomerBalance($customer, $type);
            if ($type->active || $balance > 0) {
                $result[] = [
                    'type' => $type,
                    'outstanding' => $balance,
                ];
            }
        }

        return $result;
    }

    /**
     * Calculate total outstanding container units across all types for a customer.
     */
    public function getCustomerTotalOutstanding(Customer $customer): int
    {
        $balances = $this->getCustomerBalances($customer);
        $total = 0;
        foreach ($balances as $b) {
            $total += $b['outstanding'];
        }
        return $total;
    }

    /**
     * Get collection of customers with total outstanding > 0.
     */
    public function getOutstandingCustomers()
    {
        $customers = Customer::all();

        return $customers->filter(function ($cust) {
            return $this->getCustomerTotalOutstanding($cust) > 0;
        })->values();
    }

    /**
     * Record a batch of OUT movements (containers handed to customer).
     *
     * @param array $items Structure: [['returnable_type_id' => int, 'quantity' => int], ...]
     */
    public function recordOutBatch(
        Customer $customer,
        array $items,
        User $user,
        string $batchToken,
        ?Order $order = null,
        ?string $notes = null
    ): array {
        // Idempotency pre-check
        $existing = ReturnableMovement::where('batch_token', $batchToken)->get();
        if ($existing->isNotEmpty()) {
            return $existing->all();
        }

        $createdMovements = [];
        $totalQty = 0;
        $typeIds = [];

        try {
            DB::transaction(function () use (
                $customer,
                $items,
                $user,
                $batchToken,
                $order,
                $notes,
                &$createdMovements,
                &$totalQty,
                &$typeIds
            ) {
                $createdMovements = $this->recordOutBatchInternal(
                    $customer,
                    $items,
                    $user,
                    $batchToken,
                    $order,
                    $notes,
                    $totalQty,
                    $typeIds
                );
            });
        } catch (QueryException $e) {
            if ($e->getCode() == '23000' || ($e->errorInfo[1] ?? null) == 1062) {
                $existing = ReturnableMovement::where('batch_token', $batchToken)->get();
                if ($existing->isNotEmpty()) {
                    return $existing->all();
                }
            }
            throw $e;
        }

        $this->safeBroadcast($customer->id, $order?->id, ReturnableMovementType::OUT->value, $typeIds, $totalQty, 'CREATED');

        return $createdMovements;
    }

    /**
     * Internal method to execute OUT batch recording within an existing transaction.
     * Does NOT open a DB::transaction() and does NOT fire broadcasts.
     */
    public function recordOutBatchInternal(
        Customer $customer,
        array $items,
        User $user,
        string $batchToken,
        ?Order $order = null,
        ?string $notes = null,
        int &$totalQty = 0,
        array &$typeIds = []
    ): array {
        // Secondary check inside lock
        $existingInside = ReturnableMovement::where('batch_token', $batchToken)->lockForUpdate()->get();
        if ($existingInside->isNotEmpty()) {
            $totalQty = $existingInside->sum('quantity');
            $typeIds = $existingInside->pluck('returnable_type_id')->unique()->all();
            return $existingInside->all();
        }

        if (!$customer || !$customer->id) {
            throw new InvalidArgumentException('Para dejar envases retornables debes asociar el pedido a un cliente.');
        }

        if (!$customer->active) {
            throw new InvalidArgumentException('No se pueden registrar nuevas salidas de envases a un cliente inactivo.');
        }

        if ($order) {
            if ((int)$order->customer_id !== (int)$customer->id) {
                throw new InvalidArgumentException('El pedido seleccionado no pertenece al cliente.');
            }
            if ($order->status !== OrderStatus::DELIVERED) {
                throw new InvalidArgumentException('El pedido debe estar en estado Entregado para vincular la salida de envases.');
            }
        } else {
            // Manual OUT without order requires admin or caja role and notes
            if (!$user->hasAnyRole(['admin', 'caja'])) {
                throw new InvalidArgumentException('Solo administradores y personal de caja pueden registrar salidas manuales sin pedido.');
            }
            if (empty(trim($notes ?? ''))) {
                throw new InvalidArgumentException('Debe ingresar una nota obligatoria para salidas manuales de envases.');
            }
        }

        $validItems = [];
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 0);
            if ($qty > 0) {
                $type = ReturnableType::findOrFail($item['returnable_type_id']);
                if (!$type->active) {
                    throw new InvalidArgumentException("El tipo de envase '{$type->name}' está inactivo y no se pueden registrar nuevas salidas.");
                }
                $validItems[] = [
                    'type' => $type,
                    'quantity' => $qty,
                ];
            }
        }

        if (empty($validItems)) {
            throw new InvalidArgumentException('Debe especificar al menos una cantidad de envase mayor a cero.');
        }

        // Lock customer for update
        Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

        $now = now();
        $createdMovements = [];
        $totalQty = 0;
        $typeIds = [];

        foreach ($validItems as $v) {
            $m = ReturnableMovement::create([
                'batch_token' => $batchToken,
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'returnable_type_id' => $v['type']->id,
                'movement_type' => ReturnableMovementType::OUT,
                'quantity' => $v['quantity'],
                'occurred_at' => $now,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);

            $createdMovements[] = $m;
            $totalQty += $v['quantity'];
            $typeIds[] = $v['type']->id;
        }

        return $createdMovements;
    }

    /**
     * Record a batch of RETURN movements (containers recovered from customer).
     *
     * @param array $items Structure: [['returnable_type_id' => int, 'quantity' => int], ...]
     */
    public function recordReturnBatch(
        Customer $customer,
        array $items,
        User $user,
        string $batchToken,
        ?Order $order = null,
        ?string $notes = null
    ): array {
        // Idempotency pre-check
        $existing = ReturnableMovement::where('batch_token', $batchToken)->get();
        if ($existing->isNotEmpty()) {
            return $existing->all();
        }

        $createdMovements = [];
        $totalQty = 0;
        $typeIds = [];

        try {
            DB::transaction(function () use (
                $customer,
                $items,
                $user,
                $batchToken,
                $order,
                $notes,
                &$createdMovements,
                &$totalQty,
                &$typeIds
            ) {
                $createdMovements = $this->recordReturnBatchInternal(
                    $customer,
                    $items,
                    $user,
                    $batchToken,
                    $order,
                    $notes,
                    $totalQty,
                    $typeIds
                );
            });
        } catch (QueryException $e) {
            if ($e->getCode() == '23000' || ($e->errorInfo[1] ?? null) == 1062) {
                $existing = ReturnableMovement::where('batch_token', $batchToken)->get();
                if ($existing->isNotEmpty()) {
                    return $existing->all();
                }
            }
            throw $e;
        }

        $this->safeBroadcast($customer->id, $order?->id, ReturnableMovementType::RETURN->value, $typeIds, $totalQty, 'CREATED');

        return $createdMovements;
    }

    /**
     * Internal method to execute RETURN batch recording within an existing transaction.
     * Does NOT open a DB::transaction() and does NOT fire broadcasts.
     */
    public function recordReturnBatchInternal(
        Customer $customer,
        array $items,
        User $user,
        string $batchToken,
        ?Order $order = null,
        ?string $notes = null,
        int &$totalQty = 0,
        array &$typeIds = []
    ): array {
        // Secondary check inside lock
        $existingInside = ReturnableMovement::where('batch_token', $batchToken)->lockForUpdate()->get();
        if ($existingInside->isNotEmpty()) {
            $totalQty = $existingInside->sum('quantity');
            $typeIds = $existingInside->pluck('returnable_type_id')->unique()->all();
            return $existingInside->all();
        }

        if (!$customer || !$customer->id) {
            throw new InvalidArgumentException('Cliente inválido.');
        }

        if ($order && (int)$order->customer_id !== (int)$customer->id) {
            throw new InvalidArgumentException('El pedido seleccionado no pertenece al cliente.');
        }

        $validItems = [];
        foreach ($items as $item) {
            $qty = (int)($item['quantity'] ?? 0);
            if ($qty > 0) {
                $type = ReturnableType::findOrFail($item['returnable_type_id']);
                $validItems[] = [
                    'type' => $type,
                    'quantity' => $qty,
                ];
            }
        }

        if (empty($validItems)) {
            throw new InvalidArgumentException('Debe especificar al menos una cantidad a devolver mayor a cero.');
        }

        // Lock customer for update
        Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

        // Recalculate balances inside lock
        $now = now();
        $createdMovements = [];
        $totalQty = 0;
        $typeIds = [];

        foreach ($validItems as $v) {
            $type = $v['type'];
            $currentBal = $this->getCustomerBalance($customer, $type);

            if ($v['quantity'] > $currentBal) {
                throw new InvalidArgumentException("El cliente solo tiene {$currentBal} {$type->name} pendiente(s).");
            }

            $m = ReturnableMovement::create([
                'batch_token' => $batchToken,
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'returnable_type_id' => $type->id,
                'movement_type' => ReturnableMovementType::RETURN,
                'quantity' => $v['quantity'],
                'occurred_at' => $now,
                'user_id' => $user->id,
                'notes' => $notes,
            ]);

            $createdMovements[] = $m;
            $totalQty += $v['quantity'];
            $typeIds[] = $type->id;
        }

        return $createdMovements;
    }

    /**
     * Void a returnable movement with a required reason.
     */
    public function voidMovement(ReturnableMovement $movement, string $reason, User $user): ReturnableMovement
    {
        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('Debe proporcionar un motivo para anular el movimiento.');
        }

        $movementToBroadcast = null;

        DB::transaction(function () use ($movement, $reason, $user, &$movementToBroadcast) {
            // Lock movement and customer
            $lockedMovement = ReturnableMovement::where('id', $movement->id)->lockForUpdate()->firstOrFail();

            if ($lockedMovement->isVoided()) {
                throw new InvalidArgumentException('Este movimiento ya se encuentra anulado.');
            }

            Customer::where('id', $lockedMovement->customer_id)->lockForUpdate()->firstOrFail();

            // If voiding an OUT movement, verify it won't result in a negative balance
            if ($lockedMovement->movement_type === ReturnableMovementType::OUT) {
                $type = $lockedMovement->type;
                $currentBal = $this->getCustomerBalance($lockedMovement->customer, $type);
                if ($currentBal - $lockedMovement->quantity < 0) {
                    throw new InvalidArgumentException('No se puede anular esta salida porque existen devoluciones posteriores asociadas al saldo.');
                }
            }

            $lockedMovement->update([
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
            ]);

            $movementToBroadcast = $lockedMovement;
        });

        $this->safeBroadcast(
            $movementToBroadcast->customer_id,
            $movementToBroadcast->order_id,
            $movementToBroadcast->movement_type->value,
            [$movementToBroadcast->returnable_type_id],
            $movementToBroadcast->quantity,
            'VOIDED'
        );

        return $movementToBroadcast->fresh();
    }

    /**
     * Safely dispatch broadcast event post-commit.
     */
    protected function safeBroadcast(
        int $customerId,
        ?int $orderId,
        string $movementType,
        array $typeIds,
        int $totalQuantity,
        string $action
    ): void {
        try {
            event(new ReturnableChanged(
                $customerId,
                $orderId,
                $movementType,
                $typeIds,
                $totalQuantity,
                $action
            ));
        } catch (\Throwable $e) {
            Log::warning('ReturnableChanged broadcast failed: ' . $e->getMessage());
        }
    }
}
