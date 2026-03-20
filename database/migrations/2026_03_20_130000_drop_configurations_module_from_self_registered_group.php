<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('group_module') || ! DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        $groupId = DB::table('groups')->where('name', 'Cadastro público')->value('id');
        if (! $groupId) {
            return;
        }

        $moduleId = DB::table('modules')->where('path', 'configurations')->value('id');
        if (! $moduleId) {
            return;
        }

        DB::table('group_module')
            ->where('group_id', $groupId)
            ->where('module_id', $moduleId)
            ->delete();
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $groupId = DB::table('groups')->where('name', 'Cadastro público')->value('id');
        $moduleId = DB::table('modules')->where('path', 'configurations')->value('id');
        if (! $groupId || ! $moduleId) {
            return;
        }

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
};
