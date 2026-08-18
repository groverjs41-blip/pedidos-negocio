<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\ServiceMode;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $deliveryUser;
    protected User $otherDeliveryUser;
    protected Category $category;
    protected Product $productA;
    protected Product $productB;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $deliveryRole = Role::firstOrCreate(['slug' => 'reparto'], ['name' => 'Reparto']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);

        $this->deliveryUser = User::factory()->create(['name' => 'Repartidor 1', 'active' => true]);
        $this->deliveryUser->roles()->attach($deliveryRole);

        $this->otherDeliveryUser = User::factory()->create(['name' => 'Repartidor 2', 'active' => true]);
        $this->otherDeliveryUser->roles()->attach($deliveryRole);

        $this->category = Category::create(['name' => 'Comida', 'active' => true]);

        $this->productA = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Pizza Familiar',
            'price' => '60.00',
            'active' => true,
        ]);

        $this->productB = Product::create([
            'category_id' => $this->category->id,
            'name' => 'Gaseosa 2L',
            'price' => '15.00',
            'active' => true,
        ]);

        $this->orderService = app(OrderService::class);
    }

    /**
     * 15. Solo aparecen pedidos KITCHEN en el panel de reparto.
     */
    public function test_delivery_dashboard_shows_only_kitchen_service_mode_orders(): void
    {
        $kitchenOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $directOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::DIRECT,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($kitchenOrder, $this->deliveryUser);
        $this->orderService->markReady($kitchenOrder, $this->deliveryUser);

        Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->assertSee($kitchenOrder->number)
            ->assertDontSee($directOrder->number);
    }

    /**
     * 1, 2. Seleccionar múltiples READY y calcular total por cobrar correctamente.
     */
    public function test_selecting_multiple_ready_orders_calculates_correct_batch_summary(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]], // 60.00
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 2]], // 30.00
        ], $this->deliveryUser);

        $this->orderService->startPreparing($order1, $this->deliveryUser);
        $this->orderService->markReady($order1, $this->deliveryUser);

        $this->orderService->startPreparing($order2, $this->deliveryUser);
        $this->orderService->markReady($order2, $this->deliveryUser);

        $comp = Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->call('toggleOrderSelection', $order1->id)
            ->call('toggleOrderSelection', $order2->id);

        $summary = $comp->get('batchSummary');

        $this->assertEquals(2, $summary['count']);
        $this->assertEquals('90.00', $summary['total_amount']);
    }

    /**
     * 3, 4, 5, 6, 12. Batch claim READY -> DELIVERING asigna delivery_user_id, delivering_at, historial y limpia selección.
     */
    public function test_claim_delivery_batch_transitions_orders_to_delivering_atomically(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($order1, $this->deliveryUser);
        $this->orderService->markReady($order1, $this->deliveryUser);

        $this->orderService->startPreparing($order2, $this->deliveryUser);
        $this->orderService->markReady($order2, $this->deliveryUser);

        $comp = Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->call('toggleOrderSelection', $order1->id)
            ->call('toggleOrderSelection', $order2->id)
            ->call('claimDeliveryBatch')
            ->assertDispatched('notify-toast', type: 'info', title: 'Salida Iniciada');

        $this->assertEmpty($comp->get('selectedOrderIds'));

        $fresh1 = $order1->fresh();
        $fresh2 = $order2->fresh();

        $this->assertEquals(OrderStatus::DELIVERING, $fresh1->status);
        $this->assertEquals(OrderStatus::DELIVERING, $fresh2->status);

        $this->assertEquals($this->deliveryUser->id, $fresh1->delivery_user_id);
        $this->assertEquals($this->deliveryUser->id, $fresh2->delivery_user_id);

        $this->assertNotNull($fresh1->delivering_at);
        $this->assertNotNull($fresh2->delivering_at);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order1->id,
            'from_status' => 'READY',
            'to_status' => 'DELIVERING',
            'user_id' => $this->deliveryUser->id,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order2->id,
            'from_status' => 'READY',
            'to_status' => 'DELIVERING',
            'user_id' => $this->deliveryUser->id,
        ]);
    }

    /**
     * 7. DIRECT rechazado en claimForDeliveryBatch.
     */
    public function test_claim_delivery_batch_fails_if_direct_order_included(): void
    {
        $kitchenOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $directOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::DIRECT,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($kitchenOrder, $this->deliveryUser);
        $this->orderService->markReady($kitchenOrder, $this->deliveryUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uno de los pedidos ya fue tomado');

        $this->orderService->claimForDeliveryBatch([$kitchenOrder->id, $directOrder->id], $this->deliveryUser);
    }

    /**
     * 8. PREPARING rechazado en claimForDeliveryBatch.
     */
    public function test_claim_delivery_batch_fails_if_preparing_order_included(): void
    {
        $readyOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $preparingOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($readyOrder, $this->deliveryUser);
        $this->orderService->markReady($readyOrder, $this->deliveryUser);

        $this->orderService->startPreparing($preparingOrder, $this->deliveryUser);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Uno de los pedidos ya fue tomado');

        $this->orderService->claimForDeliveryBatch([$readyOrder->id, $preparingOrder->id], $this->deliveryUser);
    }

    /**
     * 9, 10, 11. Si un pedido fue tomado por otro repartidor (DELIVERING), el lote completo falla sin transición parcial.
     */
    public function test_claim_delivery_batch_fails_atomically_if_one_order_was_claimed_concurrently(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($order1, $this->deliveryUser);
        $this->orderService->markReady($order1, $this->deliveryUser);

        $this->orderService->startPreparing($order2, $this->deliveryUser);
        $this->orderService->markReady($order2, $this->deliveryUser);

        // Concurrent claim of order2 by otherDeliveryUser
        $this->orderService->claimForDelivery($order2, $this->otherDeliveryUser);

        Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->set('selectedOrderIds', [$order1->id, $order2->id])
            ->call('claimDeliveryBatch')
            ->assertDispatched('notify-toast', type: 'error', title: 'Error de asignación');

        // Order 1 remains READY and unclaimed
        $this->assertEquals(OrderStatus::READY, $order1->fresh()->status);
        $this->assertNull($order1->fresh()->delivery_user_id);
    }

    /**
     * 13, 14. Entrega e inventario de envases individual siguen funcionando normalmente.
     */
    public function test_individual_delivery_and_returnable_prompt_continue_to_work(): void
    {
        $returnableType = ReturnableType::create(['name' => 'Garrafa 20L', 'sort_order' => 1, 'active' => true]);
        ProductReturnableRequirement::create([
            'product_id' => $this->productA->id,
            'returnable_type_id' => $returnableType->id,
            'quantity' => 1,
        ]);

        $customer = Customer::create(['name' => 'Cliente Reparto', 'phone' => '71111111', 'active' => true]);

        $order = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($order, $this->deliveryUser);
        $this->orderService->markReady($order, $this->deliveryUser);
        $this->orderService->claimForDelivery($order, $this->deliveryUser);

        $comp = Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->call('markOrderDelivered', $order->id)
            ->assertDispatched('notify-toast', type: 'success', title: 'Entregado');

        $this->assertEquals(OrderStatus::DELIVERED, $order->fresh()->status);
        $this->assertTrue($comp->get('showReturnablePrompt'));
    }

    /**
     * Test claimKitchenBatchForDelivery successfully transitions 4/4 READY orders to DELIVERING.
     */
    public function test_claim_kitchen_batch_success_when_all_orders_ready(): void
    {
        $orders = collect();
        for ($i = 0; $i < 4; $i++) {
            $order = $this->orderService->createOrder([
                'submission_token' => (string) Str::uuid(),
                'service_mode' => ServiceMode::KITCHEN,
                'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
            ], $this->deliveryUser);
            $orders->push($order);
        }

        $orderIds = $orders->pluck('id')->toArray();
        $prep = $this->orderService->startPreparingBatch($orderIds, $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser);

        $claimed = $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->deliveryUser);

        $this->assertCount(4, $claimed);

        $firstDeliveringAt = null;
        foreach ($orders as $order) {
            $fresh = $order->fresh();
            $this->assertEquals(OrderStatus::DELIVERING, $fresh->status);
            $this->assertEquals($this->deliveryUser->id, $fresh->delivery_user_id);
            $this->assertEquals($batchToken, $fresh->kitchen_batch_token);

            if ($firstDeliveringAt === null) {
                $firstDeliveringAt = $fresh->delivering_at;
            } else {
                $this->assertEquals($firstDeliveringAt->toIso8601String(), $fresh->delivering_at->toIso8601String());
            }

            $this->assertDatabaseHas('order_status_histories', [
                'order_id' => $order->id,
                'from_status' => 'READY',
                'to_status' => 'DELIVERING',
                'user_id' => $this->deliveryUser->id,
                'notes' => 'Lote de cocina recogido para reparto.',
            ]);
        }
    }

    /**
     * Test claimKitchenBatchForDelivery fails when 3 orders are READY and 1 is PREPARING.
     */
    public function test_claim_kitchen_batch_fails_when_batch_is_partially_ready(): void
    {
        $orders = collect();
        for ($i = 0; $i < 4; $i++) {
            $order = $this->orderService->createOrder([
                'submission_token' => (string) Str::uuid(),
                'service_mode' => ServiceMode::KITCHEN,
                'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
            ], $this->deliveryUser);
            $orders->push($order);
        }

        $orderIds = $orders->pluck('id')->toArray();
        $prep = $this->orderService->startPreparingBatch($orderIds, $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        // Mark only first 3 orders ready
        $readyIds = [$orders[0]->id, $orders[1]->id, $orders[2]->id];
        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser, $readyIds);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El lote cambió de estado o ya fue tomado por otro repartidor.');

        $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->deliveryUser);
    }

    /**
     * Test claimKitchenBatchForDelivery fails if one order is already DELIVERING.
     */
    public function test_claim_kitchen_batch_fails_if_one_order_already_delivering(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $prep = $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser);

        // Manually mark order1 as DELIVERING by another driver
        $order1->update(['status' => OrderStatus::DELIVERING, 'delivery_user_id' => $this->otherDeliveryUser->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El lote cambió de estado o ya fue tomado por otro repartidor.');

        $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->deliveryUser);
    }

    /**
     * Test concurrent drivers claiming same kitchen batch: only one wins.
     */
    public function test_concurrent_drivers_claiming_kitchen_batch(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $prep = $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser);

        // Driver 1 claims batch
        $claimed1 = $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->deliveryUser);
        $this->assertCount(2, $claimed1);

        // Driver 2 attempts to claim same batch
        try {
            $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->otherDeliveryUser);
            $this->fail('Driver 2 should have been rejected.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('El lote cambió de estado o ya fue tomado por otro repartidor.', $e->getMessage());
        }

        $this->assertEquals($this->deliveryUser->id, $order1->fresh()->delivery_user_id);
        $this->assertEquals($this->deliveryUser->id, $order2->fresh()->delivery_user_id);
    }

    /**
     * Test selectAllReady and toggleOrderSelection exclude orders with a kitchen_batch_token.
     */
    public function test_select_all_ready_excludes_kitchen_batch_orders(): void
    {
        $batchOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $prep = $this->orderService->startPreparingBatch([$batchOrder->id], $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser);

        $standaloneOrder = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $this->orderService->startPreparing($standaloneOrder, $this->deliveryUser);
        $this->orderService->markReady($standaloneOrder, $this->deliveryUser);

        $comp = Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->call('selectAllReady');

        $selected = $comp->get('selectedOrderIds');
        $this->assertContains($standaloneOrder->id, $selected);
        $this->assertNotContains($batchOrder->id, $selected);

        // Attempt toggle selection on batch order directly
        $comp->call('toggleOrderSelection', $batchOrder->id);
        $this->assertNotContains($batchOrder->id, $comp->get('selectedOrderIds'));
    }

    /**
     * Test that individual markOrderDelivered continues delivering one by one after batch pickup.
     */
    public function test_individual_mark_delivered_works_one_by_one_after_kitchen_batch_pickup(): void
    {
        $order1 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productA->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $order2 = $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'service_mode' => ServiceMode::KITCHEN,
            'items' => [['product_id' => $this->productB->id, 'quantity' => 1]],
        ], $this->deliveryUser);

        $prep = $this->orderService->startPreparingBatch([$order1->id, $order2->id], $this->deliveryUser);
        $batchToken = $prep->first()->kitchen_batch_token;

        $this->orderService->markReadyBatch($batchToken, $this->deliveryUser);

        $this->orderService->claimKitchenBatchForDelivery($batchToken, $this->deliveryUser);

        // Mark order 1 delivered
        $comp = Livewire::actingAs($this->deliveryUser)
            ->test(\App\Livewire\Delivery::class)
            ->call('markOrderDelivered', $order1->id);

        $this->assertEquals(OrderStatus::DELIVERED, $order1->fresh()->status);
        $this->assertEquals(OrderStatus::DELIVERING, $order2->fresh()->status);

        // Mark order 2 delivered
        $comp->call('markOrderDelivered', $order2->id);
        $this->assertEquals(OrderStatus::DELIVERED, $order2->fresh()->status);
    }
}
