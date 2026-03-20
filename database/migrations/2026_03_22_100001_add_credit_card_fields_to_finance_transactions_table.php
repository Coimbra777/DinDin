<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->foreignId('credit_card_id')->nullable()->after('category_id')
                ->constrained('finance_credit_cards')->nullOnDelete();
            $table->boolean('is_credit_card')->default(false)->after('credit_card_id')
                ->comment('Se true, despesa entra na fatura do cartão e não no caixa imediato');
        });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropForeign(['credit_card_id']);
            $table->dropColumn(['credit_card_id', 'is_credit_card']);
        });
    }
};
