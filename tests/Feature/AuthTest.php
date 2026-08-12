<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }

    /**
     * 1. GET / siendo invitado -> redirect /login
     */
    public function test_guest_accessing_root_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * 2. GET / siendo admin -> redirect /admin
     */
    public function test_admin_accessing_root_redirects_to_admin_panel(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    /**
     * 3. GET / siendo usuario normal -> redirect /inicio
     */
    public function test_normal_user_accessing_root_redirects_to_inicio(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->roles()->attach(Role::where('slug', 'pedidos')->first());

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertRedirect('/inicio');
    }

    /**
     * 4. admin autenticado entrando a /login -> redirect /admin
     */
    public function test_authenticated_admin_accessing_login_redirects_to_admin(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/admin');
    }

    /**
     * 5. usuario normal autenticado entrando a /login -> redirect /inicio
     */
    public function test_authenticated_normal_user_accessing_login_redirects_to_inicio(): void
    {
        $user = User::factory()->create(['active' => true]);
        $user->roles()->attach(Role::where('slug', 'pedidos')->first());

        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/inicio');
    }

    /**
     * 6. login admin correcto -> redirect /admin
     */
    public function test_admin_login_redirects_to_admin(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 7. login usuario normal correcto -> redirect /inicio
     */
    public function test_normal_user_login_redirects_to_inicio(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach(Role::where('slug', 'pedidos')->first());

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login fails with incorrect password.
     */
    public function test_login_fails_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test inactive user cannot log in.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'active' => false,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach(Role::where('slug', 'pedidos')->first());

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test user without admin role cannot access Filament admin panel.
     */
    public function test_user_without_admin_role_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach(Role::where('slug', 'pedidos')->first());

        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    /**
     * Test admin user can access Filament admin panel.
     */
    public function test_admin_user_can_access_admin_panel(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach(Role::where('slug', 'admin')->first());

        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertSuccessful();
    }

    /**
     * Test user can have multiple roles.
     */
    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::factory()->create();

        $adminRole = Role::where('slug', 'admin')->first();
        $pedidosRole = Role::where('slug', 'pedidos')->first();

        $user->roles()->attach([$adminRole->id, $pedidosRole->id]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('pedidos'));
        $this->assertFalse($user->hasRole('cocina'));
        $this->assertCount(2, $user->roles);
    }
}
