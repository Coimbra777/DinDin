<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('saas_modules')->where('slug', 'finance')->update(['name' => 'Financeiro']);

        $now = now();
        foreach ([
            ['name' => 'Relatórios', 'slug' => 'reports'],
            ['name' => 'Projeções', 'slug' => 'projections'],
        ] as $row) {
            if (! DB::table('saas_modules')->where('slug', $row['slug'])->exists()) {
                DB::table('saas_modules')->insert([
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('saas_modules')->whereIn('slug', ['reports', 'projections'])->delete();
    }
};
