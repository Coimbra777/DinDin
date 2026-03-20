<?php

declare(strict_types=1);

use App\Models\Finance\Category;
use App\Models\Finance\CreditCard;
use App\Models\Finance\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Cartões e despesas no cartão para test@test.com.
 */
class CreditCardSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@test.com')->first();
        if ($user === null) {
            $this->command?->warn('CreditCardSeeder: utilizador test@test.com não encontrado.');

            return;
        }

        $expenseIds = Category::query()->forUser($user->id)->expense()->pluck('id');
        if ($expenseIds->isEmpty()) {
            $this->command?->warn('CreditCardSeeder: sem categorias de despesa.');

            return;
        }

        $cards = collect([
            CreditCard::query()->create([
                'user_id' => $user->id,
                'name' => 'Nubank '.fake()->numerify('####'),
                'credit_limit' => number_format(fake()->randomFloat(2, 3000, 12000), 2, '.', ''),
                'closing_day' => 10,
                'due_day' => 17,
            ]),
            CreditCard::query()->create([
                'user_id' => $user->id,
                'name' => 'Itaú '.fake()->numerify('####'),
                'credit_limit' => number_format(fake()->randomFloat(2, 5000, 20000), 2, '.', ''),
                'closing_day' => 5,
                'due_day' => 12,
            ]),
        ]);

        foreach (range(1, 12) as $_) {
            $card = $cards->random();
            $base = Carbon::now()->subMonths(fake()->numberBetween(0, 5))->startOfMonth();
            $day = fake()->numberBetween(1, min(28, $base->daysInMonth));

            Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => (int) $expenseIds->random(),
                'credit_card_id' => $card->id,
                'title' => fake()->randomElement(['Parcela', 'Compra online', 'Supermercado', 'Assinatura', 'Restaurante']),
                'amount' => number_format(fake()->randomFloat(2, 15, 2500), 2, '.', ''),
                'type' => Transaction::TYPE_EXPENSE,
                'transaction_date' => $base->copy()->day($day)->format('Y-m-d'),
                'description' => fake()->boolean(30) ? implode(' ', fake()->words(fake()->numberBetween(3, 8))) : null,
                'installment_number' => null,
                'installment_of' => null,
                'is_credit_card' => true,
            ]);
        }

        $this->command?->info('CreditCardSeeder: 2 cartões e 12 despesas no cartão para test@test.com.');
    }
}
