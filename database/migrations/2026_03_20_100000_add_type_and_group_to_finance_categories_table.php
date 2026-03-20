<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_categories', function (Blueprint $table) {
            $table->string('type', 20)->default('expense')->after('name')->comment('income | expense');
            $table->string('group', 32)->nullable()->after('type')->comment('fixa | variavel | financeira (despesas)');
        });
    }

    public function down(): void
    {
        Schema::table('finance_categories', function (Blueprint $table) {
            $table->dropColumn(['type', 'group']);
        });
    }
};
