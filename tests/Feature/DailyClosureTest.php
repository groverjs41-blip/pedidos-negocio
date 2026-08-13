<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\DailyClosureChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyClosure;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\DailyClosureService;
use App\Services\OrderService;
use App\Services\PaymentService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyClosureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $cajaUser;
    protected User $repartoUser;
    protected User $cocinaUser;
    protected User $pedidosUser;

    protected Customer $customer;
    protected OrderService $orderService;
    protected PaymentService $paymentService;
    protected DailyClosureService $closureService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('slug', 'admin')->first() ?? Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $cajaRole = Role::where('slug', 'caja')->first() ?? Role::create(['name' => 'Caja', 'slug' => 'caja']);
        $repartoRole = Role::where('slug', 'reparto')->first() ?? Role::create(['name' => 'Reparto', 'slug' => 'reparto']);
        $cocinaRole = Role::where('slug', 'cocina')->first() ?? Role::create(['name' => 'Cocina', 'slug' => 'cocina']);
        $pedidosRole = Role::where('slug', 'pedidos')->first() ?? Role::create(['name' => 'Pedidos', 'slug' => 'pedidos']);

        $this->adminUser = User::factory()->create(['active' => true]);
        $this->adminUser->roles()->attach($adminRole);

        $this->cajaUser = User::factory()->create(['active' => true]);
        $this->cajaUser->roles()->attach($cajaRole);

        $this->repartoUser = User::factory()->create(['active' => true]);
        $this->repartoUser->roles()->attach($repartoRole);

        $this->cocinaUser = User::factory()->create(['active' => true]);
        $this->cocinaUser->roles()->attach($cocinaRole);

        $this->pedidosUser = User::factory()->create(['active' => true]);
        $this->pedidosUser->roles()->attach($pedidosRole);

        $this->customer = Customer::create([
            'name' => 'Cliente Cierre Test',
            'phone' => '5558888',
            'active' => true,
        ]);

        $this->orderService = app(OrderService::class);
        $this->paymentService = app(PaymentService::class);
        $this->closureService = app(DailyClosureService::class);
    }

    protected function createDeliveredOrder(Customer $customer, string $amount = '100.00'): Order
    {
        $cat = Category::create(['name' => 'Cat ' . Str::random(4), 'active' => true]);
        $prod = Product::create([
            'category_id' => $cat->id,
            'name' => 'Prod ' . Str::random(4),
            'price' => $amount,
            'estimated_cost' => '10.00',
            'active' => true,
        ]);

        $order = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'items' => [['product_id' => $prod->id, 'quantity' => 1]],
        ], $this->adminUser);

        $this->orderService->startPreparing($order, $this->adminUser);
        $this->orderService->markReady($order, $this->adminUser);
        $this->orderService->claimForDelivery($order, $this->adminUser);
        $this->orderService->markDelivered($order, $this->adminUser);

        return $order;
    }

    public function test_get_daily_summary_live_calculations()
    {
        $order = $this->createDeliveredOrder($this->customer, '150.00');

        $this->paymentService->recordCustomerPayment(
            $this->customer,
            '100.00',
            PaymentMethod::CASH,
            'REF1',
            'Nota',
            $this->cajaUser,
            (string) Str::uuid()
        );

        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');
        $summary = $this->closureService->getDailySummary($todayStr);

        $this->assertEquals($todayStr, $summary['business_date']);
        $this->assertFalse($summary['is_closed']);
        $this->assertEquals(1, $summary['orders_count']);
        $this->assertEquals(1, $summary['orders_delivered_count']);
        $this->assertEquals(0, $summary['open_orders_count']);
        $this->assertEquals('150.00', $summary['gross_sales']);
        $this->assertEquals('100.00', $summary['total_collected']);
        $this->assertEquals('100.00', $summary['collected_by_method'][PaymentMethod::CASH->value]);
    }

    public function test_successful_normal_daily_closure()
    {
        $order = $this->createDeliveredOrder($this->customer, '200.00');
        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');

        $closure = $this->closureService->closeDay($todayStr, $this->cajaUser, false, null, 'Cierre normal ok');

        $this->assertEquals($todayStr, $closure->business_date->format('Y-m-d'));
        $this->assertFalse($closure->forced);
        $this->assertEquals('Cierre normal ok', $closure->notes);
        $this->assertNotNull($closure->snapshot);
        $this->assertEquals('200.00', $closure->snapshot['gross_sales']);
        $this->assertTrue($this->closureService->isClosed($todayStr));
    }

    public function test_close_day_with_open_orders_rejected_if_not_forced()
    {
        // Create an open order (status NEW)
        $cat = Category::create(['name' => 'Cat Open', 'active' => true]);
        $prod = Product::create(['category_id' => $cat->id, 'name' => 'Prod', 'price' => '50.00', 'estimated_cost' => '5.00', 'active' => true]);
        $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $prod->id, 'quantity' => 1]],
        ], $this->adminUser);

        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Existen 1 pedidos pendientes de entrega o cancelación.');

        $this->closureService->closeDay($todayStr, $this->cajaUser, false);
    }

    public function test_successful_forced_daily_closure_with_reason()
    {
        // Open order exists
        $cat = Category::create(['name' => 'Cat Forced', 'active' => true]);
        $prod = Product::create(['category_id' => $cat->id, 'name' => 'Prod', 'price' => '50.00', 'estimated_cost' => '5.00', 'active' => true]);
        $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'items' => [['product_id' => $prod->id, 'quantity' => 1]],
        ], $this->adminUser);

        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');

        $closure = $this->closureService->closeDay(
            $todayStr,
            $this->cajaUser,
            true,
            'Comanda #1 quedará pendiente para turno de mañana',
            'Notas de cierre forzado'
        );

        $this->assertTrue($closure->forced);
        $this->assertEquals('Comanda #1 quedará pendiente para turno de mañana', $closure->force_reason);
        $this->assertEquals(1, $closure->snapshot['open_orders_count']);
    }

    public function test_reclose_already_closed_day_rejected()
    {
        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');
        $this->closureService->closeDay($todayStr, $this->cajaUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ya ha sido cerrado previamente.');

        $this->closureService->closeDay($todayStr, $this->cajaUser);
    }

    public function test_immutable_snapshot_preservation()
    {
        $order = $this->createDeliveredOrder($this->customer, '100.00');
        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');

        $closure = $this->closureService->closeDay($todayStr, $this->cajaUser);
        $snapshotCollectedBefore = $closure->snapshot['total_collected'];

        // Subsequent payment added AFTER closure
        $this->paymentService->recordCustomerPayment(
            $this->customer,
            '50.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        // Fresh closure snapshot must remain identical to snapshot at close_time
        $freshClosure = DailyClosure::find($closure->id);
        $this->assertEquals($snapshotCollectedBefore, $freshClosure->snapshot['total_collected']);
        $this->assertEquals('0.00', $freshClosure->snapshot['total_collected']);
    }

    public function test_closure_role_authorization()
    {
        // Admin allowed
        $this->actingAs($this->adminUser)->get('/cierre')->assertStatus(200);

        // Caja allowed
        $this->actingAs($this->cajaUser)->get('/cierre')->assertStatus(200);

        // Reparto forbidden
        $this->actingAs($this->repartoUser)->get('/cierre')->assertStatus(403);

        // Cocina forbidden
        $this->actingAs($this->cocinaUser)->get('/cierre')->assertStatus(403);

        // Pedidos forbidden
        $this->actingAs($this->pedidosUser)->get('/cierre')->assertStatus(403);
    }

    public function test_filament_daily_closure_resource_smoke()
    {
        $this->actingAs($this->adminUser);

        $this->get('/admin/daily-closures')->assertStatus(200);
    }

    public function test_daily_closure_changed_event_dispatched()
    {
        Event::fake([DailyClosureChanged::class]);

        $todayStr = now(config('app.timezone', 'UTC'))->format('Y-m-d');
        $this->closureService->closeDay($todayStr, $this->cajaUser);

        Event::assertDispatched(DailyClosureChanged::class);
    }
}
