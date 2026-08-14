<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReturnableRequirement;
use App\Models\ReturnableType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $pedidosUser;
    protected User $cocinaUser;
    protected User $cajaUser;

    protected Role $adminRole;
    protected Role $pedidosRole;
    protected Role $cocinaRole;
    protected Role $cajaRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $this->adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->pedidosRole = Role::firstOrCreate(['slug' => 'pedidos'], ['name' => 'Pedidos']);
        $this->cocinaRole = Role::firstOrCreate(['slug' => 'cocina'], ['name' => 'Cocina']);
        $this->cajaRole = Role::firstOrCreate(['slug' => 'caja'], ['name' => 'Caja']);

        $this->adminUser = User::factory()->create(['email' => 'admin@test.com', 'active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);

        $this->pedidosUser = User::factory()->create(['active' => true]);
        $this->pedidosUser->roles()->attach($this->pedidosRole);

        $this->cocinaUser = User::factory()->create(['active' => true]);
        $this->cocinaUser->roles()->attach($this->cocinaRole);

        $this->cajaUser = User::factory()->create(['active' => true]);
        $this->cajaUser->roles()->attach($this->cajaRole);
    }

    public function test_admin_login_redirects_to_inicio()
    {
        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/inicio');
    }

    public function test_admin_can_access_all_gestion_routes()
    {
        $this->actingAs($this->adminUser);

        $this->get('/gestion')->assertStatus(200);
        $this->get('/gestion/productos')->assertStatus(200);
        $this->get('/gestion/productos/nuevo')->assertStatus(200);
        $this->get('/gestion/categorias')->assertStatus(200);
        $this->get('/gestion/clientes')->assertStatus(200);
        $this->get('/gestion/clientes/nuevo')->assertStatus(200);
        $this->get('/gestion/usuarios')->assertStatus(200);
        $this->get('/gestion/usuarios/nuevo')->assertStatus(200);
        $this->get('/gestion/envases')->assertStatus(200);
        $this->get('/menu')->assertStatus(200);
    }

    public function test_non_admin_cannot_access_gestion_routes()
    {
        $this->actingAs($this->cocinaUser);
        $this->get('/gestion/productos')->assertStatus(403);

        $this->actingAs($this->pedidosUser);
        $this->get('/gestion/usuarios')->assertStatus(403);

        $this->actingAs($this->cajaUser);
        $this->get('/gestion/envases')->assertStatus(403);
    }

    public function test_create_product_with_returnable_requirements()
    {
        $this->actingAs($this->adminUser);

        $cat = Category::create(['name' => 'Bebidas', 'active' => true]);
        $taza = ReturnableType::create(['name' => 'Taza Especial', 'sort_order' => 1, 'active' => true]);

        Livewire::test(\App\Livewire\CreateProduct::class)
            ->set('name', 'Té Helado Especial')
            ->set('categoryId', (string)$cat->id)
            ->set('price', '15.00')
            ->set('estimatedCost', '3.50')
            ->set('notes', 'Té artesanal')
            ->set('requirements', [
                ['returnable_type_id' => $taza->id, 'quantity' => 1]
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/gestion/productos');

        $this->assertDatabaseHas('products', [
            'name' => 'Té Helado Especial',
            'price' => '15.00',
        ]);

        $product = Product::where('name', 'Té Helado Especial')->first();
        $this->assertNotNull($product);

        $this->assertDatabaseHas('product_returnable_requirements', [
            'product_id' => $product->id,
            'returnable_type_id' => $taza->id,
            'quantity' => 1,
        ]);
    }

    public function test_edit_product_updates_returnable_requirements()
    {
        $this->actingAs($this->adminUser);

        $cat = Category::create(['name' => 'Calientes', 'active' => true]);
        $taza = ReturnableType::create(['name' => 'Taza Grande', 'sort_order' => 1, 'active' => true]);

        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Café Latte',
            'price' => '20.00',
            'estimated_cost' => '4.00',
            'active' => true,
        ]);

        Livewire::test(\App\Livewire\EditProduct::class, ['product' => $product])
            ->set('price', '25.00')
            ->set('requirements', [
                ['returnable_type_id' => $taza->id, 'quantity' => 2]
            ])
            ->call('save')
            ->assertRedirect('/gestion/productos');

        $this->assertEquals('25.00', $product->fresh()->price);
        $this->assertDatabaseHas('product_returnable_requirements', [
            'product_id' => $product->id,
            'returnable_type_id' => $taza->id,
            'quantity' => 2,
        ]);
    }

    public function test_create_and_toggle_category()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\ManageCategories::class)
            ->set('name', 'Postres Especiales')
            ->set('sortOrder', 5)
            ->call('save');

        $this->assertDatabaseHas('categories', ['name' => 'Postres Especiales']);

        $category = Category::where('name', 'Postres Especiales')->first();
        Livewire::test(\App\Livewire\ManageCategories::class)
            ->call('toggleActive', $category->id);

        $this->assertFalse((bool)$category->fresh()->active);
    }

    public function test_create_and_view_customer_detail()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\CreateCustomer::class)
            ->set('name', 'Cliente VIP')
            ->set('phone', '8095551234')
            ->set('address', 'Av. Central #45')
            ->call('save')
            ->assertRedirect('/gestion/clientes');

        $customer = Customer::where('name', 'Cliente VIP')->first();
        $this->assertNotNull($customer);

        $this->get('/gestion/clientes/' . $customer->id)->assertStatus(200);

        // Preselection test in POS
        Livewire::withQueryParams(['customer' => $customer->id])
            ->test(\App\Livewire\CreateOrder::class)
            ->assertSet('selectedCustomerId', $customer->id);
    }

    public function test_create_user_with_roles_and_safety()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(\App\Livewire\CreateUser::class)
            ->set('name', 'Nuevo Cocinero')
            ->set('email', 'cocinero@test.com')
            ->set('password', 'password123')
            ->set('selectedRoles', [$this->cocinaRole->id])
            ->call('save')
            ->assertRedirect('/gestion/usuarios');

        $newUser = User::where('email', 'cocinero@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('cocina'));

        // Test safety rule: cannot strip last admin
        Livewire::test(\App\Livewire\EditUser::class, ['user' => $this->adminUser])
            ->set('selectedRoles', [$this->cocinaRole->id])
            ->call('save')
            ->assertSet('errorMessage', 'No se puede remover el rol Administrador al único Administrador activo del sistema.');
    }

    public function test_admin_sidebar_navigation_contains_all_links()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/inicio');
        $response->assertStatus(200);
        $response->assertSee('/inicio');
        $response->assertSee('/pedidos/nuevo');
        $response->assertSee('/pedidos');
        $response->assertSee('/cocina');
        $response->assertSee('/reparto');
        $response->assertSee('/gestion/productos');
        $response->assertSee('/gestion/categorias');
        $response->assertSee('/gestion/clientes');
        $response->assertSee('/gestion/usuarios');
        $response->assertSee('/gestion/envases');
        $response->assertSee('/caja');
        $response->assertSee('/tazas');
        $response->assertSee('/cierre');
        $response->assertDontSee('/admin');
    }
}
