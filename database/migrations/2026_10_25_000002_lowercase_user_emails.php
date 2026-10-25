<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('email')
            ->update(['email' => DB::raw('LOWER(email)')]);
    }

    public function down(): void
    {
        // Tidak ada perubahan yang perlu dibalik.
    }
};
