<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('program', 60);
            $table->string('program_nama', 100);
            $table->string('paket', 100);
            $table->string('kategori_kode', 30);
            $table->string('nama', 150);
            $table->boolean('samarkan_nama')->default(false);
            $table->boolean('nama_singkat')->default(false);
            $table->string('nip', 30)->nullable();
            $table->string('kontak', 50);
            $table->string('email', 100)->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->string('metode', 30);
            $table->string('channel', 30)->nullable();
            $table->string('channel_nama', 100)->nullable();
            $table->string('channel_info', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('menunggu')->index(); // menunggu, dibayar, diterima, ditolak, dibatalkan
            $table->dateTime('dibayar_at')->nullable();
            $table->string('bukti', 255)->nullable();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->nullOnDelete();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diproses_at')->nullable();
            $table->string('alasan_tolak', 255)->nullable();
            $table->dateTime('dibatalkan_at')->nullable();
            $table->boolean('tampil_beranda')->default(false);
            $table->boolean('tampil_doa')->default(false);
            $table->boolean('beranda_blokir')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasi');
    }
};
