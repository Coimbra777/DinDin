<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->string('type', 20)->comment('income | expense');
            $table->foreignId('category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->string('frequency', 20)->comment('monthly | weekly');
            $table->unsignedTinyInteger('day_of_month')->nullable()->comment('1–31 for monthly');
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('1–7 ISO (Mon=1) for weekly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_recurring_transactions');
    }
};
