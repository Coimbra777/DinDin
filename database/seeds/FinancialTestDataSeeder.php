<?php

declare(strict_types=1);

use App\Models\Finance\CreditCard;
use App\Models\Finance\FinanceGoal;
use App\Models\Finance\FinanceMonthlyPlan;
use App\Models\Finance\Transaction;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dados volumosos para desenvolvimento / demos (não ligar no DatabaseSeeder por defeito).
 *
 * php artisan db:seed --class=FinancialTestDataSeeder
 */
class FinancialTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $group = Group::query()->firstOrCreate(
            ['name' => 'Finance QA'],
        );
        $moduleId = DB::table('modules')->where('path', 'finance')->value('id');
        if (! $moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'Finanças',
                'father_path' => null,
                'path' => 'finance',
                'order' => 1,
                'father_order' => 0,
                'icon' => 'fa',
                'has_son' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if (! DB::table('group_module')->where(['group_id' => $group->id, 'module_id' => $moduleId])->exists()) {
            DB::table('group_module')->insert([
                'group_id' => $group->id,
                'module_id' => $moduleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $user = User::factory()->create([
            'name' => 'Usuário Finanças QA',
            'email' => 'finance-qa@example.test',
            'username' => 'finance_qa_'.uniqid(),
            'group_id' => $group->id,
        ]);

        CreditCard::factory()->count(2)->create(['user_id' => $user->id]);

        FinanceGoal::factory()->count(3)->create(['user_id' => $user->id]);

        $months = collect(range(0, 5))->map(fn ($i) => now()->copy()->subMonths($i)->format('Y-m'));
        foreach ($months as $ym) {
            FinanceMonthlyPlan::factory()->create([
                'user_id' => $user->id,
                'year_month' => $ym,
            ]);
        }

        Transaction::factory()->count(55)->create(['user_id' => $user->id]);

        $this->command?->info("Utilizador QA: {$user->email} (password: password)");
    }
}
