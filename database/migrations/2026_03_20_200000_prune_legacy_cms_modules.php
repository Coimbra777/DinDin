<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Módulos do CMS institucional removidos das rotas. */
    private const LEGACY_PATHS = [
        'admin',
        'groups',
        'users',
        'blog',
        'blog_categories',
        'blog_posts',
        'configurations',
        'pages',
        'clients',
    ];

    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('modules')) {
            return;
        }

        $ids = DB::table('modules')->whereIn('path', self::LEGACY_PATHS)->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        if (DB::getSchemaBuilder()->hasTable('group_module')) {
            DB::table('group_module')->whereIn('module_id', $ids)->delete();
        }

        DB::table('modules')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Não recria módulos legados (rotas e UI já não existem).
    }
};
