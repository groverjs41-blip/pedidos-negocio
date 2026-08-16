<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ServiceMode;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $orderService->startPreparing($directOrder, $this->user);

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
        $orderService->startPreparing($order, $this->user);
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

        $orderService->startPreparing($order, $this->user);

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

        $orderService->startPreparing($order, $this->user);
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
}
