<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove cartões de crédito: transações antigas com cartão passam a contar como despesas normais
     * (valores já estavam em amount; apenas removemos o vínculo e flags).
     */
    public function up(): void
    {
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'credit_card_id')) {
                    $table->dropForeign(['credit_card_id']);
                }
            });
            Schema::table('finance_transactions', function (Blueprint $table) {
                $cols = array_filter([
                    Schema::hasColumn('finance_transactions', 'credit_card_id') ? 'credit_card_id' : null,
                    Schema::hasColumn('finance_transactions', 'is_credit_card') ? 'is_credit_card' : null,
                ]);
                if ($cols !== []) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('finance_credit_cards');

        if (Schema::hasTable('saas_modules')) {
            $cardIds = DB::table('saas_modules')->where('slug', 'cards')->pluck('id');
            if ($cardIds->isNotEmpty() && Schema::hasTable('saas_module_user')) {
                DB::table('saas_module_user')->whereIn('saas_module_id', $cardIds)->delete();
            }
            DB::table('saas_modules')->where('slug', 'cards')->delete();
        }
    }

    public function down(): void
    {
        Schema::create('finance_credit_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('credit_limit', 15, 2)->comment('Limite total do cartão (R$)');
            $table->unsignedTinyInteger('closing_day')->comment('Dia de fechamento da fatura (1–31)');
            $table->unsignedTinyInteger('due_day')->comment('Dia de vencimento (1–31)');
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                $table->foreignId('credit_card_id')->nullable()->after('category_id')
                    ->constrained('finance_credit_cards')->nullOnDelete();
                $table->boolean('is_credit_card')->default(false)->after('credit_card_id');
            });
        }
    }
};
