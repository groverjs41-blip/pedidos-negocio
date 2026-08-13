<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create a new order with idempotency token check.
     *
     * @param  array  $data  Structure: [
     *   'submission_token' => ?string,
     *   'customer_id' => ?int,
     *   'notes' => ?string,
     *   'items' => [
     *     ['product_id' => int, 'quantity' => int],
     *     ...
     *   ]
     * ]
     * @param  User  $creator
     * @return Order
     * @throws \Exception
     */
    public function createOrder(array $data, User $creator): Order
    {
        // 1. Idempotency check prior to database transaction
        if (!empty($data['submission_token'])) {
            $existing = Order::where('submission_token', $data['submission_token'])->first();
            if ($existing) {
                return $existing;
            }
        }

        if (empty($data['items'])) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        try {
            $order = DB::transaction(function () use ($data, $creator) {
                // Secondary check with lock inside the transaction
                if (!empty($data['submission_token'])) {
                    $existing = Order::where('submission_token', $data['submission_token'])->lockForUpdate()->first();
                    if ($existing) {
                        return $existing;
                    }
                }

                $customer = null;
                if (!empty($data['customer_id'])) {
                    $customer = Customer::where('id', $data['customer_id'])->where('active', true)->first();
                    if (!$customer) {
                        throw new \InvalidArgumentException('El cliente seleccionado está inactivo o no existe.');
                    }
                }

                // 2. Generate Order Number atomically
                $dateToday = now()->format('Y-m-d');
                $nextNumber = $this->getNextDailyNumber($dateToday);
                $numberString = now()->format('Ymd') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                // 3. Validate products and calculate totals using bcmath (no floats)
                $subtotal = '0.00';
                $itemsToCreate = [];

                foreach ($data['items'] as $itemData) {
                    $qty = (int) ($itemData['quantity'] ?? 0);
                    if ($qty < 1) {
                        throw new \InvalidArgumentException('La cantidad de cada producto debe ser al menos 1.');
                    }

                    $product = Product::where('id', $itemData['product_id'])
                        ->where('active', true)
                        ->first();

                    if (!$product) {
                        throw new \InvalidArgumentException('Uno de los productos seleccionados no existe o está inactivo.');
                    }

                    if (!$product->category->active) {
                        throw new \InvalidArgumentException("La categoría '{$product->category->name}' está inactiva y no se puede vender.");
                    }

                    // Precise calculation with scale 2
                    $lineTotal = bcmul((string) $qty, (string) $product->price, 2);
                    $subtotal = bcadd($subtotal, $lineTotal, 2);

                    $itemsToCreate[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'unit_cost_snapshot' => $product->estimated_cost,
                        'line_total' => $lineTotal,
                    ];
                }

                // 4. Create Order
                $order = Order::create([
                    'number' => $numberString,
                    'submission_token' => $data['submission_token'] ?? null,
                    'customer_id' => $customer?->id,
                    'customer_name_snapshot' => $customer?->name,
                    'customer_phone_snapshot' => $customer?->phone,
                    'delivery_address_snapshot' => $customer?->address,
                    'status' => OrderStatus::NEW,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $creator->id,
                    'ordered_at' => now(),
                ]);

                // 5. Create Items
                foreach ($itemsToCreate as $itemFields) {
                    $itemFields['order_id'] = $order->id;
                    OrderItem::create($itemFields);
                }

                // 6. Log History
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => OrderStatus::NEW,
                    'user_id' => $creator->id,
                    'notes' => 'Pedido creado.',
                ]);

                return $order;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Catch concurrent duplicate key exception for submission_token
            if ($e->getCode() == '23000' && !empty($data['submission_token'])) {
                $existing = Order::where('submission_token', $data['submission_token'])->first();
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }

        // 7. Dispatch Operational Notifications & Event
        $this->notifyAndBroadcast($order, 'ORDER_CREATED', null);

        return $order;
    }

    /**
     * Update an existing NEW order.
     */
    public function updateNewOrder(Order $order, array $data, User $user): void
    {
        if (empty($data['items'])) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        DB::transaction(function () use ($order, $data, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::NEW) {
                throw new \Exception('Solo se pueden editar pedidos con estado "Nuevo".');
            }

            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('id', $data['customer_id'])->where('active', true)->first();
                if (!$customer) {
                    throw new \InvalidArgumentException('El cliente seleccionado está inactivo o no existe.');
                }
            }

            $subtotal = '0.00';
            $itemsToCreate = [];

            foreach ($data['items'] as $itemData) {
                $qty = (int) ($itemData['quantity'] ?? 0);
                if ($qty < 1) {
                    throw new \InvalidArgumentException('La cantidad debe ser al menos 1.');
                }

                $product = Product::where('id', $itemData['product_id'])
                    ->where('active', true)
                    ->first();

                if (!$product) {
                    throw new \InvalidArgumentException('Uno de los productos seleccionados no existe o está inactivo.');
                }

                if (!$product->category->active) {
                    throw new \InvalidArgumentException("La categoría '{$product->category->name}' está inactiva.");
                }

                $lineTotal = bcmul((string) $qty, (string) $product->price, 2);
                $subtotal = bcadd($subtotal, $lineTotal, 2);

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'unit_cost_snapshot' => $product->estimated_cost,
                    'line_total' => $lineTotal,
                ];
            }

            // Remove old items
            $lockedOrder->items()->delete();

            // Create new items
            foreach ($itemsToCreate as $itemFields) {
                $itemFields['order_id'] = $lockedOrder->id;
                OrderItem::create($itemFields);
            }

            // Update order totals and notes
            $lockedOrder->update([
                'customer_id' => $customer?->id,
                'customer_name_snapshot' => $customer?->name,
                'customer_phone_snapshot' => $customer?->phone,
                'delivery_address_snapshot' => $customer?->address,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::NEW,
                'to_status' => OrderStatus::NEW,
                'user_id' => $user->id,
                'notes' => 'Pedido modificado.',
            ]);
        });

        $this->broadcastOrderChanged($order, OrderStatus::NEW->value);
    }

    /**
     * Start preparing the order in kitchen.
     */
    public function startPreparing(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            
            if ($lockedOrder->status !== OrderStatus::NEW) {
                throw new \Exception('Solo se pueden empezar pedidos con estado "Nuevo".');
            }

            $lockedOrder->update([
                'status' => OrderStatus::PREPARING,
                'preparing_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::NEW,
                'to_status' => OrderStatus::PREPARING,
                'user_id' => $user->id,
                'notes' => 'Comenzó la preparación en cocina.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'PREPARING', $previousStatus->value);
    }

    /**
     * Mark order as ready in kitchen.
     */
    public function markReady(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::PREPARING) {
                throw new \Exception('Solo se pueden marcar listos pedidos en estado "Preparando".');
            }

            $lockedOrder->update([
                'status' => OrderStatus::READY,
                'ready_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::PREPARING,
                'to_status' => OrderStatus::READY,
                'user_id' => $user->id,
                'notes' => 'Pedido listo para entrega.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'READY', $previousStatus->value);
    }

    /**
     * Claim order for delivery by current user.
     */
    public function claimForDelivery(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::READY) {
                throw new \Exception('Este pedido ya fue tomado por otro repartidor.');
            }

            $lockedOrder->update([
                'status' => OrderStatus::DELIVERING,
                'delivery_user_id' => $user->id,
                'delivering_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::READY,
                'to_status' => OrderStatus::DELIVERING,
                'user_id' => $user->id,
                'notes' => 'Pedido tomado para reparto.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'DELIVERING', $previousStatus->value);
    }

    /**
     * Mark delivery as completed.
     */
    public function markDelivered(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::DELIVERING) {
                throw new \Exception('El pedido debe estar en reparto para marcarse como entregado.');
            }

            if (!$user->hasRole('admin') && $lockedOrder->delivery_user_id !== $user->id) {
                throw new \Exception('Solo el repartidor asignado o un administrador puede entregar este pedido.');
            }

            $lockedOrder->update([
                'status' => OrderStatus::DELIVERED,
                'delivered_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::DELIVERING,
                'to_status' => OrderStatus::DELIVERED,
                'user_id' => $user->id,
                'notes' => 'Pedido entregado al cliente.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'DELIVERED', $previousStatus->value);
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(Order $order, User $user, ?string $reason = null): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user, $reason) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedOrder->status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED])) {
                throw new \Exception('No se puede cancelar un pedido entregado o ya cancelado.');
            }

            // Check for active (non-voided) payment allocations
            $activePaymentCount = $lockedOrder->paymentAllocations()
                ->whereHas('payment', function ($query) {
                    $query->whereNull('voided_at');
                })->count();

            if ($activePaymentCount > 0) {
                throw new \Exception('Este pedido tiene pagos registrados. Anula primero los pagos asociados antes de cancelar el pedido.');
            }

            if ($lockedOrder->status === OrderStatus::DELIVERING && !$user->hasRole('admin')) {
                throw new \Exception('Solo los administradores pueden cancelar un pedido que ya está en reparto.');
            }

            // Save state before modification to avoid logging CANCELLED -> CANCELLED
            $fromStatus = $lockedOrder->status;

            $lockedOrder->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $fromStatus,
                'to_status' => OrderStatus::CANCELLED,
                'user_id' => $user->id,
                'notes' => $reason ?? 'Pedido cancelado.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'CANCELLED', $previousStatus->value);
    }

    /**
     * Dispatch operational notifications and broadcast event safely.
     */
    protected function notifyAndBroadcast(Order $order, string $action, ?string $previousStatus = null): void
    {
        try {
            app(OperationalNotificationService::class)->notifyOrderStatusChange($order, $action, $previousStatus);
        } catch (\Throwable $e) {
            logger()->warning("Failed dispatching operational notification for order {$order->number}. Message: " . $e->getMessage());
        }
    }

    /**
     * Atomically get the next number for the daily counter.
     */
    protected function getNextDailyNumber(string $date): int
    {
        $counter = DB::table('order_daily_counters')
            ->where('date', $date)
            ->lockForUpdate()
            ->first();

        if (!$counter) {
            try {
                DB::table('order_daily_counters')->insert([
                    'date' => $date,
                    'last_number' => 1,
                ]);
                return 1;
            } catch (\Illuminate\Database\QueryException $e) {
                $counter = DB::table('order_daily_counters')
                    ->where('date', $date)
                    ->lockForUpdate()
                    ->first();
                $next = $counter->last_number + 1;
                DB::table('order_daily_counters')
                    ->where('date', $date)
                    ->update(['last_number' => $next]);
                return $next;
            }
        }

        $next = $counter->last_number + 1;
        DB::table('order_daily_counters')
            ->where('date', $date)
            ->update(['last_number' => $next]);

        return $next;
    }
}
