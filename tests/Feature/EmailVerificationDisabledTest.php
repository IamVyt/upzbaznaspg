<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class EmailVerificationDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['upz.fitur.verifikasi_email' => false]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_registrasi_langsung_membuka_akun_tanpa_mengirim_verifikasi(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Donatur Tanpa Verifikasi',
            'username' => 'donatur_tanpa_verifikasi',
            'email' => 'tanpa.verifikasi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $user = User::where('username', 'donatur_tanpa_verifikasi')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertNotSentTo($user, VerifyEmailNotification::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Email belum diverifikasi');
    }

    public function test_petugas_belum_verifikasi_bisa_membuka_dashboard(): void
    {
        $petugas = User::factory()->create([
            'role' => 'bendahara',
            'email' => 'petugas.belum.verifikasi@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($petugas)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_lupa_password_bisa_dipakai_tanpa_verifikasi_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'belum.verifikasi@example.com',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('PasswordBaru123', $user->fresh()->password));
    }

    public function test_mengubah_email_tidak_membatalkan_verifikasi_dan_tidak_mengirim_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'username' => 'donatur_ubah_email',
            'email' => 'email.lama@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => 'email.baru@example.com',
                'no_hp' => $user->no_hp,
                'nip' => $user->nip,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('email.baru@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNotSentTo($user, VerifyEmailNotification::class);
    }

    public function test_halaman_verifikasi_mengalihkan_karena_fitur_nonaktif(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'tanpa.halaman.verifikasi@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('info');
    }

    public function test_tombol_kirim_ulang_di_kelola_pengguna_disembunyikan(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $user = User::factory()->unverified()->create([
            'email' => 'perlu.verifikasi@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Verifikasi email sedang dinonaktifkan')
            ->assertDontSee(route('users.resendVerification', $user->id));
    }
}
