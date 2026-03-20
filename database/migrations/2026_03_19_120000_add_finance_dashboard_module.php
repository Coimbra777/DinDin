<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        DB::table('modules')
            ->where('father_path', 'finance')
            ->where('path', 'finance_transactions')
            ->update(['order' => 2]);

        DB::table('modules')
            ->where('father_path', 'finance')
            ->where('path', 'finance_categories')
            ->update(['order' => 3]);

        if (DB::table('modules')->where('path', 'finance_dashboard')->exists()) {
            return;
        }

        $now = now()->format('Y-m-d H:i:s');
        DB::table('modules')->insert([
            'name' => 'Resumo',
            'father_path' => 'finance',
            'path' => 'finance_dashboard',
            'father_order' => 50,
            'order' => 1,
            'icon' => '',
            'has_son' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $moduleId = DB::table('modules')->where('path', 'finance_dashboard')->value('id');
        if (!$moduleId) {
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

        $id = DB::table('modules')->where('path', 'finance_dashboard')->value('id');
        if ($id && DB::getSchemaBuilder()->hasTable('group_module')) {
            DB::table('group_module')->where('module_id', $id)->delete();
        }
        DB::table('modules')->where('path', 'finance_dashboard')->delete();

        DB::table('modules')
            ->where('father_path', 'finance')
            ->where('path', 'finance_transactions')
            ->update(['order' => 1]);

        DB::table('modules')
            ->where('father_path', 'finance')
            ->where('path', 'finance_categories')
            ->update(['order' => 2]);
    }
};
