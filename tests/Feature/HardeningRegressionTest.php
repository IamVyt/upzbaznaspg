<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\KategoriDana;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class HardeningRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_konfirmasi_donasi_gagal_jika_kategori_donasi_tidak_aktif(): void
    {
        $donasi = $this->buatDonasi(['kategori_kode' => 'ZAKAT_MAAL']);
        KategoriDana::where('kode', 'ZAKAT_MAAL')->update(['aktif' => false]);

        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('donasi_masuk.konfirmasi', $donasi->id))
            ->assertRedirect()
            ->assertSessionHas('danger');

        $this->assertSame('menunggu', $donasi->fresh()->status);
        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_donasi_hanya_bisa_dikonfirmasi_sekali(): void
    {
        $donasi = $this->buatDonasi();
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('donasi_masuk.konfirmasi', $donasi->id))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('donasi_masuk.konfirmasi', $donasi->id))
            ->assertSessionHas('danger');

        $this->assertSame(1, Transaksi::count());
        $this->assertSame('diterima', $donasi->fresh()->status);
    }

    public function test_admin_tidak_bisa_menurunkan_peran_akun_sendiri(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'aksi' => 'ubah',
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'user',
            ])
            ->assertRedirect()
            ->assertSessionHas('danger');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_username_saat_registrasi_dinormalisasi_sebelum_cek_unik(): void
    {
        $this->post(route('register'), [
            'name' => 'Admin Palsu',
            'username' => 'Admin',
            'email' => 'admin.palsu@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['email' => 'admin.palsu@example.com']);
    }

    public function test_login_tidak_peka_huruf_besar_kecil(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->post(route('login'), [
            'username' => 'Admin',
            'password' => 'admin123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_profil_menolak_username_yang_bentrok_setelah_normalisasi(): void
    {
        $donatur = User::where('username', 'donatur')->firstOrFail();

        $this->actingAs($donatur)
            ->put(route('profile.update'), [
                'name' => $donatur->name,
                'username' => 'ADMIN',
                'email' => $donatur->email,
                'no_hp' => $donatur->no_hp,
                'nip' => $donatur->nip,
            ])
            ->assertSessionHasErrors('username');

        $this->assertSame('donatur', $donatur->fresh()->username);
    }

    public function test_qris_dengan_ekstensi_jpeg_dikenali(): void
    {
        $path = public_path('img/qris.jpeg');
        file_put_contents($path, 'qris-test');

        try {
            $donasi = $this->buatDonasi([
                'metode' => 'qris',
                'channel' => null,
                'channel_nama' => 'QRIS UPZ BAZNAS',
                'channel_info' => null,
            ]);
            $donatur = User::where('username', 'donatur')->firstOrFail();

            $this->actingAs($donatur)
                ->get(route('donasi.sukses', $donasi->kode))
                ->assertOk()
                ->assertSee('img/qris.jpeg');
        } finally {
            @unlink($path);
        }
    }

    public function test_lapor_bayar_wajib_menyertakan_bukti_saat_belum_ada(): void
    {
        Storage::fake('local');

        $donasi = $this->buatDonasi();
        $donatur = User::where('username', 'donatur')->firstOrFail();

        $this->actingAs($donatur)
            ->post(route('donasi.laporBayar', $donasi->kode), [])
            ->assertSessionHasErrors('bukti');

        $donasi->refresh();
        $this->assertSame('menunggu', $donasi->status);
        $this->assertNull($donasi->bukti);
    }

    public function test_dashboard_menolak_jumlah_tiga_desimal_dan_tanggal_tidak_valid(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $kategori = KategoriDana::where('tipe', 'masuk')->firstOrFail();

        $dasar = [
            'aksi' => 'tambah',
            'tipe' => 'masuk',
            'kategori_id' => $kategori->id,
            'nama' => 'Uji Validasi',
            'tanggal' => now()->format('Y-m-d'),
        ];

        $this->actingAs($admin)
            ->post(route('dashboard.proses'), array_merge($dasar, ['jumlah' => '0.001']))
            ->assertSessionHas('danger');

        $this->actingAs($admin)
            ->post(route('dashboard.proses'), array_merge($dasar, [
                'jumlah' => '1000',
                'tanggal' => '2026-02-30',
            ]))
            ->assertSessionHas('danger');

        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_transaksi_yang_terhubung_donasi_tidak_bisa_di_void(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $kategori = KategoriDana::where('tipe', 'masuk')->firstOrFail();

        $trx = Transaksi::create([
            'tipe' => 'masuk',
            'kategori_id' => $kategori->id,
            'nama_pihak' => 'Ahmad Muzakki',
            'jumlah' => 50000,
            'tanggal' => now(),
            'keterangan' => 'Donasi online uji',
            'dibuat_oleh' => $admin->id,
        ]);

        $donasi = $this->buatDonasi([
            'status' => 'diterima',
            'transaksi_id' => $trx->id,
            'diproses_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.proses'), [
                'aksi' => 'void',
                'id' => $trx->id,
                'alasan' => 'Uji konsistensi',
            ])
            ->assertSessionHas('danger');

        $this->assertNull($trx->fresh()->dibatalkan_at);
        $this->assertSame('diterima', $donasi->fresh()->status);
    }

    public function test_audit_log_ubah_tampilan_menyimpan_nilai_lama(): void
    {
        $donasi = $this->buatDonasi([
            'status' => 'diterima',
            'tampil_beranda' => true,
            'tampil_doa' => true,
            'catatan' => 'Doa untuk keluarga',
            'diproses_at' => now(),
        ]);
        $donatur = User::where('username', 'donatur')->firstOrFail();

        $this->actingAs($donatur)
            ->post(route('donasi.ubahTampilan', $donasi->kode), [
                'tampil_nama' => 'lengkap',
            ])
            ->assertRedirect();

        $log = AuditLog::where('aksi', 'donasi_ubah_tampilan')->latest('id')->firstOrFail();

        $this->assertTrue((bool) $log->data_lama['beranda']);
        $this->assertTrue((bool) $log->data_lama['doa']);
        $this->assertFalse((bool) $log->data_baru['beranda']);
        $this->assertFalse((bool) $log->data_baru['doa']);
    }

    public function test_guest_dan_pengguna_lain_tidak_bisa_melihat_detail_donasi(): void
    {
        $donasi = $this->buatDonasi();

        $this->get(route('donasi.sukses', $donasi->kode))
            ->assertRedirect(route('login'));

        $penggunaLain = User::factory()->create();

        $this->actingAs($penggunaLain)
            ->get(route('donasi.sukses', $donasi->kode))
            ->assertForbidden();
    }

    public function test_petugas_belum_verifikasi_email_tidak_bisa_masuk_dashboard(): void
    {
        $petugas = User::factory()->create([
            'role' => 'bendahara',
            'email' => 'petugas.belum.verifikasi@example.com',
            'email_verified_at' => null,
        ]);

        $this->actingAs($petugas)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    private function buatDonasi(array $override = []): Donasi
    {
        $donatur = User::where('username', 'donatur')->firstOrFail();

        return Donasi::create(array_merge([
            'kode' => 'UPZ-TEST-'.strtoupper(Str::random(8)),
            'user_id' => $donatur->id,
            'program' => 'infak-dan-sedekah',
            'program_nama' => 'Infak dan sedekah',
            'paket' => 'Paket Berbagi',
            'kategori_kode' => 'INFAK',
            'nama' => 'Ahmad Muzakki',
            'kontak' => '081234567890',
            'email' => 'donatur@gmail.com',
            'jumlah' => 50000,
            'metode' => 'transfer',
            'channel' => 'bsi',
            'channel_nama' => 'Bank Syariah Indonesia (BSI)',
            'channel_info' => '2020300041 a.n. UPZ BAZNAS Petrokimia Gresik',
            'status' => 'menunggu',
            'tampil_beranda' => true,
            'beranda_blokir' => false,
            'diproses_at' => null,
        ], $override));
    }
}
