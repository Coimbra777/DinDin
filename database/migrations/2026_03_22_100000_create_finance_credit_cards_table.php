<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_cards');
    }
};
