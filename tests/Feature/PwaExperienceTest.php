<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PwaExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $pedidosRole = Role::firstOrCreate(['slug' => 'pedidos'], ['name' => 'Pedidos']);

        $this->user = User::factory()->create(['active' => true]);
        $this->user->roles()->attach([$adminRole->id, $pedidosRole->id]);
    }

    /**
     * Test layout rendered HTML contains PWA manifest link and required meta tags.
     */
    public function test_layout_contains_pwa_manifest_and_meta_tags(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/pedidos/nuevo');

        $response->assertStatus(200);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('<meta name="theme-color" content="#0E141B">', false);
        $response->assertSee('<meta name="mobile-web-app-capable" content="yes">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">', false);
        $response->assertSee('<meta name="apple-mobile-web-app-title" content="Pedidos Negocio">', false);
        $response->assertSee('<link rel="apple-touch-icon" href="/icons/icon-192x192.png">', false);
        $response->assertSee('id="pwaOfflineBanner"', false);
    }

    /**
     * Test "Más" menu page renders the PWA installation card, debug section and iOS Safari modal elements.
     */
    public function test_menu_page_contains_pwa_install_card_container_and_ios_modal(): void
    {
        Livewire::actingAs($this->user)
            ->test(\App\Livewire\MenuPage::class)
            ->assertSee('pwaInstallCard')
            ->assertSee('iosPwaModal')
            ->assertSee('Instalar Aplicación')
            ->assertSee('pwaCardNotice')
            ->assertSee('pwaDebugInfo');
    }

    /**
     * Test navigation and logout functionality remain intact after PWA additions.
     */
    public function test_navigation_and_logout_remain_unbroken(): void
    {
        $this->actingAs($this->user);

        // Navigation check
        $this->get('/pedidos')->assertStatus(200);
        $this->get('/cocina')->assertStatus(200);
        $this->get('/reparto')->assertStatus(200);

        // Logout check
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
