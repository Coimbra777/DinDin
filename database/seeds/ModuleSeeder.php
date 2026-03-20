<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Só grupos e vínculos a módulos já criados pelas migrations (dashboard, finanças, etc.).
 * O CMS antigo (blog, páginas, clientes, admin de grupos) foi removido das rotas.
 */
class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        if (! DB::getSchemaBuilder()->hasTable('groups') || ! DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        if (! DB::table('groups')->where('name', 'Administrador')->exists()) {
            DB::table('groups')->insert([
                'name' => 'Administrador',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! DB::getSchemaBuilder()->hasTable('group_module')) {
            return;
        }

        $groups = DB::table('groups')->pluck('id');
        $modules = DB::table('modules')->pluck('id');

        foreach ($groups as $groupId) {
            foreach ($modules as $moduleId) {
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
    }
}
