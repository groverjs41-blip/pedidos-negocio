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
     * 1, 2. startPreparingBatch asigna el mismo kitchen_batch_token a todos los pedidos del lote.
     * Pedidos preparados individualmente quedan con kitchen_batch_token NULL.
     */
    public function test_start_preparing_batch_assigns_batch_token_while_individual_remains_null(): void
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

        $orderIndividual = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productPapas->id, 'quantity' => 1]],
        ], $this->cocinaUser);

        $preparedBatch = $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->cocinaUser);
        $this->orderService->startPreparing($orderIndividual, $this->cocinaUser);

        $fresh1 = $order1->fresh();
        $fresh2 = $order2->fresh();
        $freshIndiv = $orderIndividual->fresh();

        $this->assertNotNull($fresh1->kitchen_batch_token);
        $this->assertEquals($fresh1->kitchen_batch_token, $fresh2->kitchen_batch_token);
        $this->assertNull($freshIndiv->kitchen_batch_token);
    }

    /**
     * 3, 4. Reload de Kitchen reconoce los lotes activos y agrupa productos.
     */
    public function test_kitchen_reload_identifies_active_preparing_batch_and_aggregates_products(): void
    {
        $orderA = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'notes' => 'Sin mayo',
            'items' => [
                ['product_id' => $this->productHamburguesa->id, 'quantity' => 2],
                ['product_id' => $this->productJugo->id, 'quantity' => 1],
            ],
        ], $this->cocinaUser);

        $orderB = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'notes' => 'Sin sal',
            'items' => [
                ['product_id' => $this->productHamburguesa->id, 'quantity' => 3],
                ['product_id' => $this->productPapas->id, 'quantity' => 2],
            ],
        ], $this->cocinaUser);

        $this->orderService->startPreparingBatch([$orderA->id, $orderB->id], $this->cocinaUser);

        $comp = Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class);

        $activeBatches = $comp->get('activeBatches');
        $this->assertCount(1, $activeBatches);

        $batch = $activeBatches[0];
        $this->assertEquals(2, $batch['preparing_count']);
        $this->assertFalse($batch['is_partial']);

        $itemsMap = collect($batch['items'])->pluck('quantity', 'name')->toArray();
        $this->assertEquals(5, $itemsMap['Hamburguesa'] ?? 0);
        $this->assertEquals(1, $itemsMap['Jugo'] ?? 0);
        $this->assertEquals(2, $itemsMap['Papas'] ?? 0);
    }

    /**
     * 5, 6, 7, 8, 16. markReadyBatch mueve todos los pedidos PREPARING -> READY,
     * asigna ready_at, genera historial y el lote desaparece de lotes activos.
     */
    public function test_mark_ready_batch_completes_all_orders_and_removes_active_batch(): void
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
        $batchToken = $order1->fresh()->kitchen_batch_token;

        $comp = Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class)
            ->call('markBatchReady', $batchToken)
            ->assertDispatched('notify-toast', type: 'success', title: 'Lote Listo');

        $fresh1 = $order1->fresh();
        $fresh2 = $order2->fresh();

        $this->assertEquals(OrderStatus::READY, $fresh1->status);
        $this->assertEquals(OrderStatus::READY, $fresh2->status);

        $this->assertNotNull($fresh1->ready_at);
        $this->assertNotNull($fresh2->ready_at);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order1->id,
            'from_status' => 'PREPARING',
            'to_status' => 'READY',
            'notes' => 'Pedido marcado listo como parte de lote de cocina.',
        ]);

        // Token is preserved for history
        $this->assertEquals($batchToken, $fresh1->kitchen_batch_token);

        // Batch no longer active in kitchen
        $this->assertEmpty($comp->get('activeBatches'));
    }

    /**
     * 14, 15. Lote parcial: Si un pedido fue marcado listo individualmente,
     * el lote se muestra como parcial y el botón marcar restantes completa sólo los PREPARING restantes.
     */
    public function test_partial_batch_shows_remaining_and_allows_marking_remaining_ready(): void
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

        $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->cocinaUser);
        $batchToken = $order1->fresh()->kitchen_batch_token;

        // Mark order1 READY individually beforehand
        $this->orderService->markReady($order1, $this->cocinaUser);

        $comp = Livewire::actingAs($this->cocinaUser)
            ->test(\App\Livewire\Kitchen::class);

        $activeBatches = $comp->get('activeBatches');
        $this->assertCount(1, $activeBatches);

        $batch = $activeBatches[0];
        $this->assertTrue($batch['is_partial']);
        $this->assertEquals(1, $batch['preparing_count']);
        $this->assertEquals(1, $batch['ready_count']);

        // Marking whole batch without remaining flag fails because full batch status changed
        $comp->call('markBatchReady', $batchToken, false)
            ->assertDispatched('notify-toast', type: 'error', title: 'Error en Lote');

        // Marking remaining ready succeeds
        $comp->call('markBatchReady', $batchToken, true)
            ->assertDispatched('notify-toast', type: 'success', title: 'Lote Listo');

        $this->assertEquals(OrderStatus::READY, $order2->fresh()->status);
        $this->assertEmpty($comp->get('activeBatches'));
    }

    /**
     * 9, 10. DIRECT orders and orders from another batch cannot be mixed into batch operations.
     */
    public function test_direct_orders_cannot_participate_in_batch_operations(): void
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
}
