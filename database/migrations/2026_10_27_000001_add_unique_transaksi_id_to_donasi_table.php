<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu catatan kas hanya boleh terhubung ke satu donasi. Ini mencegah
        // dua donasi (atau dua request bersamaan) memakai transaksi yang sama.
        Schema::table('donasi', function (Blueprint $table) {
            $table->unique('transaksi_id');
        });
    }

    public function down(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->dropUnique(['transaksi_id']);
        });
    }
};
