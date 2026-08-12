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
     * Create a new order.
     *
     * @param  array  $data  Structure: [
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
        if (empty($data['items'])) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        $order = DB::transaction(function () use ($data, $creator) {
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('id', $data['customer_id'])->where('active', true)->first();
                if (!$customer) {
                    throw new \InvalidArgumentException('El cliente seleccionado está inactivo o no existe.');
                }
            }

            // 1. Generate Order Number atomatically
            $dateToday = now()->format('Y-m-d');
            $nextNumber = $this->getNextDailyNumber($dateToday);
            $numberString = now()->format('Ymd') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // 2. Fetch and Validate products, calculate totals
            $subtotal = 0.00;
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

                $lineTotal = $qty * $product->price;
                $subtotal += $lineTotal;

                $itemsToCreate[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'unit_cost_snapshot' => $product->estimated_cost,
                    'line_total' => $lineTotal,
                ];
            }

            // 3. Create Order
            $order = Order::create([
                'number' => $numberString,
                'customer_id' => $customer?->id,
                'customer_name_snapshot' => $customer?->name,
                'customer_phone_snapshot' => $customer?->phone,
                'delivery_address_snapshot' => $customer?->address,
                'status' => OrderStatus::NEW,
                'subtotal' => $subtotal,
                'total' => $subtotal, // Subtotal equals total for now
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator->id,
                'ordered_at' => now(),
            ]);

            // 4. Create Items
            foreach ($itemsToCreate as $itemFields) {
                $itemFields['order_id'] = $order->id;
                OrderItem::create($itemFields);
            }

            // 5. Log History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => OrderStatus::NEW,
                'user_id' => $creator->id,
                'notes' => 'Pedido creado.',
            ]);

            return $order;
        });

        // 6. Broadcast event outside transaction
        broadcast(new OrderChanged($order, null))->toOthers();

        return $order;
    }

    /**
     * Update an existing NEW order.
     *
     * @param  Order  $order
     * @param  array  $data
     * @param  User  $user
     * @return void
     * @throws \Exception
     */
    public function updateNewOrder(Order $order, array $data, User $user): void
    {
        if ($order->status !== OrderStatus::NEW) {
            throw new \Exception('Solo se pueden editar pedidos con estado "Nuevo".');
        }

        if (empty($data['items'])) {
            throw new \InvalidArgumentException('El pedido debe tener al menos un producto.');
        }

        DB::transaction(function () use ($order, $data, $user) {
            $customer = null;
            if (!empty($data['customer_id'])) {
                $customer = Customer::where('id', $data['customer_id'])->where('active', true)->first();
                if (!$customer) {
                    throw new \InvalidArgumentException('El cliente seleccionado está inactivo o no existe.');
                }
            }

            $subtotal = 0.00;
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

                $lineTotal = $qty * $product->price;
                $subtotal += $lineTotal;

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
            $order->items()->delete();

            // Create new items
            foreach ($itemsToCreate as $itemFields) {
                $itemFields['order_id'] = $order->id;
                OrderItem::create($itemFields);
            }

            // Update order totals and notes
            $order->update([
                'customer_id' => $customer?->id,
                'customer_name_snapshot' => $customer?->name,
                'customer_phone_snapshot' => $customer?->phone,
                'delivery_address_snapshot' => $customer?->address,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => OrderStatus::NEW,
                'to_status' => OrderStatus::NEW,
                'user_id' => $user->id,
                'notes' => 'Pedido modificado.',
            ]);
        });

        broadcast(new OrderChanged($order, OrderStatus::NEW->value))->toOthers();
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
        broadcast(new OrderChanged($order, $previousStatus->value))->toOthers();
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
        broadcast(new OrderChanged($order, $previousStatus->value))->toOthers();
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
        broadcast(new OrderChanged($order, $previousStatus->value))->toOthers();
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
        broadcast(new OrderChanged($order, $previousStatus->value))->toOthers();
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

            if ($lockedOrder->status === OrderStatus::DELIVERING && !$user->hasRole('admin')) {
                throw new \Exception('Solo los administradores pueden cancelar un pedido que ya está en reparto.');
            }

            $lockedOrder->update([
                'status' => OrderStatus::CANCELLED,
                'cancelled_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => $lockedOrder->status,
                'to_status' => OrderStatus::CANCELLED,
                'user_id' => $user->id,
                'notes' => $reason ?? 'Pedido cancelado.',
            ]);
        });

        $order->refresh();
        broadcast(new OrderChanged($order, $previousStatus->value))->toOthers();
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
