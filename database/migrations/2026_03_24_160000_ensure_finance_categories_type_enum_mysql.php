<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Garante valores válidos e, em MySQL, coluna ENUM nativa.
 * SQLite mantém VARCHAR (Laravel); a aplicação valida income|expense.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('finance_categories')
            ->where(function ($q) {
                $q->whereNull('type')->orWhereNotIn('type', ['income', 'expense']);
            })
            ->update(['type' => 'expense']);

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE finance_categories MODIFY COLUMN `type` ENUM('income','expense') NOT NULL DEFAULT 'expense'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE finance_categories MODIFY COLUMN `type` VARCHAR(20) NOT NULL DEFAULT 'expense'");
    }
};
