<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReturnableMovementType;
use App\Events\ReturnableChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnableMovement;
use App\Models\ReturnableType;
use App\Models\Role;
use App\Models\User;
use App\Services\CollectionVisitService;
use App\Services\OrderService;
use App\Services\ReturnableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnableTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $cajaUser;
    protected User $repartoUser;
    protected User $cocinaUser;
    protected User $pedidosUser;

    protected Customer $activeCustomer;
    protected ReturnableType $tazaType;
    protected ReturnableType $vasoType;
    protected ReturnableService $returnableService;
    protected OrderService $orderService;
    protected CollectionVisitService $visitService;

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

        $this->activeCustomer = Customer::create([
            'name' => 'Cliente Envases Test',
            'phone' => '5559999',
            'active' => true,
        ]);

        $this->tazaType = ReturnableType::create(['name' => 'Taza', 'sort_order' => 1, 'active' => true]);
        $this->vasoType = ReturnableType::create(['name' => 'Vaso', 'sort_order' => 2, 'active' => true]);

        $this->returnableService = app(ReturnableService::class);
        $this->orderService = app(OrderService::class);
        $this->visitService = app(CollectionVisitService::class);
    }

    protected function createDeliveredOrder(Customer $customer = null): Order
    {
        $cat = Category::create(['name' => 'Comida', 'active' => true]);
        $prod = Product::create([
            'category_id' => $cat->id,
            'name' => 'Prod ' . Str::random(4),
            'price' => '100.00',
            'estimated_cost' => '10.00',
            'active' => true,
        ]);

        $order = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $customer?->id,
            'items' => [['product_id' => $prod->id, 'quantity' => 1]],
        ], $this->adminUser);

        $this->orderService->startPreparing($order, $this->adminUser);
        $this->orderService->markReady($order, $this->adminUser);
        $this->orderService->claimForDelivery($order, $this->adminUser);
        $this->orderService->markDelivered($order, $this->adminUser);

        return $order;
    }

    public function test_container_balance_calculation()
    {
        // OUT 5
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 5],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida 5 tazas');

        $this->assertEquals(5, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));

        // RETURN 2
        $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'Retorno 2 tazas');

        $this->assertEquals(3, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));

        // OUT 1
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 1],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida 1 taza');

        $this->assertEquals(4, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
    }

    public function test_non_negative_balance_rejection()
    {
        // OUT 2
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida 2 tazas');

        // Attempting RETURN 3 should throw Exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El cliente solo tiene 2 Taza pendiente(s)');

        $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid());
    }

    public function test_multitype_batch_registration()
    {
        $movements = $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
            ['returnable_type_id' => $this->vasoType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid(), null, 'Lote multitipo');

        $this->assertCount(2, $movements);
        $this->assertEquals(2, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
        $this->assertEquals(3, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->vasoType));
        $this->assertEquals(5, $this->returnableService->getCustomerTotalOutstanding($this->activeCustomer));
    }

    public function test_batch_token_idempotency()
    {
        $token = (string) Str::uuid();

        $m1 = $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, $token, null, 'Lote 1');

        $m2 = $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, $token, null, 'Lote 1 repetido');

        $this->assertEquals($m1[0]->id, $m2[0]->id);
        $this->assertEquals(1, ReturnableMovement::count());
        $this->assertEquals(2, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
    }

    public function test_inactive_customer_return_allowed()
    {
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida');

        // Deactivate customer
        $this->activeCustomer->update(['active' => false]);

        // Customer still owes 3
        $this->assertEquals(3, $this->returnableService->getCustomerTotalOutstanding($this->activeCustomer));

        // Attempt new manual OUT should fail
        try {
            $this->returnableService->recordOutBatch($this->activeCustomer, [
                ['returnable_type_id' => $this->tazaType->id, 'quantity' => 1],
            ], $this->adminUser, (string) Str::uuid(), null, 'Nueva salida inactiva');
            $this->fail('Expected InvalidArgumentException for inactive customer OUT.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('cliente inactivo', $e->getMessage());
        }

        // RETURN for inactive customer is ALLOWED
        $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid());

        $this->assertEquals(0, $this->returnableService->getCustomerTotalOutstanding($this->activeCustomer));
    }

    public function test_inactive_type_return_allowed_new_out_rejected()
    {
        // OUT 2 tazas
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida previa');

        // Deactivate Taza type
        $this->tazaType->update(['active' => false]);

        // New OUT for inactive type fails
        try {
            $this->returnableService->recordOutBatch($this->activeCustomer, [
                ['returnable_type_id' => $this->tazaType->id, 'quantity' => 1],
            ], $this->adminUser, (string) Str::uuid(), null, 'Nueva salida');
            $this->fail('Expected InvalidArgumentException for inactive type OUT.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('inactivo', $e->getMessage());
        }

        // RETURN for inactive type is ALLOWED
        $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid());

        $this->assertEquals(0, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
    }

    public function test_counter_sale_out_rejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Para dejar envases retornables debes asociar el pedido a un cliente.');

        $order = $this->createDeliveredOrder(null);

        $this->returnableService->recordOutBatch(
            new Customer(),
            [['returnable_type_id' => $this->tazaType->id, 'quantity' => 1]],
            $this->adminUser,
            (string) Str::uuid(),
            $order
        );
    }

    public function test_order_linked_out_validation()
    {
        $order = $this->createDeliveredOrder($this->activeCustomer);

        // Valid order-linked OUT
        $movements = $this->returnableService->recordOutBatch(
            $this->activeCustomer,
            [['returnable_type_id' => $this->tazaType->id, 'quantity' => 2]],
            $this->repartoUser,
            (string) Str::uuid(),
            $order
        );

        $this->assertEquals($order->id, $movements[0]->order_id);
    }

    public function test_void_return_restores_balance()
    {
        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid(), null, 'OUT 3');

        $retMovements = $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'RETURN 2');

        $this->assertEquals(1, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));

        // Void RETURN
        $this->returnableService->voidMovement($retMovements[0], 'Anular retorno', $this->adminUser);

        $this->assertEquals(3, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
    }

    public function test_void_out_rejected_if_negative_balance()
    {
        $outMovements = $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid(), null, 'OUT 3');

        $this->returnableService->recordReturnBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'RETURN 2');

        // Current balance = 1. Attempting to void the OUT 3 movement would make balance = 1 - 3 = -2!
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No se puede anular esta salida porque existen devoluciones posteriores');

        $this->returnableService->voidMovement($outMovements[0], 'Anular OUT que daría negativo', $this->adminUser);
    }

    public function test_combined_collection_visit()
    {
        $order = $this->createDeliveredOrder($this->activeCustomer); // Order = 100

        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 3],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida inicial');

        $this->assertEquals('100.00', $this->activeCustomer->outstandingBalance());
        $this->assertEquals(3, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));

        // Visit: Pay 40.00 + Return 2 Tazas
        $result = $this->visitService->recordVisit(
            $this->activeCustomer,
            ['amount' => '40.00', 'method' => PaymentMethod::CASH, 'reference' => 'VISITA1', 'notes' => 'Pago parcial'],
            ['items' => [['returnable_type_id' => $this->tazaType->id, 'quantity' => 2]], 'notes' => 'Devolución 2 tazas'],
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->assertNotNull($result['payment']);
        $this->assertCount(1, $result['returnables']);

        $this->assertEquals('60.00', $this->activeCustomer->fresh()->outstandingBalance());
        $this->assertEquals(1, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
    }

    public function test_visit_rollback_on_failure()
    {
        $order = $this->createDeliveredOrder($this->activeCustomer); // Debt = 100

        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida');

        // Attempt Visit: Pay 40.00 + Return 5 Tazas (only owes 2, so return part will fail!)
        try {
            $this->visitService->recordVisit(
                $this->activeCustomer,
                ['amount' => '40.00', 'method' => PaymentMethod::CASH],
                ['items' => [['returnable_type_id' => $this->tazaType->id, 'quantity' => 5]]],
                $this->cajaUser,
                (string) Str::uuid()
            );
            $this->fail('Expected InvalidArgumentException on visit.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('solo tiene 2', $e->getMessage());
        }

        // Entire visit must be rolled back: Debt remains 100.00, Tazas remain 2
        $this->assertEquals('100.00', $this->activeCustomer->fresh()->outstandingBalance());
        $this->assertEquals(2, $this->returnableService->getCustomerBalance($this->activeCustomer, $this->tazaType));
        $this->assertEquals(0, \App\Models\Payment::count());
    }

    public function test_returnables_role_authorization()
    {
        // Admin allowed
        $this->actingAs($this->adminUser)->get('/tazas')->assertStatus(200);

        // Caja allowed
        $this->actingAs($this->cajaUser)->get('/tazas')->assertStatus(200);

        // Reparto allowed
        $this->actingAs($this->repartoUser)->get('/tazas')->assertStatus(200);

        // Cocina forbidden
        $this->actingAs($this->cocinaUser)->get('/tazas')->assertStatus(403);

        // Pedidos forbidden
        $this->actingAs($this->pedidosUser)->get('/tazas')->assertStatus(403);
    }

    public function test_returnable_changed_event_dispatched()
    {
        Event::fake([ReturnableChanged::class]);

        $this->returnableService->recordOutBatch($this->activeCustomer, [
            ['returnable_type_id' => $this->tazaType->id, 'quantity' => 2],
        ], $this->adminUser, (string) Str::uuid(), null, 'Salida test');

        Event::assertDispatched(ReturnableChanged::class);
    }
}
