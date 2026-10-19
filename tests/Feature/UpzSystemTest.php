<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpzSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_landing_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('UPZ BAZNAS PG');
        $response->assertSee('Salurkan');
    }

    public function test_salurkan_page_loads_successfully(): void
    {
        $response = $this->get(route('donasi.create'));
        $response->assertStatus(200);
        $response->assertSee('Salurkan');
        $response->assertSee('Pilih paket');
    }

    public function test_login_page_loads_successfully(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Masuk');
    }

    public function test_staff_can_login_and_access_dashboard(): void
    {
        $response = $this->post(route('login'), [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Riwayat transaksi');
    }

    public function test_user_can_register_without_phone(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Donatur Baru',
            'username' => 'donatur_baru',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'donatur_baru',
            'no_hp' => null,
        ]);
    }

    public function test_user_can_register_with_optional_phone(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Donatur Telepon',
            'username' => 'donatur_wa',
            'no_hp' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'donatur_wa',
            'no_hp' => '081234567890',
        ]);
    }
}
