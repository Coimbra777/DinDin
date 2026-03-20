<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline');
            $table->foreignId('income_category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_goals');
    }
};
