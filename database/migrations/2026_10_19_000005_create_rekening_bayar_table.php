<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekening_bayar', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20); // bank, ewallet
            $table->string('kode', 30);
            $table->string('nama', 100);
            $table->string('nomor', 50)->default('');
            $table->string('atas_nama', 100)->default('');
            $table->boolean('aktif')->default(false);
            $table->integer('urutan')->default(0);
            $table->timestamps();

            $table->unique(['jenis', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekening_bayar');
    }
};
