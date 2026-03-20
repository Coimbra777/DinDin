<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('groups') || ! DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        $name = 'Cadastro público';
        $groupId = DB::table('groups')->where('name', $name)->value('id');

        if (! $groupId) {
            $now = now()->format('Y-m-d H:i:s');
            $groupId = DB::table('groups')->insertGetId([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $moduleIds = DB::table('modules')
            ->where(function ($q) {
                $q->whereIn('path', ['dashboard', 'finance'])
                    ->orWhere('father_path', 'finance');
            })
            ->pluck('id');

        foreach ($moduleIds as $moduleId) {
            $exists = DB::table('group_module')
                ->where('group_id', $groupId)
                ->where('module_id', $moduleId)
                ->exists();
            if (! $exists) {
                DB::table('group_module')->insert([
                    'group_id' => $groupId,
                    'module_id' => $moduleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('groups')) {
            return;
        }

        $groupId = DB::table('groups')->where('name', 'Cadastro público')->value('id');
        if (! $groupId) {
            return;
        }

        if (DB::getSchemaBuilder()->hasTable('group_module')) {
            DB::table('group_module')->where('group_id', $groupId)->delete();
        }

        DB::table('groups')->where('id', $groupId)->delete();
    }
};
