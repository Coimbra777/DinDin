<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditCard>
 */
class CreditCardFactory extends Factory
{
    protected $model = CreditCard::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Nubank', 'Itaú', 'C6', 'Inter', 'Amex']).' '.fake()->numerify('####'),
            'credit_limit' => round(fake()->randomFloat(2, 2000, 25000), 2),
            'closing_day' => fake()->numberBetween(1, 28),
            'due_day' => fake()->numberBetween(1, 28),
        ];
    }

    public function forUserId(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}
