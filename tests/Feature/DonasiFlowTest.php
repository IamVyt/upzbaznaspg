<?php

namespace Tests\Feature;

use App\Models\Donasi;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DonasiFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_donatur_dapat_mengirim_donasi_online(): void
    {
        $donatur = User::where('username', 'donatur')->firstOrFail();

        $response = $this->actingAs($donatur)->post(route('donasi.prosesBayar'), [
            'program' => 'infak-dan-sedekah',
            'paket' => 0,
            'nama' => 'Ahmad Muzakki',
            'kontak' => '081234567890',
            'metode' => 'transfer',
            'channel_transfer' => 'bsi',
            'tampil_nama' => 'lengkap',
        ]);

        $response->assertSessionHasNoErrors();

        $donasi = Donasi::firstOrFail();

        $response->assertRedirect(route('donasi.sukses', [
            'kode' => $donasi->kode,
            'baru' => 1,
        ]));

        $this->assertDatabaseHas('donasi', [
            'id' => $donasi->id,
            'user_id' => $donatur->id,
            'status' => 'menunggu',
            'jumlah' => 50000,
        ]);
    }

    public function test_admin_mengonfirmasi_donasi_dan_mencatat_kas(): void
    {
        $donasi = $this->buatDonasi();
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('donasi_masuk.konfirmasi', $donasi->id))
            ->assertRedirect();

        $donasi->refresh();

        $this->assertSame('diterima', $donasi->status);
        $this->assertNotNull($donasi->transaksi_id);

        $transaksi = Transaksi::findOrFail($donasi->transaksi_id);

        $this->assertSame('masuk', $transaksi->tipe);
        $this->assertEquals(50000, $transaksi->jumlah);
    }

    public function test_ketua_tidak_boleh_mengonfirmasi_donasi(): void
    {
        $donasi = $this->buatDonasi();
        $ketua = User::where('username', 'ketua')->firstOrFail();

        $this->actingAs($ketua)
            ->post(route('donasi_masuk.konfirmasi', $donasi->id))
            ->assertRedirect()
            ->assertSessionHas('danger');

        $this->assertSame('menunggu', $donasi->fresh()->status);
        $this->assertDatabaseCount('transaksi', 0);
    }

    public function test_pengurus_dapat_menyembunyikan_donatur_dari_beranda(): void
    {
        $donasi = $this->buatDonasi([
            'status' => 'diterima',
            'diproses_at' => now(),
        ]);
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->get('/')->assertSee('Ahmad Muzakki');

        $this->actingAs($admin)
            ->post(route('data_donasi.proses'), [
                'aksi' => 'beranda',
                'id' => $donasi->id,
            ])
            ->assertRedirect();

        $this->assertTrue($donasi->fresh()->beranda_blokir);
        $this->get('/')->assertDontSee('Ahmad Muzakki');
    }

    public function test_bukti_pembayaran_disimpan_privat_dan_hanya_bisa_diakses_pemilik(): void
    {
        Storage::fake('local');

        $donasi = $this->buatDonasi();
        $donatur = User::where('username', 'donatur')->firstOrFail();
        $penggunaLain = User::factory()->create();

        // Nama asli .jpeg tetapi isinya PNG: ekstensi tersimpan harus mengikuti isi file.
        $berkas = $this->berkasPng('bukti.jpeg');

        $this->actingAs($donatur)
            ->post(route('donasi.laporBayar', $donasi->kode), ['bukti' => $berkas])
            ->assertRedirect(route('donasi.sukses', $donasi->kode));

        $donasi->refresh();

        $this->assertSame('dibayar', $donasi->status);
        $this->assertTrue(Storage::disk('local')->exists($donasi->bukti));
        $this->assertSame('png', strtolower(pathinfo($donasi->bukti, PATHINFO_EXTENSION)));
        $this->assertFileDoesNotExist(public_path('uploads/bukti/'.basename($donasi->bukti)));

        $this->actingAs($donatur)
            ->get(route('donasi.bukti', $donasi->kode))
            ->assertOk();

        $this->actingAs($penggunaLain)
            ->get(route('donasi.bukti', $donasi->kode))
            ->assertForbidden();

        $this->get('/uploads/bukti/'.basename($donasi->bukti))->assertNotFound();
    }

    public function test_berkas_dengan_nama_php_ditolak(): void
    {
        Storage::fake('local');

        $donasi = $this->buatDonasi();
        $donatur = User::where('username', 'donatur')->firstOrFail();

        $this->actingAs($donatur)
            ->post(route('donasi.laporBayar', $donasi->kode), [
                'bukti' => $this->berkasPng('bukti.php'),
            ])
            ->assertSessionHasErrors('bukti');

        $donasi->refresh();

        $this->assertSame('menunggu', $donasi->status);
        $this->assertNull($donasi->bukti);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function berkasPng(string $nama): UploadedFile
    {
        $isiPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        $tmp = tempnam(sys_get_temp_dir(), 'bukti');
        file_put_contents($tmp, $isiPng);

        return new UploadedFile($tmp, $nama, 'image/png', null, true);
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
