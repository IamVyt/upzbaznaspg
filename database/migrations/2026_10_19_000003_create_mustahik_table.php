<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mustahik', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100)->index();
            $table->string('nik', 20)->nullable();
            $table->string('kontak', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('asnaf', 30)->default('fakir'); // fakir, miskin, amil, muallaf, riqab, gharim, sabilillah, ibnu_sabil
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mustahik');
    }
};
