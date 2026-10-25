<?php

namespace Tests\Feature;

use App\Models\RekeningBayar;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RekeningBayarSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_tidak_mereset_password_atau_rekening_yang_sudah_diubah(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $admin->update(['password' => Hash::make('PasswordBaru123')]);

        $bsi = RekeningBayar::where('jenis', 'bank')->where('kode', 'bsi')->firstOrFail();
        $bsi->update(['nomor' => '9999999999']);

        $this->seed(UserSeeder::class);
        $this->seed(RekeningBayarSeeder::class);

        $this->assertTrue(Hash::check('PasswordBaru123', $admin->fresh()->password));
        $this->assertSame('9999999999', $bsi->fresh()->nomor);
    }

    public function test_login_dibatasi_setelah_enam_percobaan_gagal(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'username' => 'admin',
                'password' => 'password-salah',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password-salah',
        ])->assertStatus(429);
    }

    public function test_admin_dapat_membuka_kelola_pengguna_dengan_kolom_email(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Email');
    }

    public function test_admin_dapat_menyimpan_email_petugas_untuk_reset_password(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'aksi' => 'tambah',
                'name' => 'Petugas Baru',
                'username' => 'petugasbaru',
                'email' => 'Petugas@Example.com',
                'role' => 'bendahara',
                'password' => 'password123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'username' => 'petugasbaru',
            'email' => 'petugas@example.com',
        ]);
    }

    public function test_bendahara_tidak_bisa_mengubah_metode_pembayaran(): void
    {
        $bendahara = User::where('username', 'bendahara')->firstOrFail();
        $bsi = RekeningBayar::where('kode', 'bsi')->firstOrFail();

        $this->actingAs($bendahara)
            ->put(route('pembayaran.update'), [
                'aksi' => 'simpan',
                'id' => $bsi->id,
                'nama' => $bsi->nama,
                'nomor' => '1111111111',
                'atas_nama' => $bsi->atas_nama,
                'aktif' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('danger');

        $this->assertSame('2020300041', $bsi->fresh()->nomor);
    }
}
