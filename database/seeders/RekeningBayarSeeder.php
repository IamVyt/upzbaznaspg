<?php

namespace Database\Seeders;

use App\Models\RekeningBayar;
use Illuminate\Database\Seeder;

class RekeningBayarSeeder extends Seeder
{
    public function run(): void
    {
        $rekening = [
            ['jenis' => 'bank', 'kode' => 'bsi', 'nama' => 'Bank Syariah Indonesia (BSI)', 'nomor' => '2020300041', 'atas_nama' => 'UPZ BAZNAS Petrokimia Gresik', 'aktif' => true, 'urutan' => 1],
            ['jenis' => 'bank', 'kode' => 'bca', 'nama' => 'Bank BCA', 'nomor' => '1234567890', 'atas_nama' => 'UPZ BAZNAS Petrokimia Gresik', 'aktif' => true, 'urutan' => 2],
            ['jenis' => 'bank', 'kode' => 'bni', 'nama' => 'Bank BNI', 'nomor' => '0987654321', 'atas_nama' => 'UPZ BAZNAS Petrokimia Gresik', 'aktif' => true, 'urutan' => 3],
            ['jenis' => 'bank', 'kode' => 'bri', 'nama' => 'Bank BRI', 'nomor' => '5432109876', 'atas_nama' => 'UPZ BAZNAS Petrokimia Gresik', 'aktif' => false, 'urutan' => 4],
            ['jenis' => 'bank', 'kode' => 'mandiri', 'nama' => 'Bank Mandiri', 'nomor' => '6789012345', 'atas_nama' => 'UPZ BAZNAS Petrokimia Gresik', 'aktif' => false, 'urutan' => 5],
            ['jenis' => 'ewallet', 'kode' => 'gopay', 'nama' => 'GoPay', 'nomor' => '082264904535', 'atas_nama' => 'UPZ Petrokimia Gresik', 'aktif' => true, 'urutan' => 1],
            ['jenis' => 'ewallet', 'kode' => 'ovo', 'nama' => 'OVO', 'nomor' => '082264904535', 'atas_nama' => 'UPZ Petrokimia Gresik', 'aktif' => true, 'urutan' => 2],
            ['jenis' => 'ewallet', 'kode' => 'dana', 'nama' => 'DANA', 'nomor' => '082264904535', 'atas_nama' => 'UPZ Petrokimia Gresik', 'aktif' => true, 'urutan' => 3],
            ['jenis' => 'ewallet', 'kode' => 'shopeepay', 'nama' => 'ShopeePay', 'nomor' => '082264904535', 'atas_nama' => 'UPZ Petrokimia Gresik', 'aktif' => false, 'urutan' => 4],
        ];

        foreach ($rekening as $item) {
            RekeningBayar::updateOrCreate(
                ['jenis' => $item['jenis'], 'kode' => $item['kode']],
                $item
            );
        }
    }
}
