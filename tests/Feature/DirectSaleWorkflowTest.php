<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceMode;
use App\Events\OrderChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOperationalNotificationPreference;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class DirectSaleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'cocina'], ['name' => 'Cocina']);
        Role::firstOrCreate(['slug' => 'reparto'], ['name' => 'Reparto']);

        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        $this->category = Category::create(['name' => 'Platos', 'active' => true]);
        $this->product = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pollo Spatchcock',
            'price' => '35.00',
            'active' => true,
        ]);
    }

    public function test_existing_migrated_orders_default_to_kitchen(): void
    {
        $order = Order::create([
            'number' => 'PED-OLD-001',
            'status' => OrderStatus::NEW,
            'subtotal' => '35.00',
            'total' => '35.00',
            'created_by' => $this->user->id,
            'ordered_at' => now(),
        ]);

        $this->assertEquals(ServiceMode::KITCHEN, $order->fresh()->service_mode);
    }

    public function test_kitchen_order_appears_in_kitchen_queue(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->assertEquals(ServiceMode::KITCHEN, $order->service_mode);

        Livewire::test(\App\Livewire\Kitchen::class)
            ->assertSee($order->number);
    }

    public function test_direct_order_does_not_appear_in_kitchen_or_delivery_queues(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $directOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->assertEquals(ServiceMode::DIRECT, $directOrder->service_mode);

        // 1. Assert not in Kitchen component
        Livewire::test(\App\Livewire\Kitchen::class)
            ->assertDontSee($directOrder->number);

        // 2. Transition to PREPARING
        $orderService->startDirectPreparing($directOrder, $this->user);

        // 3. Transition to DELIVERED
        $orderService->markDirectDelivered($directOrder, $this->user);
        $this->assertEquals(OrderStatus::DELIVERED, $directOrder->fresh()->status);

        // 4. Assert not in Delivery component
        Livewire::test(\App\Livewire\Delivery::class)
            ->assertDontSee($directOrder->number);
    }

    public function test_direct_order_transitions_new_to_preparing_and_preparing_to_delivered(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->assertEquals(OrderStatus::NEW, $order->status);

        // Transition NEW -> PREPARING
        $orderService->startDirectPreparing($order, $this->user);
        $this->assertEquals(OrderStatus::PREPARING, $order->fresh()->status);

        // Transition PREPARING -> DELIVERED
        $orderService->markDirectDelivered($order, $this->user);
        $this->assertEquals(OrderStatus::DELIVERED, $order->fresh()->status);

        // Assert histories recorded
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'PREPARING',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'DELIVERED',
        ]);
    }

    public function test_direct_order_cannot_transition_to_ready_or_delivering(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $orderService->startDirectPreparing($order, $this->user);

        // Attempt markReady on DIRECT order
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Los pedidos en puesto no pueden pasar al estado Listo.');
        $orderService->markReady($order, $this->user);
    }

    public function test_direct_order_cannot_be_claimed_for_delivery(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Los pedidos en puesto no pueden pasar al estado En reparto.');
        $orderService->claimForDelivery($order, $this->user);
    }

    public function test_direct_payment_uses_payment_service_and_creates_allocation(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2], // 70.00
            ],
        ], $this->user);

        $orderService->startDirectPreparing($order, $this->user);
        $orderService->markDirectDelivered($order, $this->user);

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordOrderPayment(
            $order,
            '70.00',
            PaymentMethod::CASH,
            'REF-123',
            'Cobro venta en puesto',
            $this->user,
            (string) \Illuminate\Support\Str::uuid()
        );

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'amount' => '70.00',
            'method' => 'CASH',
        ]);

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => '70.00',
        ]);

        $this->assertEquals(PaymentStatus::PAID, $order->fresh()->paymentStatus());
    }

    public function test_counter_sale_and_registered_customer_with_direct_mode(): void
    {
        // 1. Counter Sale (Venta Mostrador) + DIRECT
        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCounterSale')
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder')
            ->assertHasNoErrors()
            ->assertSet('serviceMode', 'DIRECT');

        $this->assertNotNull($component->get('activeDirectOrderId'));

        // Complete order 1 so state resets
        $component->call('startDirectPreparing')
            ->call('markDirectDelivered')
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment');

        // 2. Registered Customer + DIRECT
        $customer = Customer::create(['name' => 'Juan Cliente', 'phone' => '77777777', 'active' => true]);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder')
            ->assertHasNoErrors();
    }

    public function test_mobile_cart_closes_after_successful_kitchen_order_submission(): void
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCounterSale')
            ->call('setServiceMode', 'KITCHEN')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder')
            ->assertDispatched('order-submitted-success')
            ->assertSet('cart', []);
    }

    public function test_partial_payment_does_not_close_sale_and_second_payment_completes_it(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2], // Total 70.00
            ],
        ], $this->user);

        $orderService->startDirectPreparing($order, $this->user);
        $orderService->markDirectDelivered($order, $this->user);

        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->set('directPaymentAmount', '40.00')
            ->set('directPaymentMethod', 'CASH')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'info', title: 'Pago Registrado', message: 'Pago registrado. Saldo pendiente: Bs 30.00')
            ->assertSet('activeDirectOrderId', $order->id)
            ->assertSet('directPaymentAmount', '30.00');

        $this->assertEquals(PaymentStatus::PARTIAL, $order->fresh()->paymentStatus());

        // Second payment of remaining 30.00 completes sale
        $component->set('directPaymentAmount', '30.00')
            ->set('directPaymentMethod', 'QR')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'success', title: 'Venta Completada', message: "Venta completada para el pedido #{$order->number}.")
            ->assertSet('activeDirectOrderId', null);

        $this->assertEquals(PaymentStatus::PAID, $order->fresh()->paymentStatus());
    }

    public function test_cannot_pay_direct_order_if_status_is_new_or_preparing(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        // 1. Try paying NEW order
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'error', title: 'Cobro no permitido', message: 'Solo se pueden registrar cobros en pedidos de venta en puesto en estado Entregado.');

        // 2. Try paying PREPARING order
        $orderService->startDirectPreparing($order, $this->user);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'error', title: 'Cobro no permitido', message: 'Solo se pueden registrar cobros en pedidos de venta en puesto en estado Entregado.');

        // 3. Deliver order and pay DELIVERED order
        $orderService->markDirectDelivered($order, $this->user);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'success', title: 'Venta Completada', message: "Venta completada para el pedido #{$order->number}.");
    }

    public function test_start_direct_preparing_rejects_kitchen_orders(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $kitchenOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Esta función solo es para ventas en puesto.');
        $orderService->startDirectPreparing($kitchenOrder, $this->user);
    }

    public function test_direct_order_does_not_dispatch_kitchen_notifications(): void
    {
        UserOperationalNotificationPreference::create([
            'user_id' => $this->user->id,
            'event_type' => 'ORDER_CREATED',
            'in_app_enabled' => true,
            'sound_enabled' => true,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $directOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        // Assert NO database notification created for user pointing to /cocina
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->user->id,
            'data->url' => '/cocina',
        ]);

        $orderService->startDirectPreparing($directOrder, $this->user);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->user->id,
            'data->url' => '/cocina',
        ]);
    }

    public function test_kitchen_order_dispatches_kitchen_notifications(): void
    {
        UserOperationalNotificationPreference::create([
            'user_id' => $this->user->id,
            'event_type' => 'ORDER_CREATED',
            'in_app_enabled' => true,
            'sound_enabled' => true,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $kitchenOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'data->url' => '/cocina',
        ]);
    }

    public function test_direct_order_returnables_recorded_correctly_and_counter_sale_bypasses_debt(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Sifón de Vidrio', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->product->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 1,
        ]);

        $customer = Customer::create(['name' => 'Cliente Envases', 'phone' => '88888888', 'active' => true]);

        // Real Livewire flow for registered customer
        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $orderId = $component->get('activeDirectOrderId');
        $this->assertNotNull($orderId);

        $component->call('startDirectPreparing')
            ->call('markDirectDelivered');

        // Check prefill quantity using plan quantity (2)
        $quantities = $component->get('directReturnableQuantities');
        $this->assertEquals(2, $quantities[$returnableType->id] ?? null);

        // Record returnables
        $component->call('recordDirectReturnables')
            ->assertDispatched('notify-toast');

        $this->assertDatabaseHas('returnable_movements', [
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 2,
            'movement_type' => 'OUT',
        ]);

        $this->assertNotNull(Order::find($orderId)->direct_returnables_resolved_at);

        // Call a second time - assert no duplicates created
        $component->call('recordDirectReturnables');
        $this->assertEquals(
            1,
            \App\Models\ReturnableMovement::where('order_id', $orderId)->count()
        );

        // Complete first order with payment so it's closed
        $component->set('directPaymentAmount', '70.00')
            ->call('submitDirectPayment');
        $this->assertNull($component->get('activeDirectOrderId'));

        // Counter Sale (no customer) direct sale does not record returnable debt
        $counterComp = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCounterSale')
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $counterOrderId = $counterComp->get('activeDirectOrderId');
        $counterComp->call('startDirectPreparing')
            ->call('markDirectDelivered')
            ->call('recordDirectReturnables');

        $this->assertDatabaseMissing('returnable_movements', [
            'order_id' => $counterOrderId,
        ]);
    }

    public function test_cart_cannot_be_modified_while_direct_sale_is_active(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        // 1. addToCart blocked
        $c1 = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('addToCart', $this->product->id)
            ->assertDispatched('notify-toast');
        $this->assertEmpty($c1->get('cart'));

        // 2. selectCounterSale blocked
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('selectCounterSale')
            ->assertDispatched('notify-toast');

        // 3. setServiceMode blocked
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('setServiceMode', 'DIRECT')
            ->assertDispatched('notify-toast');

        // 4. submitOrder blocked
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('submitOrder')
            ->assertDispatched('notify-toast');
    }

    public function test_recovery_of_active_direct_sale_on_page_reload_or_remount(): void
    {
        // 1. Create active DIRECT order via Livewire
        $comp1 = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCounterSale')
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $activeId = $comp1->get('activeDirectOrderId');
        $this->assertNotNull($activeId);

        // 2. Simulate page reload / new Livewire mount for same user
        $comp2 = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class);

        $this->assertEquals($activeId, $comp2->get('activeDirectOrderId'));

        // 3. Server prevents starting a second DIRECT order while first is active
        $comp2->call('selectCounterSale')
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder')
            ->assertDispatched('notify-toast', type: 'warning', title: 'Venta en Puesto en Curso');

        // 4. Transition & complete first order
        $comp2->call('startDirectPreparing')
            ->call('markDirectDelivered')
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment');

        $this->assertNull($comp2->get('activeDirectOrderId'));

        // 5. Fresh instance can now start a new DIRECT order
        $comp3 = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class);

        $this->assertNull($comp3->get('activeDirectOrderId'));

        $comp3->call('selectCounterSale')
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder')
            ->assertHasNoErrors();

        $this->assertNotNull($comp3->get('activeDirectOrderId'));
    }

    public function test_skip_direct_returnables_persists_resolution_timestamp(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Sifón de Vidrio', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->product->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 1,
        ]);

        $customer = Customer::create(['name' => 'Cliente Skip', 'phone' => '66666666', 'active' => true]);

        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $orderId = $component->get('activeDirectOrderId');

        $component->call('startDirectPreparing')
            ->call('markDirectDelivered')
            ->set('directPaymentAmount', '35.00')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'warning', title: 'Envases Pendientes');

        $this->assertNull(Order::find($orderId)->direct_returnables_resolved_at);

        // Explicit skip
        $component->call('skipDirectReturnables')
            ->assertDispatched('notify-toast', type: 'info', title: 'Sin Envases');

        $this->assertNotNull(Order::find($orderId)->direct_returnables_resolved_at);
        $this->assertNull($component->get('activeDirectOrderId'));

        // Re-mount component: should NOT recover completed order
        $remount = Livewire::actingAs($this->user)->test(\App\Livewire\CreateOrder::class);
        $this->assertNull($remount->get('activeDirectOrderId'));
    }

    public function test_skip_direct_returnables_with_partial_payment(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Botella', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->product->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 1,
        ]);

        $customer = Customer::create(['name' => 'Cliente Parcial', 'phone' => '55555555', 'active' => true]);

        $comp = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $orderId = $comp->get('activeDirectOrderId');

        $comp->call('startDirectPreparing')
            ->call('markDirectDelivered')
            ->set('directPaymentAmount', '15.00')
            ->call('submitDirectPayment');

        // Confirm skip returnables
        $comp->call('skipDirectReturnables');
        $this->assertNotNull(Order::find($orderId)->direct_returnables_resolved_at);

        // Remount component: recovers order because balance > 0
        $remount = Livewire::actingAs($this->user)->test(\App\Livewire\CreateOrder::class);
        $this->assertEquals($orderId, $remount->get('activeDirectOrderId'));
        $this->assertTrue($remount->get('directReturnablesHandled'));

        // Pay remaining balance (20.00): sale closes without asking for returnables again
        $remount->set('directPaymentAmount', '20.00')
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'success', title: 'Venta Completada');

        $this->assertNull($remount->get('activeDirectOrderId'));
    }

    public function test_invalid_skip_direct_returnables(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        // NEW status skip attempt
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('skipDirectReturnables')
            ->assertDispatched('notify-toast', type: 'error', title: 'Acción no válida');

        // PREPARING status skip attempt
        $orderService->startDirectPreparing($order, $this->user);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->set('activeDirectOrderId', $order->id)
            ->call('skipDirectReturnables')
            ->assertDispatched('notify-toast', type: 'error', title: 'Acción no válida');

        $this->assertNull($order->fresh()->direct_returnables_resolved_at);
    }

    public function test_order_service_prevents_simultaneous_direct_orders_for_same_user(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $order1 = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ya existe una venta en puesto activa para este usuario.');

        $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);
    }

    public function test_active_direct_order_does_not_block_kitchen_order_creation(): void
    {
        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        $directOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $kitchenOrder = $orderService->createOrder([
            'service_mode' => ServiceMode::KITCHEN,
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        $this->assertEquals(ServiceMode::KITCHEN, $kitchenOrder->service_mode);
    }

    public function test_direct_order_broadcasts_order_changed_without_kitchen_sound_or_notification(): void
    {
        Event::fake([OrderChanged::class]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $orderService->createOrder([
            'service_mode' => ServiceMode::DIRECT,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ], $this->user);

        // Event OrderChanged was dispatched
        Event::assertDispatched(OrderChanged::class, function ($event) {
            return $event->soundType === null && empty($event->targetUserIds);
        });

        // Database notifications table does not have kitchen notifications
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $this->user->id,
            'data->url' => '/cocina',
        ]);
    }

    public function test_reload_with_pending_returnables(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Botella 1L', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->product->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 2,
        ]);

        $customer = Customer::create(['name' => 'Cliente Envases Reload', 'phone' => '70000000', 'active' => true]);

        // 1. Crear DIRECT con cliente y producto retornable
        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $orderId = $component->get('activeDirectOrderId');
        $this->assertNotNull($orderId);

        // 2. NEW -> PREPARING -> DELIVERED
        $component->call('startDirectPreparing')
            ->call('markDirectDelivered');

        // 3. NO resolver envases
        $order = Order::find($orderId);
        $this->assertNull($order->direct_returnables_resolved_at);

        // 4. Simular nueva instancia/mount de CreateOrder
        $remount = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class);

        // 5. Debe recuperar activeDirectOrderId
        $this->assertEquals($orderId, $remount->get('activeDirectOrderId'));

        // 6. directReturnablesHandled = false
        $this->assertFalse($remount->get('directReturnablesHandled'));

        // 7. directReturnablesRecorded = false
        $this->assertFalse($remount->get('directReturnablesRecorded'));

        // 8. directReturnableBatchToken NO debe estar vacío
        $batchToken = $remount->get('directReturnableBatchToken');
        $this->assertNotEmpty($batchToken);

        // 9. Ejecutar recordDirectReturnables
        $remount->call('recordDirectReturnables');

        // 10. Debe crear movimiento OUT correctamente
        $movement = \App\Models\ReturnableMovement::where('order_id', $orderId)
            ->where('movement_type', 'OUT')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2, $movement->quantity);

        // 11. batch_token almacenado no debe estar vacío
        $this->assertNotEmpty($movement->batch_token);

        // 12. direct_returnables_resolved_at debe quedar NOT NULL
        $this->assertNotNull($order->fresh()->direct_returnables_resolved_at);
    }

    public function test_reload_after_skip_returnables(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Sifón', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->product->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 1,
        ]);

        $customer = Customer::create(['name' => 'Cliente Skip Reload', 'phone' => '71111111', 'active' => true]);

        // 1. Crear DIRECT con retornables
        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class)
            ->call('selectCustomer', $customer->id)
            ->call('setServiceMode', 'DIRECT')
            ->call('addToCart', $this->product->id)
            ->call('submitOrder');

        $orderId = $component->get('activeDirectOrderId');

        // 2. Entregar
        $component->call('startDirectPreparing')
            ->call('markDirectDelivered');

        // 3. Elegir "Continuar sin dejar envases"
        $component->call('skipDirectReturnables');

        // 4. Si todavía hay saldo, mantener venta activa
        $order = Order::find($orderId);
        $this->assertNotNull($order->direct_returnables_resolved_at);
        $this->assertTrue(bccomp($order->outstandingBalance(), '0.00', 2) > 0);

        // 5. Simular reload/mount
        $remount = Livewire::actingAs($this->user)
            ->test(\App\Livewire\CreateOrder::class);

        // 6. Comprobar directReturnablesHandled = true, directReturnablesRecorded = false
        $this->assertEquals($orderId, $remount->get('activeDirectOrderId'));
        $this->assertTrue($remount->get('directReturnablesHandled'));
        $this->assertFalse($remount->get('directReturnablesRecorded'));

        // 7. No volver a pedir resolución de envases (UI renders skip status)
        $remount->assertSee('Se continuó sin dejar envases');

        // 8. Completar el saldo y cerrar normalmente
        $remount->set('directPaymentAmount', (string) $order->outstandingBalance())
            ->call('submitDirectPayment')
            ->assertDispatched('notify-toast', type: 'success', title: 'Venta Completada');

        $this->assertNull($remount->get('activeDirectOrderId'));
    }
}
