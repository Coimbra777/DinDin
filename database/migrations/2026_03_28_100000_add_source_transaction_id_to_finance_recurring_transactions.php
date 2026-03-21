<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'finance_recurring_transactions';

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'source_transaction_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('source_transaction_id')->nullable()->after('user_id');
            $blueprint->foreign('source_transaction_id')
                ->references('id')
                ->on('finance_transactions')
                ->nullOnDelete();
            // Nome curto: limite MySQL 64 caracteres para identificadores
            $blueprint->index(['user_id', 'source_transaction_id'], 'frt_user_src_txn_idx');
        });
    }

    public function down(): void
    {
        $table = 'finance_recurring_transactions';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'source_transaction_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropForeign(['source_transaction_id']);
            $blueprint->dropIndex('frt_user_src_txn_idx');
            $blueprint->dropColumn('source_transaction_id');
        });
    }
};
