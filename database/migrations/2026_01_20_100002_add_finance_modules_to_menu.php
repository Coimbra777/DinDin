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

        $now = now()->format('Y-m-d H:i:s');

        if (!DB::table('modules')->where('path', 'finance')->exists()) {
            DB::table('modules')->insert([
                'name' => 'Finanças',
                'father_path' => '',
                'path' => 'finance',
                'father_order' => 50,
                'order' => 0,
                'icon' => 'fa fa-wallet',
                'has_son' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $financeId = DB::table('modules')->where('path', 'finance')->value('id');
        if (!$financeId) {
            return;
        }

        $children = [
            ['name' => 'Transações', 'path' => 'finance_transactions', 'order' => 1],
            ['name' => 'Categorias', 'path' => 'finance_categories', 'order' => 2],
        ];

        foreach ($children as $child) {
            if (DB::table('modules')->where('path', $child['path'])->exists()) {
                continue;
            }
            DB::table('modules')->insert([
                'name' => $child['name'],
                'father_path' => 'finance',
                'path' => $child['path'],
                'father_order' => 50,
                'order' => $child['order'],
                'icon' => '',
                'has_son' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $moduleIds = DB::table('modules')
            ->whereIn('path', ['finance', 'finance_transactions', 'finance_categories'])
            ->pluck('id');

        $groups = DB::table('groups')->pluck('id');
        foreach ($moduleIds as $moduleId) {
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
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        $paths = ['finance_categories', 'finance_transactions', 'finance'];
        $ids = DB::table('modules')->whereIn('path', $paths)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (DB::getSchemaBuilder()->hasTable('group_module')) {
            DB::table('group_module')->whereIn('module_id', $ids)->delete();
        }
        DB::table('modules')->whereIn('path', $paths)->delete();
    }
};
