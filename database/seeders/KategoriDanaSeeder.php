<?php

namespace Database\Seeders;

use App\Models\KategoriDana;
use Illuminate\Database\Seeder;

class KategoriDanaSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            // Pemasukan
            ['nama' => 'Zakat Maal',   'kode' => 'ZAKAT_MAAL',   'tipe' => 'masuk', 'keterangan' => 'Zakat harta atau penghasilan'],
            ['nama' => 'Zakat Fitrah', 'kode' => 'ZAKAT_FITRAH', 'tipe' => 'masuk', 'keterangan' => 'Zakat jiwa di bulan Ramadan'],
            ['nama' => 'Infak',        'kode' => 'INFAK',        'tipe' => 'masuk', 'keterangan' => 'Infak sukarela'],
            ['nama' => 'Sedekah',      'kode' => 'SEDEKAH',      'tipe' => 'masuk', 'keterangan' => 'Sedekah atau donasi umum'],
            ['nama' => 'Dana Sosial',  'kode' => 'DANA_SOSIAL',  'tipe' => 'masuk', 'keterangan' => 'Dana sosial keagamaan dan kemanusiaan'],
            ['nama' => 'Fidyah',       'kode' => 'FIDYAH',       'tipe' => 'masuk', 'keterangan' => 'Pembayaran fidyah'],
            ['nama' => 'Wakaf',        'kode' => 'WAKAF',        'tipe' => 'masuk', 'keterangan' => 'Dana wakaf tunai'],

            // Pengeluaran
            ['nama' => 'Bantuan Fakir Miskin',    'kode' => 'FAKIR_MISKIN', 'tipe' => 'keluar', 'keterangan' => 'Penyaluran untuk fakir dan miskin'],
            ['nama' => 'Beasiswa Pendidikan',     'kode' => 'BEASISWA',     'tipe' => 'keluar', 'keterangan' => 'Bantuan biaya pendidikan'],
            ['nama' => 'Bantuan Kesehatan',       'kode' => 'KESEHATAN',    'tipe' => 'keluar', 'keterangan' => 'Biaya pengobatan dan kesehatan'],
            ['nama' => 'Bantuan Muallaf',         'kode' => 'MUALLAF',      'tipe' => 'keluar', 'keterangan' => 'Bantuan bagi muallaf'],
            ['nama' => 'Bantuan Gharim (Hutang)', 'kode' => 'GHARIM',       'tipe' => 'keluar', 'keterangan' => 'Bantuan melunasi hutang mendesak'],
            ['nama' => 'Dakwah dan Sabilillah',   'kode' => 'DAKWAH',       'tipe' => 'keluar', 'keterangan' => 'Kegiatan dakwah dan sabilillah'],
            ['nama' => 'Bantuan Ibnu Sabil',      'kode' => 'IBNU_SABIL',   'tipe' => 'keluar', 'keterangan' => 'Bantuan musafir'],
            ['nama' => 'Program Umum / Lainnya',  'kode' => 'UMUM',         'tipe' => 'keluar', 'keterangan' => 'Program umum atau insidental'],
        ];

        foreach ($kategori as $item) {
            KategoriDana::updateOrCreate(['kode' => $item['kode']], $item);
        }
    }
}
