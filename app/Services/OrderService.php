<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Events\OrderChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

                $requestedMode = isset($data['service_mode'])
                    ? ($data['service_mode'] instanceof ServiceMode ? $data['service_mode'] : ServiceMode::tryFrom($data['service_mode']))
                    : ServiceMode::KITCHEN;

                if ($requestedMode === ServiceMode::DIRECT) {
                    User::where('id', $creator->id)->lockForUpdate()->first();
                    $activeDirect = $this->findActiveDirectOrderForUser($creator);
                    if ($activeDirect) {
                        throw new \InvalidArgumentException('Ya existe una venta en puesto activa para este usuario.');
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
                $serviceMode = $data['service_mode'] ?? \App\Enums\ServiceMode::KITCHEN;
                if (is_string($serviceMode)) {
                    $serviceMode = \App\Enums\ServiceMode::from($serviceMode);
                }

                $order = Order::create([
                    'number' => $numberString,
                    'submission_token' => $data['submission_token'] ?? null,
                    'customer_id' => $customer?->id,
                    'customer_name_snapshot' => $customer?->name,
                    'customer_phone_snapshot' => $customer?->phone,
                    'delivery_address_snapshot' => $customer?->address,
                    'status' => OrderStatus::NEW,
                    'service_mode' => $serviceMode,
                    'subtotal' => $subtotal,
                    'total' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $creator->id,
                    'ordered_at' => now(),
                ]);

                // 5. Create Items and calculate returnable plans
                $plansToCreate = [];
                foreach ($itemsToCreate as $itemFields) {
                    $itemFields['order_id'] = $order->id;
                    OrderItem::create($itemFields);

                    $product = Product::with('returnableRequirements')->find($itemFields['product_id']);
                    if ($product && $product->returnableRequirements) {
                        foreach ($product->returnableRequirements as $req) {
                            $typeId = $req->returnable_type_id;
                            $needed = $itemFields['quantity'] * $req->quantity;
                            $plansToCreate[$typeId] = ($plansToCreate[$typeId] ?? 0) + $needed;
                        }
                    }
                }

                // 5b. Save Returnable Plans Snapshot
                foreach ($plansToCreate as $typeId => $planQty) {
                    if ($planQty > 0) {
                        \App\Models\OrderReturnablePlan::create([
                            'order_id' => $order->id,
                            'returnable_type_id' => $typeId,
                            'quantity' => $planQty,
                        ]);
                    }
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
            
            if ($lockedOrder->service_mode === \App\Enums\ServiceMode::DIRECT) {
                throw new \Exception('Los pedidos en puesto deben procesarse desde la pantalla de toma de pedidos.');
            }

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
     * Start preparing a batch of KITCHEN orders in kitchen atomically.
     *
     * @param array<int> $orderIds
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function startPreparingBatch(array $orderIds, User $user): \Illuminate\Support\Collection
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));

        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un pedido para iniciar la preparación por lote.');
        }

        $preparedOrders = collect();
        $batchToken = (string) Str::uuid();

        DB::transaction(function () use ($orderIds, $user, $batchToken, &$preparedOrders) {
            $lockedOrders = Order::whereIn('id', $orderIds)->lockForUpdate()->get();

            if ($lockedOrders->count() !== count($orderIds)) {
                throw new \InvalidArgumentException('El lote cambió mientras lo seleccionabas. Actualiza la selección e intenta nuevamente.');
            }

            $now = now();

            foreach ($lockedOrders as $lockedOrder) {
                if ($lockedOrder->service_mode !== \App\Enums\ServiceMode::KITCHEN) {
                    throw new \InvalidArgumentException('El lote cambió mientras lo seleccionabas. Actualiza la selección e intenta nuevamente.');
                }

                if ($lockedOrder->status !== OrderStatus::NEW) {
                    throw new \InvalidArgumentException('El lote cambió mientras lo seleccionabas. Actualiza la selección e intenta nuevamente.');
                }

                $lockedOrder->update([
                    'status' => OrderStatus::PREPARING,
                    'preparing_at' => $now,
                    'kitchen_batch_token' => $batchToken,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => OrderStatus::NEW,
                    'to_status' => OrderStatus::PREPARING,
                    'user_id' => $user->id,
                    'notes' => 'Comenzó la preparación en lote de cocina.',
                ]);

                $preparedOrders->push($lockedOrder);
            }
        });

        foreach ($preparedOrders as $order) {
            $this->notifyAndBroadcast($order, 'PREPARING', OrderStatus::NEW->value);
        }

        return $preparedOrders;
    }

    /**
     * Mark all or remaining PREPARING orders in a batch as READY atomically.
     *
     * @param string $batchToken
     * @param User $user
     * @param array<int>|null $expectedOrderIds
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function markReadyBatch(string $batchToken, User $user, ?array $expectedOrderIds = null): \Illuminate\Support\Collection
    {
        if (empty($batchToken)) {
            throw new \InvalidArgumentException('El identificador de lote no es válido.');
        }

        $readyOrders = collect();

        DB::transaction(function () use ($batchToken, $user, $expectedOrderIds, &$readyOrders) {
            $allBatchOrders = Order::where('kitchen_batch_token', $batchToken)
                ->where('service_mode', \App\Enums\ServiceMode::KITCHEN)
                ->lockForUpdate()
                ->get();

            if ($allBatchOrders->isEmpty()) {
                throw new \InvalidArgumentException('El lote cambió mientras trabajabas. Actualiza Cocina e intenta nuevamente.');
            }

            if ($expectedOrderIds !== null) {
                $expectedIds = array_values(array_unique(array_map('intval', $expectedOrderIds)));
                $preparingOrders = $allBatchOrders->filter(fn($o) => $o->status === OrderStatus::PREPARING && in_array($o->id, $expectedIds));

                if ($preparingOrders->count() !== count($expectedIds)) {
                    throw new \InvalidArgumentException('El lote cambió mientras trabajabas. Actualiza Cocina e intenta nuevamente.');
                }
            } else {
                // For a full batch completion, no orders in the batch can be non-PREPARING (e.g. already READY or CANCELLED)
                $hasNonPreparing = $allBatchOrders->contains(fn($o) => $o->status !== OrderStatus::PREPARING);
                if ($hasNonPreparing) {
                    throw new \InvalidArgumentException('El lote cambió mientras trabajabas. Actualiza Cocina e intenta nuevamente.');
                }
                $preparingOrders = $allBatchOrders;
            }

            $now = now();

            foreach ($preparingOrders as $lockedOrder) {
                if ($lockedOrder->status !== OrderStatus::PREPARING || $lockedOrder->service_mode !== \App\Enums\ServiceMode::KITCHEN) {
                    throw new \InvalidArgumentException('El lote cambió mientras trabajabas. Actualiza Cocina e intenta nuevamente.');
                }

                $lockedOrder->update([
                    'status' => OrderStatus::READY,
                    'ready_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => OrderStatus::PREPARING,
                    'to_status' => OrderStatus::READY,
                    'user_id' => $user->id,
                    'notes' => 'Pedido marcado listo como parte de lote de cocina.',
                ]);

                $readyOrders->push($lockedOrder);
            }
        });

        foreach ($readyOrders as $order) {
            $this->notifyAndBroadcast($order, 'READY', OrderStatus::PREPARING->value);
        }

        return $readyOrders;
    }

    /**
     * Start preparing a DIRECT order.
     */
    public function startDirectPreparing(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->service_mode !== \App\Enums\ServiceMode::DIRECT) {
                throw new \Exception('Esta función solo es para ventas en puesto.');
            }

            if ($lockedOrder->status !== OrderStatus::NEW) {
                throw new \Exception('Solo se pueden empezar a preparar ventas en puesto con estado "Nuevo".');
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
                'notes' => 'Comenzó la preparación en puesto.',
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

            if ($lockedOrder->service_mode === \App\Enums\ServiceMode::DIRECT) {
                throw new \Exception('Los pedidos en puesto no pueden pasar al estado Listo.');
            }

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

            if ($lockedOrder->service_mode === \App\Enums\ServiceMode::DIRECT) {
                throw new \Exception('Los pedidos en puesto no pueden pasar al estado En reparto.');
            }

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
     * Claim a batch of READY KITCHEN orders for delivery by current user.
     * Manual selection pickup is restricted to orders without a kitchen_batch_token.
     *
     * @param array<int> $orderIds
     * @param User $user
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function claimForDeliveryBatch(array $orderIds, User $user): \Illuminate\Support\Collection
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));

        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Debe seleccionar al menos un pedido para iniciar la salida.');
        }

        $claimedOrders = collect();

        DB::transaction(function () use ($orderIds, $user, &$claimedOrders) {
            $lockedOrders = Order::whereIn('id', $orderIds)->lockForUpdate()->get();

            if ($lockedOrders->count() !== count($orderIds)) {
                throw new \InvalidArgumentException('Uno de los pedidos ya fue tomado. Actualiza la selección e intenta nuevamente.');
            }

            $now = now();

            foreach ($lockedOrders as $lockedOrder) {
                if ($lockedOrder->service_mode !== \App\Enums\ServiceMode::KITCHEN) {
                    throw new \InvalidArgumentException('Uno de los pedidos ya fue tomado. Actualiza la selección e intenta nuevamente.');
                }

                if ($lockedOrder->status !== OrderStatus::READY) {
                    throw new \InvalidArgumentException('Uno de los pedidos ya fue tomado. Actualiza la selección e intenta nuevamente.');
                }

                if ($lockedOrder->kitchen_batch_token !== null) {
                    throw new \InvalidArgumentException('Los pedidos pertenecientes a un lote de cocina deben recogerse con la acción de lote.');
                }

                $lockedOrder->update([
                    'status' => OrderStatus::DELIVERING,
                    'delivery_user_id' => $user->id,
                    'delivering_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => OrderStatus::READY,
                    'to_status' => OrderStatus::DELIVERING,
                    'user_id' => $user->id,
                    'notes' => 'Pedido asignado en salida por lote para reparto.',
                ]);

                $claimedOrders->push($lockedOrder);
            }
        });

        foreach ($claimedOrders as $order) {
            $this->notifyAndBroadcast($order, 'DELIVERING', OrderStatus::READY->value);
        }

        return $claimedOrders;
    }

    /**
     * Claim an entire Kitchen batch for delivery by current user using kitchen_batch_token.
     *
     * @param string $kitchenBatchToken
     * @param User $user
     * @return \Illuminate\Support\Collection<int, Order>
     */
    public function claimKitchenBatchForDelivery(string $kitchenBatchToken, User $user): \Illuminate\Support\Collection
    {
        $token = trim($kitchenBatchToken);
        if (empty($token)) {
            throw new \InvalidArgumentException('El token del lote de cocina no es válido.');
        }

        $claimedOrders = collect();

        DB::transaction(function () use ($token, $user, &$claimedOrders) {
            $lockedOrders = Order::where('kitchen_batch_token', $token)
                ->lockForUpdate()
                ->get();

            if ($lockedOrders->isEmpty()) {
                throw new \InvalidArgumentException('El lote no existe o ya fue tomado por otro repartidor.');
            }

            $now = now();

            foreach ($lockedOrders as $order) {
                if ($order->service_mode !== \App\Enums\ServiceMode::KITCHEN) {
                    throw new \InvalidArgumentException('El lote contiene pedidos que no corresponden a Cocina.');
                }

                if ($order->status !== OrderStatus::READY) {
                    throw new \InvalidArgumentException('El lote cambió de estado o ya fue tomado por otro repartidor.');
                }

                if ($order->delivery_user_id !== null) {
                    throw new \InvalidArgumentException('El lote cambió de estado o ya fue tomado por otro repartidor.');
                }
            }

            foreach ($lockedOrders as $lockedOrder) {
                $lockedOrder->update([
                    'status' => OrderStatus::DELIVERING,
                    'delivery_user_id' => $user->id,
                    'delivering_at' => $now,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $lockedOrder->id,
                    'from_status' => OrderStatus::READY,
                    'to_status' => OrderStatus::DELIVERING,
                    'user_id' => $user->id,
                    'notes' => 'Lote de cocina recogido para reparto.',
                ]);

                $claimedOrders->push($lockedOrder);
            }
        });

        foreach ($claimedOrders as $order) {
            $this->notifyAndBroadcast($order, 'DELIVERING', OrderStatus::READY->value);
        }

        return $claimedOrders;
    }

    /**
     * Mark a DIRECT order as delivered (PREPARING -> DELIVERED).
     */
    public function markDirectDelivered(Order $order, User $user): void
    {
        $previousStatus = $order->status;

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->service_mode !== \App\Enums\ServiceMode::DIRECT) {
                throw new \Exception('Esta función solo es para ventas en puesto.');
            }

            if ($lockedOrder->status !== OrderStatus::PREPARING) {
                throw new \Exception('El pedido en puesto debe estar en preparación para marcarse como entregado.');
            }

            $lockedOrder->update([
                'status' => OrderStatus::DELIVERED,
                'delivered_at' => now(),
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'from_status' => OrderStatus::PREPARING,
                'to_status' => OrderStatus::DELIVERED,
                'user_id' => $user->id,
                'notes' => 'Venta en puesto entregada.',
            ]);
        });

        $order->refresh();
        $this->notifyAndBroadcast($order, 'DELIVERED', $previousStatus->value);
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

    /**
     * Find active direct order for a user (NEW, PREPARING, or DELIVERED requiring payment or returnables resolution).
     */
    public function findActiveDirectOrderForUser(User|int $user): ?Order
    {
        $userId = $user instanceof User ? $user->id : $user;

        $candidates = Order::where('created_by', $userId)
            ->where('service_mode', ServiceMode::DIRECT)
            ->whereIn('status', [OrderStatus::NEW, OrderStatus::PREPARING, OrderStatus::DELIVERED])
            ->with(['items', 'paymentAllocations', 'returnablePlans.returnableType', 'returnableMovements'])
            ->orderBy('id', 'desc')
            ->get();

        foreach ($candidates as $ord) {
            if (in_array($ord->status, [OrderStatus::NEW, OrderStatus::PREPARING])) {
                return $ord;
            }

            if ($ord->status === OrderStatus::DELIVERED) {
                $hasBalance = bccomp($ord->outstandingBalance(), '0.00', 2) > 0;
                $needsReturnables = $ord->customer_id 
                    && $ord->returnablePlans->count() > 0 
                    && is_null($ord->direct_returnables_resolved_at)
                    && !$ord->returnableMovements()->where('movement_type', 'OUT')->exists();

                if ($hasBalance || $needsReturnables) {
                    return $ord;
                }
            }
        }

        return null;
    }
}
