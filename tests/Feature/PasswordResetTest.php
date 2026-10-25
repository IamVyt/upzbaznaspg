<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_halaman_lupa_password_dan_link_di_login_tersedia(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa password');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->get(route('password.reset', [
            'token' => 'contoh-token',
            'email' => 'donatur@gmail.com',
        ]))
            ->assertOk()
            ->assertSee('Password baru');
    }

    public function test_link_reset_dikirim_ke_email_terdaftar(): void
    {
        Notification::fake();

        $user = User::where('username', 'admin')->firstOrFail();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_password_dapat_direset_dengan_token_valid(): void
    {
        $user = User::where('username', 'donatur')->firstOrFail();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertTrue(Hash::check('PasswordBaru123', $user->password));
        $this->assertFalse(Hash::check('donatur123', $user->password));
        $this->assertDatabaseHas('audit_log', [
            'aksi' => 'reset_password',
            'record_id' => $user->id,
        ]);
    }

    public function test_token_tidak_valid_ditolak(): void
    {
        $user = User::where('username', 'donatur')->firstOrFail();

        $this->from(route('password.reset', [
            'token' => 'token-palsu',
            'email' => $user->email,
        ]))->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'PasswordBaru123',
            'password_confirmation' => 'PasswordBaru123',
        ])
            ->assertRedirect(route('password.reset', [
                'token' => 'token-palsu',
                'email' => $user->email,
            ]))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('donatur123', $user->fresh()->password));
    }

    public function test_email_tidak_terdaftar_tetap_memberi_pesan_generik(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak-ada@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }
}
