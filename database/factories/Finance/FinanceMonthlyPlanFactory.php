<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\FinanceMonthlyPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceMonthlyPlan>
 */
class FinanceMonthlyPlanFactory extends Factory
{
    protected $model = FinanceMonthlyPlan::class;

    public function definition(): array
    {
        $d = Carbon::instance(fake()->dateTimeBetween('-6 months', 'now'))->startOfMonth();

        return [
            'user_id' => User::factory(),
            'year_month' => $d->format('Y-m'),
            'planned_expense' => round(fake()->randomFloat(2, 2000, 12000), 2),
            'planned_saving' => round(fake()->randomFloat(2, 200, 3500), 2),
        ];
    }

    public function forYearMonth(string $ym): static
    {
        return $this->state(fn () => ['year_month' => $ym]);
    }

    public function forUserId(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}
