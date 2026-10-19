<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_dana', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 30)->unique();
            $table->string('tipe', 20); // masuk, keluar
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_dana');
    }
};
