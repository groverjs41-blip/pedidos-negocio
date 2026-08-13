<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PaymentChanged;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $cajaUser;
    protected User $cocinaUser;
    protected Customer $activeCustomer;
    protected Product $testProduct;
    protected PaymentService $paymentService;
    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('slug', 'admin')->first() ?? Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $cajaRole = Role::where('slug', 'caja')->first() ?? Role::create(['name' => 'Caja', 'slug' => 'caja']);
        $cocinaRole = Role::where('slug', 'cocina')->first() ?? Role::create(['name' => 'Cocina', 'slug' => 'cocina']);

        $this->adminUser = User::factory()->create(['active' => true]);
        $this->adminUser->roles()->attach($adminRole);

        $this->cajaUser = User::factory()->create(['active' => true]);
        $this->cajaUser->roles()->attach($cajaRole);

        $this->cocinaUser = User::factory()->create(['active' => true]);
        $this->cocinaUser->roles()->attach($cocinaRole);

        $this->activeCustomer = Customer::create([
            'name' => 'Cliente Cobranza Test',
            'phone' => '5551234',
            'address' => 'Av. Principal 123',
            'active' => true,
        ]);

        $category = Category::create(['name' => 'Comida', 'active' => true]);
        $this->testProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Burger Combo',
            'price' => '100.10',
            'estimated_cost' => '40.00',
            'active' => true,
        ]);

        $this->paymentService = app(PaymentService::class);
        $this->orderService = app(OrderService::class);
    }

    protected function createOrder(Customer $customer = null, string $price = '100.00'): Order
    {
        $product = Product::create([
            'category_id' => $this->testProduct->category_id,
            'name' => 'Prod ' . Str::random(5),
            'price' => $price,
            'estimated_cost' => '10.00',
            'active' => true,
        ]);

        return $this->orderService->createOrder([
            'submission_token' => (string) Str::uuid(),
            'customer_id' => $customer?->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ], $this->adminUser);
    }

    public function test_payment_status_calculation()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');

        $this->assertEquals('0.00', $order->paidAmount());
        $this->assertEquals('100.00', $order->outstandingBalance());
        $this->assertEquals(PaymentStatus::PENDING, $order->paymentStatus());

        // Partial payment 40.00
        $this->paymentService->recordOrderPayment(
            $order,
            '40.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('40.00', $order->paidAmount());
        $this->assertEquals('60.00', $order->outstandingBalance());
        $this->assertEquals(PaymentStatus::PARTIAL, $order->paymentStatus());

        // Full remaining payment 60.00
        $this->paymentService->recordOrderPayment(
            $order,
            '60.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('100.00', $order->paidAmount());
        $this->assertEquals('0.00', $order->outstandingBalance());
        $this->assertEquals(PaymentStatus::PAID, $order->paymentStatus());
    }

    public function test_customer_debt_calculation()
    {
        // Order A DELIVERED 100
        $orderA = $this->createOrder($this->activeCustomer, '100.00');
        $this->orderService->startPreparing($orderA, $this->adminUser);
        $this->orderService->markReady($orderA, $this->adminUser);
        $this->orderService->claimForDelivery($orderA, $this->adminUser);
        $this->orderService->markDelivered($orderA, $this->adminUser);

        // Order B READY 50 (NOT delivered, so NOT customer debt yet)
        $orderB = $this->createOrder($this->activeCustomer, '50.00');
        $this->orderService->startPreparing($orderB, $this->adminUser);
        $this->orderService->markReady($orderB, $this->adminUser);

        $this->assertEquals('100.00', $this->activeCustomer->outstandingBalance());
    }

    public function test_oldest_first_allocation()
    {
        // Order A (older) = 100
        $orderA = $this->createOrder($this->activeCustomer, '100.00');
        $orderA->update(['ordered_at' => now()->subHours(3)]);
        $this->orderService->startPreparing($orderA, $this->adminUser);
        $this->orderService->markReady($orderA, $this->adminUser);
        $this->orderService->claimForDelivery($orderA, $this->adminUser);
        $this->orderService->markDelivered($orderA, $this->adminUser);

        // Order B (newer) = 50
        $orderB = $this->createOrder($this->activeCustomer, '50.00');
        $orderB->update(['ordered_at' => now()->subHours(1)]);
        $this->orderService->startPreparing($orderB, $this->adminUser);
        $this->orderService->markReady($orderB, $this->adminUser);
        $this->orderService->claimForDelivery($orderB, $this->adminUser);
        $this->orderService->markDelivered($orderB, $this->adminUser);

        // Total debt = 150. Pay 120 lump sum
        $payment = $this->paymentService->recordCustomerPayment(
            $this->activeCustomer,
            '120.00',
            PaymentMethod::TRANSFER,
            'REF123',
            'Abono',
            $this->cajaUser,
            (string) Str::uuid()
        );

        $orderA->refresh();
        $orderB->refresh();

        // Order A should be fully paid (100)
        $this->assertEquals('100.00', $orderA->paidAmount());
        $this->assertEquals('0.00', $orderA->outstandingBalance());
        $this->assertEquals(PaymentStatus::PAID, $orderA->paymentStatus());

        // Order B should be partially paid (20) with balance 30
        $this->assertEquals('20.00', $orderB->paidAmount());
        $this->assertEquals('30.00', $orderB->outstandingBalance());
        $this->assertEquals(PaymentStatus::PARTIAL, $orderB->paymentStatus());

        // Customer debt remaining = 30
        $this->assertEquals('30.00', $this->activeCustomer->outstandingBalance());
    }

    public function test_specific_order_payment_advance()
    {
        // Order in PREPARING status
        $order = $this->createOrder($this->activeCustomer, '50.00');
        $this->orderService->startPreparing($order, $this->adminUser);

        $this->paymentService->recordOrderPayment(
            $order,
            '20.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('20.00', $order->paidAmount());
        $this->assertEquals('30.00', $order->outstandingBalance());
    }

    public function test_overpayment_rejection()
    {
        $order = $this->createOrder($this->activeCustomer, '50.00');

        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->recordOrderPayment(
            $order,
            '50.01',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );
    }

    public function test_submission_token_idempotency()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');
        $token = (string) Str::uuid();

        $p1 = $this->paymentService->recordOrderPayment(
            $order,
            '40.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            $token
        );

        // Same token again
        $p2 = $this->paymentService->recordOrderPayment(
            $order,
            '40.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            $token
        );

        $this->assertEquals($p1->id, $p2->id);
        $this->assertEquals(1, Payment::count());
        $this->assertEquals('40.00', $order->fresh()->paidAmount());
    }

    public function test_payment_voiding()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');

        $payment = $this->paymentService->recordOrderPayment(
            $order,
            '100.00',
            PaymentMethod::CARD,
            'CARD123',
            'Nota',
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->assertEquals(PaymentStatus::PAID, $order->fresh()->paymentStatus());

        // Void payment
        $this->paymentService->voidPayment($payment, 'Error en cobro', $this->adminUser);

        $order->refresh();
        $this->assertEquals('0.00', $order->paidAmount());
        $this->assertEquals('100.00', $order->outstandingBalance());
        $this->assertEquals(PaymentStatus::PENDING, $order->paymentStatus());
        $this->assertTrue($payment->fresh()->isVoided());
    }

    public function test_voided_payment_exclusion()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');

        $payment = $this->paymentService->recordOrderPayment(
            $order,
            '50.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->paymentService->voidPayment($payment, 'Anulado test', $this->adminUser);

        // Voided payments are not counted in non-voided today's collections
        $todayCollected = Payment::whereNull('voided_at')->whereDate('paid_at', today())->sum('amount');
        $this->assertEquals(0, $todayCollected);
    }

    public function test_counter_sale_payment()
    {
        // Counter sale (customer_id = null)
        $order = $this->createOrder(null, '25.00');
        $this->orderService->startPreparing($order, $this->adminUser);
        $this->orderService->markReady($order, $this->adminUser);
        $this->orderService->claimForDelivery($order, $this->adminUser);
        $this->orderService->markDelivered($order, $this->adminUser);

        $payment = $this->paymentService->recordOrderPayment(
            $order,
            '25.00',
            PaymentMethod::CASH,
            null,
            'Venta mostrador',
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->assertNull($payment->customer_id);
        $this->assertEquals(PaymentStatus::PAID, $order->fresh()->paymentStatus());
    }

    public function test_cancelled_order_payment_rejection()
    {
        $order = $this->createOrder($this->activeCustomer, '50.00');
        $this->orderService->cancelOrder($order, $this->adminUser, 'Cliente canceló');

        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->recordOrderPayment(
            $order,
            '50.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );
    }

    public function test_order_cancellation_blocked_with_payments()
    {
        $order = $this->createOrder($this->activeCustomer, '50.00');

        $payment = $this->paymentService->recordOrderPayment(
            $order,
            '20.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Este pedido tiene pagos registrados');
        $this->orderService->cancelOrder($order, $this->adminUser);
    }

    public function test_bcmath_precision()
    {
        // 100.10 - 20.05 = 80.05
        $order = $this->createOrder($this->activeCustomer, '100.10');

        $this->paymentService->recordOrderPayment(
            $order,
            '20.05',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('20.05', $order->paidAmount());
        $this->assertEquals('80.05', $order->outstandingBalance());
    }

    public function test_role_authorization()
    {
        // Role caja allowed
        $this->actingAs($this->cajaUser)
            ->get('/caja')
            ->assertStatus(200);

        // Role admin allowed
        $this->actingAs($this->adminUser)
            ->get('/caja')
            ->assertStatus(200);

        // Role cocina forbidden
        $this->actingAs($this->cocinaUser)
            ->get('/caja')
            ->assertStatus(403);
    }

    public function test_inactive_customer_payment()
    {
        $inactiveCust = Customer::create([
            'name' => 'Cliente Inactivo Deudor',
            'phone' => '99988877',
            'active' => true,
        ]);

        $order = $this->createOrder($inactiveCust, '75.00');
        $this->orderService->startPreparing($order, $this->adminUser);
        $this->orderService->markReady($order, $this->adminUser);
        $this->orderService->claimForDelivery($order, $this->adminUser);
        $this->orderService->markDelivered($order, $this->adminUser);

        // Deactivate customer after order delivery
        $inactiveCust->update(['active' => false]);

        $this->assertEquals('75.00', $inactiveCust->outstandingBalance());

        $payment = $this->paymentService->payCustomerBalance(
            $inactiveCust,
            PaymentMethod::CASH,
            null,
            'Pago cliente inactivo',
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->assertEquals('0.00', $inactiveCust->fresh()->outstandingBalance());
    }

    public function test_payment_changed_event_dispatched()
    {
        Event::fake([PaymentChanged::class]);

        $order = $this->createOrder($this->activeCustomer, '50.00');

        $this->paymentService->recordOrderPayment(
            $order,
            '50.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        Event::assertDispatched(PaymentChanged::class);
    }

    public function test_amount_with_three_decimals_is_rejected()
    {
        $order = $this->createOrder($this->activeCustomer, '50.00');

        $this->expectException(\InvalidArgumentException::class);
        $this->paymentService->recordOrderPayment(
            $order,
            '10.999',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );
    }

    public function test_exact_decimal_preservation_and_bcmath_addition()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');

        // Pay 0.10
        $this->paymentService->recordOrderPayment(
            $order,
            '0.10',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('0.10', $order->paidAmount());

        // Pay 0.20
        $this->paymentService->recordOrderPayment(
            $order,
            '0.20',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $order->refresh();
        $this->assertEquals('0.30', $order->paidAmount());
        $this->assertEquals('99.70', $order->outstandingBalance());
    }

    public function test_already_voided_payment_cannot_be_revoided()
    {
        $order = $this->createOrder($this->activeCustomer, '100.00');

        $payment = $this->paymentService->recordOrderPayment(
            $order,
            '100.00',
            PaymentMethod::CASH,
            null,
            null,
            $this->cajaUser,
            (string) Str::uuid()
        );

        $this->paymentService->voidPayment($payment, 'Primera anulación', $this->adminUser);

        $firstVoidedBy = $payment->fresh()->voided_by;
        $firstVoidReason = $payment->fresh()->void_reason;

        // Second void attempt must throw exception
        try {
            $this->paymentService->voidPayment($payment->fresh(), 'Segunda anulación', $this->cajaUser);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('ya se encuentra anulado', $e->getMessage());
        }

        // Verify second void did not overwrite original audit values
        $fresh = $payment->fresh();
        $this->assertEquals($firstVoidedBy, $fresh->voided_by);
        $this->assertEquals($firstVoidReason, $fresh->void_reason);
    }
}
