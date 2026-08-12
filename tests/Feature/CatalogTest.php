<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test a customer can be created.
     */
    public function test_can_create_customer(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'phone' => '123456789',
            'address' => '123 Main St',
            'location_notes' => 'Near the park',
            'notes' => 'Likes morning deliveries',
            'active' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'John Doe',
            'phone' => '123456789',
            'active' => true,
        ]);
    }

    /**
     * Test a customer can be disabled (active set to false).
     */
    public function test_customer_can_be_disabled(): void
    {
        $customer = Customer::factory()->create(['active' => true]);

        $customer->update(['active' => false]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'active' => false,
        ]);
    }

    /**
     * Test a category can have multiple products.
     */
    public function test_category_has_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $this->assertCount(3, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
    }

    /**
     * Test a product belongs to a category.
     */
    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    /**
     * Test that product price precision (2 decimals) is preserved.
     */
    public function test_product_price_precision_is_preserved(): void
    {
        $product = Product::factory()->create([
            'price' => 19.99,
        ]);

        $freshProduct = $product->fresh();

        $this->assertEquals('19.99', $freshProduct->price);
    }

    /**
     * Test that estimated cost can be null.
     */
    public function test_estimated_cost_can_be_null(): void
    {
        $product = Product::factory()->create([
            'estimated_cost' => null,
        ]);

        $freshProduct = $product->fresh();

        $this->assertNull($freshProduct->estimated_cost);
    }

    /**
     * Test that a product can be disabled.
     */
    public function test_product_can_be_disabled(): void
    {
        $product = Product::factory()->create(['active' => true]);

        $product->update(['active' => false]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'active' => false,
        ]);
    }

    /**
     * Test category name uniqueness.
     */
    public function test_unique_category_name(): void
    {
        Category::factory()->create(['name' => 'Bebidas']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Category::factory()->create(['name' => 'Bebidas']);
    }

    /**
     * Test price and cost validation rules in the Product resource form.
     */
    public function test_product_form_price_and_cost_validation(): void
    {
        $adminRole = Role::where('slug', 'admin')->first() ?? Role::create(['slug' => 'admin', 'name' => 'Administrador']);
        $admin = User::factory()->create(['active' => true]);
        $admin->roles()->attach($adminRole);
        $this->actingAs($admin);

        $category = Category::factory()->create();


        // 1. Negative price validation
        $res = \Livewire\Livewire::test(\App\Filament\Resources\ProductResource\Pages\CreateProduct::class);
        $res->set('data.category_id', $category->id);
        $res->set('data.name', 'Ice Cream');
        $res->set('data.price', -5.00);
        $res->set('data.active', true);
        $res->call('create');
        $res->assertHasErrors(['data.price']);

        // 2. Negative estimated cost validation
        $res = \Livewire\Livewire::test(\App\Filament\Resources\ProductResource\Pages\CreateProduct::class);
        $res->set('data.category_id', $category->id);
        $res->set('data.name', 'Ice Cream');
        $res->set('data.price', 5.00);
        $res->set('data.estimated_cost', -1.00);
        $res->set('data.active', true);
        $res->call('create');
        $res->assertHasErrors(['data.estimated_cost']);
    }
}
