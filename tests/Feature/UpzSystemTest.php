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
            'email' => 'donatur_baru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'donatur_baru',
            'email' => 'donatur_baru@example.com',
            'no_hp' => null,
            'email_verified_at' => null,
        ]);
    }

    public function test_user_can_register_with_optional_phone(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Donatur Telepon',
            'username' => 'donatur_wa',
            'email' => 'donatur_wa@example.com',
            'no_hp' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'donatur_wa',
            'email' => 'donatur_wa@example.com',
            'no_hp' => '081234567890',
        ]);
    }

    public function test_bayar_page_redirects_guest_to_login(): void
    {
        $response = $this->get('/bayar');
        $response->assertRedirect(route('login'));
    }

    public function test_bayar_page_redirects_user_if_no_program_params(): void
    {
        $user = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get('/bayar');
        $response->assertRedirect(route('donasi.create'));
    }

    public function test_bayar_page_loads_with_valid_program_params(): void
    {
        $user = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($user)->get('/bayar?program=zakat-penghasilan&paket=0');
        $response->assertStatus(200);
    }

    public function test_logout_works_via_get_and_post(): void
    {
        $user = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);

        // Test POST logout
        $resPost = $this->actingAs($user)->post('/logout');
        $resPost->assertRedirect(route('home'));
        $this->assertGuest();

        // Test GET logout
        $user2 = User::where('role', 'user')->first() ?? User::factory()->create(['role' => 'user']);
        $resGet = $this->actingAs($user2)->get('/logout');
        $resGet->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_non_admin_staff_redirected_from_user_and_audit_management(): void
    {
        $bendahara = User::where('role', 'bendahara')->first() ?? User::factory()->create(['role' => 'bendahara']);

        $resUsers = $this->actingAs($bendahara)->get('/admin/users');
        $resUsers->assertRedirect(route('dashboard'));
        $resUsers->assertSessionHas('danger');

        $resAudit = $this->actingAs($bendahara)->get('/admin/audit');
        $resAudit->assertRedirect(route('dashboard'));
        $resAudit->assertSessionHas('danger');
    }

    public function test_staff_dashboard_renders_with_topbar_and_user_panel(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('staff-header', false);
        $response->assertSee('Panel Admin');
        $response->assertSee('Keluar');
    }
}
