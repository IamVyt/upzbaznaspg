<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_registrasi_wajib_mengisi_email(): void
    {
        $this->post(route('register'), [
            'name' => 'Donatur Tanpa Email',
            'username' => 'tanpa_email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['username' => 'tanpa_email']);
    }

    public function test_registrasi_mengirim_link_verifikasi_dan_akun_belum_terverifikasi(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Donatur Verifikasi',
            'username' => 'donatur_verifikasi',
            'email' => 'Donatur.Verifikasi@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('success');

        $user = User::where('username', 'donatur_verifikasi')->firstOrFail();

        $this->assertSame('donatur.verifikasi@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_halaman_verifikasi_menampilkan_email_dan_tombol_kirim_ulang(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'tampil@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('tampil@example.com')
            ->assertSee('Kirim ulang email verifikasi');
    }

    public function test_notifikasi_verifikasi_memuat_signed_url(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'signed@example.com',
        ]);

        $mail = (new VerifyEmailNotification)->toMail($user);

        $this->assertStringContainsString('/email/verify/'.$user->id, $mail->actionUrl);
        $this->assertStringContainsString('signature=', $mail->actionUrl);
    }

    public function test_pengguna_dapat_meminta_ulang_email_verifikasi(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'belum.verifikasi@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_link_verifikasi_yang_sah_menandai_email_terverifikasi(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'calon.terverifikasi@example.com',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'verifikasi_email',
            'record_id' => $user->id,
        ]);
    }

    public function test_link_verifikasi_dengan_hash_salah_ditolak(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'hash.salah@example.com',
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('email-lain@example.com'),
        ]);

        $this->actingAs($user)
            ->get($url)
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_belum_terverifikasi_tidak_menerima_link_reset_password(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'belum.bisa.reset@example.com',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_token_reset_tidak_bisa_dipakai_selama_email_belum_terverifikasi(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'token.belum.verifikasi@example.com',
            'password' => Hash::make('password-lama'),
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }

    public function test_mengubah_email_di_profil_membatalkan_verifikasi_dan_mengirim_link_baru(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'username' => 'donatur_profil',
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
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_admin_melihat_status_verifikasi_email_di_kelola_pengguna(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        User::factory()->unverified()->create([
            'name' => 'Petugas Belum Verifikasi',
            'email' => 'belum.verifikasi@example.com',
        ]);

        User::factory()->create([
            'name' => 'Petugas Sudah Verifikasi',
            'email' => 'sudah.verifikasi@example.com',
        ]);

        User::factory()->create([
            'name' => 'Petugas Tanpa Email',
            'email' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Status email')
            ->assertSee('Terverifikasi')
            ->assertSee('Belum verifikasi')
            ->assertSee('Tanpa email');
    }

    public function test_admin_mengubah_email_petugas_mereset_status_verifikasi(): void
    {
        Notification::fake();

        $admin = User::where('username', 'admin')->firstOrFail();
        $petugas = User::factory()->create([
            'role' => 'bendahara',
            'email' => 'petugas.lama@example.com',
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'aksi' => 'ubah',
                'id' => $petugas->id,
                'name' => $petugas->name,
                'username' => $petugas->username,
                'email' => 'petugas.baru@example.com',
                'role' => 'bendahara',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $petugas->refresh();

        $this->assertSame('petugas.baru@example.com', $petugas->email);
        $this->assertNull($petugas->email_verified_at);
        Notification::assertSentTo($petugas, VerifyEmailNotification::class);
    }
}
