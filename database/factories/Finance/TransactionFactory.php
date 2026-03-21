<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\CreditCard;
use App\Models\Finance\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement([Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]);
        $amount = fake()->randomFloat(2, 10, 5000);

        $date = Carbon::instance(fake()->dateTimeBetween('-6 months', 'now'));

        return [
            'user_id' => User::factory(),
            'parent_transaction_id' => null,
            'recurring_transaction_id' => null,
            'credit_card_id' => null,
            'title' => fake()->randomElement(['Mercado', 'Uber', 'Aluguel', 'Farmácia', 'Restaurante', 'Salário', 'Bônus']),
            'amount' => $amount,
            'type' => $type,
            'category_id' => function (array $attributes) {
                $userId = $attributes['user_id'];
                if ($userId instanceof User) {
                    $userId = $userId->id;
                }

                $existing = Category::query()
                    ->forUser((int) $userId)
                    ->ofType($attributes['type'])
                    ->inRandomOrder()
                    ->first();

                if ($existing !== null) {
                    return $existing->id;
                }

                return Category::factory()->create([
                    'user_id' => $userId,
                    'type' => $attributes['type'],
                ])->id;
            },
            'transaction_date' => $date->format('Y-m-d'),
            'description' => fake()->boolean(40) ? implode(' ', fake()->words(fake()->numberBetween(3, 8))) : null,
            'installment_number' => null,
            'installment_of' => null,
            'is_credit_card' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => Transaction::TYPE_INCOME,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'is_credit_card' => false,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => fake()->randomFloat(2, 10, 5000),
            'is_credit_card' => false,
        ]);
    }

    public function forMonth(string $yearMonth): static
    {
        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $day = fake()->numberBetween(1, min(28, $start->daysInMonth));

        return $this->state(fn () => [
            'transaction_date' => $start->copy()->day($day)->format('Y-m-d'),
        ]);
    }

    public function creditCard(CreditCard $card): static
    {
        return $this->state(fn () => [
            'user_id' => $card->user_id,
            'credit_card_id' => $card->id,
            'type' => Transaction::TYPE_EXPENSE,
            'is_credit_card' => true,
            'amount' => fake()->randomFloat(2, 15, 2500),
        ]);
    }

    public function forUserId(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }
}
