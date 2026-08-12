<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $pedidosUser;
    protected User $cocinaUser;
    protected User $repartoUser;
    
    protected Category $activeCategory;
    protected Product $activeProduct;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderService = new OrderService();

        // 1. Setup Roles
        $adminRole = Role::where('slug', 'admin')->first() ?? Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $pedidosRole = Role::where('slug', 'pedidos')->first() ?? Role::create(['name' => 'Pedidos', 'slug' => 'pedidos']);
        $cocinaRole = Role::where('slug', 'cocina')->first() ?? Role::create(['name' => 'Cocina', 'slug' => 'cocina']);
        $repartoRole = Role::where('slug', 'reparto')->first() ?? Role::create(['name' => 'Reparto', 'slug' => 'reparto']);

        // 2. Setup Users
        $this->adminUser = User::factory()->create(['active' => true]);
        $this->adminUser->roles()->attach($adminRole);

        $this->pedidosUser = User::factory()->create(['active' => true]);
        $this->pedidosUser->roles()->attach($pedidosRole);

        $this->cocinaUser = User::factory()->create(['active' => true]);
        $this->cocinaUser->roles()->attach($cocinaRole);

        $this->repartoUser = User::factory()->create(['active' => true]);
        $this->repartoUser->roles()->attach($repartoRole);

        // 3. Setup Catalog
        $this->activeCategory = Category::factory()->create(['active' => true]);
        $this->activeProduct = Product::factory()->create([
            'category_id' => $this->activeCategory->id,
            'active' => true,
            'price' => 15.50,
            'estimated_cost' => 6.20,
        ]);
    }

    /**
     * Test order creation increments the daily number correctly.
     */
    public function test_create_order_generates_unique_number_and_increments(): void
    {
        $order1 = $this->orderService->createOrder([
            'customer_id' => null,
            'items' => [
                ['product_id' => $this->activeProduct->id, 'quantity' => 2]
            ]
        ], $this->pedidosUser);

        $order2 = $this->orderService->createOrder([
            'customer_id' => null,
            'items' => [
                ['product_id' => $this->activeProduct->id, 'quantity' => 1]
            ]
        ], $this->pedidosUser);

        $todayStr = now()->format('Ymd');
        $this->assertEquals("{$todayStr}-001", $order1->number);
        $this->assertEquals("{$todayStr}-002", $order2->number);
    }

    /**
     * Test item snapshots preserve values and calculations happen on backend.
     */
    public function test_order_item_saves_snapshot_and_backend_calculates_totals(): void
    {
        $order = $this->orderService->createOrder([
            'customer_id' => null,
            'items' => [
                ['product_id' => $this->activeProduct->id, 'quantity' => 3]
            ]
        ], $this->pedidosUser);

        // Modify the product after creation
        $this->activeProduct->update([
            'name' => 'Modified Burger Name',
            'price' => 25.00,
            'estimated_cost' => 12.00,
        ]);

        $item = $order->items()->first();

        // Snapshot values should be old product values
        $this->assertNotEquals('Modified Burger Name', $item->product_name);
        $this->assertEquals(15.50, (float) $item->unit_price);
        $this->assertEquals(6.20, (float) $item->unit_cost_snapshot);
        
        // Backend calculated total should be 3 * 15.50 = 46.50
        $this->assertEquals(46.50, (float) $item->line_total);
        $this->assertEquals(46.50, (float) $order->total);
    }

    /**
     * Test ordering inactive products or categories is prohibited.
     */
    public function test_inactive_product_or_category_cannot_be_ordered(): void
    {
        $inactiveProduct = Product::factory()->create([
            'category_id' => $this->activeCategory->id,
            'active' => false,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->orderService->createOrder([
            'items' => [['product_id' => $inactiveProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);
    }

    public function test_inactive_category_cannot_be_ordered(): void
    {
        $inactiveCategory = Category::factory()->create(['active' => false]);
        $productInInactiveCategory = Product::factory()->create([
            'category_id' => $inactiveCategory->id,
            'active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->orderService->createOrder([
            'items' => [['product_id' => $productInInactiveCategory->id, 'quantity' => 1]]
        ], $this->pedidosUser);
    }

    /**
     * Test quantity constraints.
     */
    public function test_quantity_less_than_one_not_allowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 0]]
        ], $this->pedidosUser);
    }

    /**
     * Test customer presence and snapshots.
     */
    public function test_order_without_customer_is_allowed(): void
    {
        $order = $this->orderService->createOrder([
            'customer_id' => null,
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        $this->assertNull($order->customer_id);
        $this->assertNull($order->customer_name_snapshot);
    }

    public function test_order_with_customer_saves_snapshots(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Maria Gomez',
            'phone' => '987654321',
            'address' => 'Av. San Martin 123',
            'active' => true,
        ]);

        $order = $this->orderService->createOrder([
            'customer_id' => $customer->id,
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals('Maria Gomez', $order->customer_name_snapshot);
        $this->assertEquals('987654321', $order->customer_phone_snapshot);
        $this->assertEquals('Av. San Martin 123', $order->delivery_address_snapshot);
    }

    /**
     * Test standard state transitions and history logs.
     */
    public function test_order_transitions_and_history_logging(): void
    {
        $order = $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        $this->assertEquals(OrderStatus::NEW, $order->status);
        $this->assertNotNull($order->ordered_at);

        // 1. NEW -> PREPARING
        $this->orderService->startPreparing($order, $this->cocinaUser);
        $this->assertEquals(OrderStatus::PREPARING, $order->status);
        $this->assertNotNull($order->preparing_at);

        // 2. PREPARING -> READY
        $this->orderService->markReady($order, $this->cocinaUser);
        $this->assertEquals(OrderStatus::READY, $order->status);
        $this->assertNotNull($order->ready_at);

        // 3. READY -> DELIVERING
        $this->orderService->claimForDelivery($order, $this->repartoUser);
        $this->assertEquals(OrderStatus::DELIVERING, $order->status);
        $this->assertEquals($this->repartoUser->id, $order->delivery_user_id);
        $this->assertNotNull($order->delivering_at);

        // 4. DELIVERING -> DELIVERED
        $this->orderService->markDelivered($order, $this->repartoUser);
        $this->assertEquals(OrderStatus::DELIVERED, $order->status);
        $this->assertNotNull($order->delivered_at);

        // Verifying status history exists
        $this->assertCount(5, $order->histories); // Creation log + 4 transitions
    }

    /**
     * Test forbidden transitions throw exceptions.
     */
    public function test_invalid_transitions_are_forbidden(): void
    {
        $order = $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        // NEW -> DELIVERED directly should fail
        $this->expectException(\Exception::class);
        $this->orderService->markDelivered($order, $this->repartoUser);
    }

    /**
     * Test terminal states cannot be modified.
     */
    public function test_terminal_states_cannot_be_changed(): void
    {
        $order = $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        $this->orderService->cancelOrder($order, $this->pedidosUser);
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);

        // Try to start preparing a cancelled order
        $this->expectException(\Exception::class);
        $this->orderService->startPreparing($order, $this->cocinaUser);
    }

    /**
     * Test concurrent delivery claims are prevented.
     */
    public function test_concurrent_claims_prevented(): void
    {
        $order = $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        $this->orderService->startPreparing($order, $this->cocinaUser);
        $this->orderService->markReady($order, $this->cocinaUser);

        // Driver A takes it
        $this->orderService->claimForDelivery($order, $this->repartoUser);
        $this->assertEquals(OrderStatus::DELIVERING, $order->status);

        // Driver B (another user) tries to claim it afterwards
        $anotherDriver = User::factory()->create(['active' => true]);
        
        $this->expectException(\Exception::class);
        $this->orderService->claimForDelivery($order, $anotherDriver);
    }

    /**
     * Test role authorization middleware boundaries.
     */
    public function test_role_authorization_boundaries(): void
    {
        $this->actingAs($this->pedidosUser);
        $this->get('/pedidos/nuevo')->assertStatus(200);
        $this->get('/cocina')->assertStatus(403);
        $this->get('/reparto')->assertStatus(403);

        $this->actingAs($this->cocinaUser);
        $this->get('/pedidos/nuevo')->assertStatus(403);
        $this->get('/cocina')->assertStatus(200);
        $this->get('/reparto')->assertStatus(403);

        // Admin has full operational access
        $this->actingAs($this->adminUser);
        $this->get('/pedidos/nuevo')->assertStatus(200);
        $this->get('/cocina')->assertStatus(200);
        $this->get('/reparto')->assertStatus(200);
    }

    /**
     * Test event broadcasting.
     */
    public function test_order_changed_realtime_broadcast(): void
    {
        Event::fake();

        $order = $this->orderService->createOrder([
            'items' => [['product_id' => $this->activeProduct->id, 'quantity' => 1]]
        ], $this->pedidosUser);

        Event::assertDispatched(OrderChanged::class);

        Event::fake(); // Reset fake
        $this->orderService->startPreparing($order, $this->cocinaUser);

        Event::assertDispatched(OrderChanged::class, function ($event) use ($order) {
            return $event->orderId === (string) $order->id && $event->status === OrderStatus::PREPARING->value;
        });
    }
}
