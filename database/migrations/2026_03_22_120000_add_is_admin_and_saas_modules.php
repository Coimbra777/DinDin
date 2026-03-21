<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        Schema::create('saas_modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('saas_module_user', function (Blueprint $table) {
            // users.id é increments() (INT UNSIGNED), não BIGINT — foreignId() quebraria a FK no MySQL.
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->foreignId('saas_module_id')->constrained('saas_modules')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'saas_module_id']);
        });

        $now = now();
        DB::table('saas_modules')->insert([
            'name' => 'Finanças',
            'slug' => 'finance',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bootstrap = env('SAAS_BOOTSTRAP_ADMIN_EMAIL');
        if (is_string($bootstrap) && $bootstrap !== '') {
            DB::table('users')->where('email', $bootstrap)->update(['is_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_module_user');
        Schema::dropIfExists('saas_modules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
