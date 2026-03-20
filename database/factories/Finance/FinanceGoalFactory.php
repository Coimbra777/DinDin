<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\FinanceGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceGoal>
 */
class FinanceGoalFactory extends Factory
{
    protected $model = FinanceGoal::class;

    public function definition(): array
    {
        $target = fake()->randomFloat(2, 2000, 40000);
        $current = fake()->randomFloat(2, 0, $target * 0.85);

        return [
            'user_id' => User::factory(),
            'title' => fake()->randomElement(['Reserva de emergência', 'Viagem', 'Entrada do apartamento', 'Carro novo', 'Curso', 'Reforma']),
            'description' => fake()->boolean(50) ? implode(' ', fake()->words(fake()->numberBetween(3, 8))) : null,
            'target_amount' => round($target, 2),
            'current_amount' => round($current, 2),
            'deadline' => Carbon::instance(fake()->dateTimeBetween('-1 month', '+18 months'))->format('Y-m-d'),
            'income_category_id' => null,
        ];
    }

    public function forUserId(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}
