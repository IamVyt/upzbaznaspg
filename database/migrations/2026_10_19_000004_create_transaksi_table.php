<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 20)->index(); // masuk, keluar
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_dana')->nullOnDelete();
            $table->unsignedBigInteger('pihak_id')->nullable();
            $table->string('nama_pihak', 150)->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->dateTime('tanggal')->index();
            $table->text('keterangan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dibatalkan_at')->nullable()->index();
            $table->text('alasan_batal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
