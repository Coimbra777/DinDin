<?php

declare(strict_types=1);

use App\Models\Finance\FinanceMonthlyPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Planejamento mensal (últimos 3 meses civis) para test@test.com.
 */
class PlanningSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@test.com')->first();
        if ($user === null) {
            $this->command?->warn('PlanningSeeder: utilizador test@test.com não encontrado.');

            return;
        }

        for ($i = 0; $i < 3; $i++) {
            $ym = now()->subMonths($i)->format('Y-m');
            FinanceMonthlyPlan::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'year_month' => $ym,
                ],
                [
                    'planned_expense' => number_format(fake()->randomFloat(2, 2500, 9500), 2, '.', ''),
                    'planned_saving' => number_format(fake()->randomFloat(2, 300, 4000), 2, '.', ''),
                ]
            );
        }

        $this->command?->info('PlanningSeeder: 3 planejamentos mensais para test@test.com.');
    }
}
