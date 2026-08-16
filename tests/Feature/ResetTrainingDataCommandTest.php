<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyClosure;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\BusinessSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetTrainingDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'cocina'], ['name' => 'Cocina']);
    }

    public function test_reset_command_clears_operational_data_and_preserves_master_data(): void
    {
        // 1. Setup Master Data (Must remain)
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Comidas', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Hamburguesa',
            'price' => '25.00',
            'active' => true,
        ]);

        /** @var BusinessSettingsService $settingsService */
        $settingsService = app(BusinessSettingsService::class);
        $settingsService->updateSettings([
            'business_name' => 'Mi Negocio Test',
        ]);

        $customer = Customer::create([
            'name' => 'Cliente Prueba',
            'phone' => '70000000',
        ]);

        // 2. Setup Operational Data (Must be deleted)
        $order = Order::create([
            'number' => 'PED-0001',
            'daily_number' => 1,
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'status' => \App\Enums\OrderStatus::DELIVERED,
            'subtotal' => '25.00',
            'total' => '25.00',
            'ordered_at' => now(),
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => '25.00',
            'line_total' => '25.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_status_histories')->insert([
            'order_id' => $order->id,
            'from_status' => \App\Enums\OrderStatus::NEW->value,
            'to_status' => \App\Enums\OrderStatus::DELIVERED->value,
            'user_id' => $admin->id,
            'created_at' => now(),
        ]);

        $payment = Payment::create([
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,
            'amount' => '25.00',
            'method' => \App\Enums\PaymentMethod::CASH,
            'paid_at' => now(),
            'created_by' => $admin->id,
        ]);

        DB::table('payment_allocations')->insert([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => '25.00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('collection_visits')->insert([
            'submission_token' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,
            'payment_id' => $payment->id,
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DailyClosure::create([
            'business_date' => now()->format('Y-m-d'),
            'closed_at' => now(),
            'closed_by' => $admin->id,
            'snapshot' => ['total' => 25],
        ]);

        DB::table('order_daily_counters')->insert([
            'date' => now()->format('Y-m-d'),
            'last_number' => 1,
        ]);

        DB::table('notifications')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\OperationalNotification::class,
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $admin->id,
            'data' => json_encode(['title' => 'Test notification']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Run Command without --with-customers
        $this->artisan('training:reset', ['--force' => true])
            ->assertExitCode(0)
            ->expectsOutputToContain('Datos de capacitación reiniciados con éxito.');

        // 4. Assert Operational Data is cleared
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_status_histories', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('payment_allocations', 0);
        $this->assertDatabaseCount('collection_visits', 0);
        $this->assertDatabaseCount('daily_closures', 0);
        $this->assertDatabaseCount('order_daily_counters', 0);
        $this->assertDatabaseCount('notifications', 0);

        // 5. Assert Master Data & Customer remain
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', 5);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('business_settings', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_reset_command_deletes_customers_when_with_customers_option_is_passed(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Borrable',
            'phone' => '71111111',
        ]);

        $this->artisan('training:reset', ['--with-customers' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_reset_command_requires_force_in_production_environment(): void
    {
        $this->app['config']->set('app.env', 'production');

        $this->artisan('training:reset')
            ->assertExitCode(1)
            ->expectsOutputToContain('El comando debe ejecutarse con --force en entorno de producción.');
    }
}
