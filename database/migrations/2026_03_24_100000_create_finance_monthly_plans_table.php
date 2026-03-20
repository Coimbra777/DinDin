<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_monthly_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('year_month', 7)->comment('YYYY-MM');
            $table->decimal('planned_expense', 15, 2)->default(0);
            $table->decimal('planned_saving', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year_month']);
            $table->index(['user_id', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_monthly_plans');
    }
};
