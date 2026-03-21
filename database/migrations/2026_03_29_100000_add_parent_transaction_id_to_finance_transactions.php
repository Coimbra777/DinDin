<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'finance_transactions';

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'parent_transaction_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('parent_transaction_id')->nullable()->after('user_id');
            $blueprint->foreign('parent_transaction_id')
                ->references('id')
                ->on('finance_transactions')
                ->nullOnDelete();
            $blueprint->index(['user_id', 'parent_transaction_id'], 'ft_user_parent_idx');
        });
    }

    public function down(): void
    {
        $table = 'finance_transactions';

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'parent_transaction_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropForeign(['parent_transaction_id']);
            $blueprint->dropIndex('ft_user_parent_idx');
            $blueprint->dropColumn('parent_transaction_id');
        });
    }
};
