<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class KitchenBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $cocinaUser;
    protected Category $category;
    protected Product $productHamburguesa;
    protected Product $productJugo;
    protected Product $productPapas;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $cocinaRole = Role::firstOrCreate(['slug' => 'cocina'], ['name' => 'Cocina']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        $this->cocinaUser = User::factory()->create(['active' => true]);
        $this->cocinaUser->roles()->attach($cocinaRole);

        $this->category = Category::create(['name' => 'Comida', 'active' => true]);

        $this->productHamburguesa = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Hamburguesa',
            'price' => '25.00',
            'active' => true,
        ]);

        $this->productJugo = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Jugo',
            'price' => '10.00',
            'active' => true,
        ]);

        $this->productPapas = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Papas',
            'price' => '15.00',
            'active' => true,
        ]);

        $this->orderService = app(OrderService::class);
    }

    /**
     * 12. Cocina continúa filtrando exclusivamente KITCHEN.
     */
    public function test_kitchen_filters_exclusively_kitchen_service_mode(): void
    {
        $kitchenOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $directOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::DIRECT,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->assertSee($kitchenOrder->number)
            ->assertDontSee($directOrder->number);
    }

    /**
     * 1, 2. Seleccionar varios NEW y verificar que el resumen agrupa correctamente cantidades y notas.
     */
    public function test_batch_selection_and_smart_summary_aggregation(): void
    {
        // Pedido A: 2 Hamburguesas, 1 Jugo, nota "Sin cebolla"
        $orderA = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'notes' => 'Sin cebolla',
            'items' => [
                ['product_id' => $this->productHamburguesa->id, 'quantity' => 2],
                ['product_id' => $this->productJugo->id, 'quantity' => 1],
            ],
        ], $this->cocinaUser);

        // Pedido B: 3 Hamburguesas, 2 Papas, nota "Sin hielo"
        $orderB = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'notes' => 'Sin hielo',
            'items' => [
                ['product_id' => $this->productHamburguesa->id, 'quantity' => 3],
                ['product_id' => $this->productPapas->id, 'quantity' => 2],
            ],
        ], $this->cocinaUser);

        $comp = Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->call('toggleOrderSelection', $orderA->id)
            ->call('toggleOrderSelection', $orderB->id);

        $summary = $comp->get('batchSummary');

        $this->assertEquals(2, $summary['count']);

        $itemsMap = collect($summary['items'])->pluck('quantity', 'name')->toArray();

        $this->assertEquals(5, $itemsMap['Hamburguesa'] ?? 0);
        $this->assertEquals(1, $itemsMap['Jugo'] ?? 0);
        $this->assertEquals(2, $itemsMap['Papas'] ?? 0);

        $notesList = collect($summary['notes'])->pluck('note')->toArray();
        $this->assertContains('Sin cebolla', $notesList);
        $this->assertContains('Sin hielo', $notesList);
    }

    /**
     * 3, 4, 5, 10. Iniciar lote cambia todos los NEW seleccionados a PREPARING,
     * les asigna preparing_at, crea historial y limpia la selección.
     */
    public function test_start_batch_preparing_updates_status_preparing_at_history_and_clears_selection(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productJugo->id, 'quantity' => 2]],
        ], $this->cocinaUser);

        $comp = Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->call('toggleOrderSelection', $order1->id)
            ->call('toggleOrderSelection', $order2->id)
            ->call('startBatchPreparing')
            ->assertDispatched('notify-toast', type: 'info', title: 'Lote en Preparación');

        $this->assertEmpty($comp->get('selectedOrderIds'));

        $fresh1 = $order1->fresh();
        $fresh2 = $order2->fresh();

        $this->assertEquals(OrderStatus::PREPARING, $fresh1->status);
        $this->assertEquals(OrderStatus::PREPARING, $fresh2->status);

        $this->assertNotNull($fresh1->preparing_at);
        $this->assertNotNull($fresh2->preparing_at);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order1->id,
            'from_status' => 'NEW',
            'to_status' => 'PREPARING',
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order2->id,
            'from_status' => 'NEW',
            'to_status' => 'PREPARING',
        ]);
    }

    /**
     * 6. DIRECT no puede entrar al lote.
     */
    public function test_batch_preparing_fails_if_direct_order_included(): void
    {
        $kitchenOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $directOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::DIRECT,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El lote cambió mientras lo seleccionabas');

        $this->orderService->startPreparingBatch([$kitchenOrder->id, $directOrder->id], $this->cocinaUser);
    }

    /**
     * 7. PREPARING no puede entrar al lote.
     */
    public function test_batch_preparing_fails_if_preparing_order_included(): void
    {
        $newOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $preparingOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $this->orderService->startPreparing($preparingOrder, $this->cocinaUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El lote cambió mientras lo seleccionabas');

        $this->orderService->startPreparingBatch([$newOrder->id, $preparingOrder->id], $this->cocinaUser);
    }

    /**
     * 8, 9. Si un pedido cambia antes de ejecutar, el lote completo falla y no hay transición parcial.
     */
    public function test_batch_preparing_fails_atomically_without_partial_transition(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productJugo->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        // Concurrent action: another process prepares order2
        $this->orderService->startPreparing($order2, $this->cocinaUser);

        Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->set('selectedOrderIds', [$order1->id, $order2->id])
            ->call('startBatchPreparing')
            ->assertDispatched('notify-toast', type: 'error', title: 'Lote no válido');

        // Order 1 was NOT transitioned partially
        $this->assertEquals(OrderStatus::NEW, $order1->fresh()->status);
        $this->assertNull($order1->fresh()->preparing_at);
    }

    /**
     * 11. markOrderReady individual sigue funcionando normalmente.
     */
    public function test_individual_mark_order_ready_works_after_batch_preparation(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productHamburguesa->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productJugo->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->cocinaUser);

        Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->call('markOrderReady', $order1->id)
            ->assertDispatched('notify-toast', type: 'success', title: 'Pedido Listo');

        $this->assertEquals(OrderStatus::READY, $order1->fresh()->status);
        $this->assertEquals(OrderStatus::PREPARING, $order2->fresh()->status);
    }
}
