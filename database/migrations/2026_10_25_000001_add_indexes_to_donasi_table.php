<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('program');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['program']);
            $table->dropIndex(['created_at']);
        });
    }
};
