<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Esta migração tinha data anterior à criação da tabela finance_recurring_transactions
 * e falhava ou era ignorada. As colunas são aplicadas em 2026_03_27_120002.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
