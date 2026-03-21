<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        if (! DB::table('saas_modules')->where('slug', 'planning')->exists()) {
            DB::table('saas_modules')->insert([
                'name' => 'Planejamento',
                'slug' => 'planning',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('saas_modules')->where('slug', 'planning')->delete();
    }
};
