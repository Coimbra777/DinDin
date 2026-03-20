<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getSchemaBuilder()->hasTable('modules') === false) {
            return;
        }

        if (DB::table('modules')->where('path', 'dashboard')->exists()) {
            return;
        }

        // id fixo 11: o ModuleSeeder usa 1,2,3,5,6,7,8,9,10 — evita conflito com insertGetId (=1) antes do seed.
        $moduleId = 11;
        $now = now()->format('Y-m-d H:i:s');
        DB::table('modules')->insert([
            'id' => $moduleId,
            'name' => 'Início',
            'father_path' => '',
            'path' => 'dashboard',
            'father_order' => 0,
            'order' => 0,
            'icon' => 'fa fa-home',
            'has_son' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $groups = DB::table('groups')->pluck('id');
        foreach ($groups as $groupId) {
            $exists = DB::table('group_module')
                ->where('group_id', $groupId)
                ->where('module_id', $moduleId)
                ->exists();
            if (!$exists) {
                DB::table('group_module')->insert([
                    'group_id' => $groupId,
                    'module_id' => $moduleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        $moduleId = DB::table('modules')->where('path', 'dashboard')->value('id');
        if ($moduleId && DB::getSchemaBuilder()->hasTable('group_module')) {
            DB::table('group_module')->where('module_id', $moduleId)->delete();
        }
        DB::table('modules')->where('path', 'dashboard')->delete();
    }
};
