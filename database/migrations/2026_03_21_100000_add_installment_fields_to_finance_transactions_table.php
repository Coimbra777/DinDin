<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->unsignedSmallInteger('installment_number')->nullable()->after('description')
                ->comment('Parcela atual (ex.: 3 em 3/12)');
            $table->unsignedSmallInteger('installment_of')->nullable()->after('installment_number')
                ->comment('Total de parcelas (ex.: 12)');
        });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropColumn(['installment_number', 'installment_of']);
        });
    }
};
