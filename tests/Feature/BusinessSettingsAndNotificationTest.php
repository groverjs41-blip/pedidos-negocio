<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\OrderService;
use App\Support\MoneyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Events\OrderChanged;
use Tests\TestCase;

class BusinessSettingsAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        Role::firstOrCreate(['slug' => 'cocina'], ['name' => 'Cocina']);
        Role::firstOrCreate(['slug' => 'reparto'], ['name' => 'Reparto']);
        Role::firstOrCreate(['slug' => 'pedidos'], ['name' => 'Pedidos']);
        Role::firstOrCreate(['slug' => 'caja'], ['name' => 'Caja']);
    }

    public function test_money_formatter_default_formatting(): void
    {
        $this->assertEquals('Bs 12,00', MoneyFormatter::format('12.00'));
        $this->assertEquals('Bs 1.250,50', MoneyFormatter::format('1250.50'));
        $this->assertEquals('Bs 0,50', MoneyFormatter::format('0.50'));
    }

    public function test_money_formatter_with_custom_settings(): void
    {
        /** @var BusinessSettingsService $service */
        $service = app(BusinessSettingsService::class);
        $service->updateSettings([
            'business_name' => 'Mi Negocio Test',
            'currency_name' => 'Dólares',
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'currency_symbol_position' => 'AFTER',
            'currency_decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);

        $this->assertEquals('1,250.50 $', MoneyFormatter::format('1250.50'));
    }

    public function test_admin_can_access_settings_and_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/gestion/configuracion');
        $response->assertStatus(200);
        $response->assertSee('CONFIGURACIÓN DEL NEGOCIO');
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('cocina');

        $response = $this->actingAs($user)->get('/gestion/configuracion');
        $response->assertStatus(403);
    }

    public function test_operational_notifications_dispatched_on_order_creation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $kitchenUser = User::factory()->create();
        $kitchenUser->assignRole('cocina');

        $deliveryUser = User::factory()->create();
        $deliveryUser->assignRole('reparto');

        $category = \App\Models\Category::create(['name' => 'Bebidas', 'active' => true]);
        $product = \App\Models\Product::create([
            'category_id' => $category->id,
            'name' => 'Café',
            'price' => '10.00',
            'active' => true,
        ]);

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ], $admin);

        // Check kitchen user and admin received notification
        $this->assertCount(1, $kitchenUser->notifications);
        $this->assertEquals('ORDER_CREATED', $kitchenUser->notifications->first()->data['action']);

        // Check delivery user did NOT receive creation notification
        $this->assertCount(0, $deliveryUser->notifications);

        // Advance to READY
        $orderService->startPreparing($order, $kitchenUser);
        $orderService->markReady($order, $kitchenUser);

        // Check delivery user received READY notification
        $deliveryUser->refresh();
        $this->assertCount(1, $deliveryUser->notifications);
        $this->assertEquals('READY', $deliveryUser->notifications->first()->data['action']);
    }
}
