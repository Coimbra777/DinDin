<?php

declare(strict_types=1);

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Transações de demonstração para o utilizador test@test.com (após FinanceCategorySeeder).
 */
class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@test.com')->first();
        if ($user === null) {
            $this->command?->warn('FinanceSeeder: utilizador test@test.com não encontrado.');

            return;
        }

        $incomeIds = Category::query()->forUser($user->id)->income()->pluck('id');
        $expenseIds = Category::query()->forUser($user->id)->expense()->pluck('id');

        if ($incomeIds->isEmpty() || $expenseIds->isEmpty()) {
            $this->command?->warn('FinanceSeeder: sem categorias para o utilizador; corra FinanceCategorySeeder.');

            return;
        }

        $titlesIncome = ['Salário', 'Freelance', 'Rendimento extra', 'Reembolso', 'Venda'];
        $titlesExpense = ['Mercado', 'Combustível', 'Farmácia', 'Restaurante', 'Assinatura', 'Manutenção'];

        for ($i = 0; $i < 52; $i++) {
            $type = fake()->boolean(35)
                ? Transaction::TYPE_INCOME
                : Transaction::TYPE_EXPENSE;

            $categoryId = $type === Transaction::TYPE_INCOME
                ? (int) $incomeIds->random()
                : (int) $expenseIds->random();

            $base = Carbon::now()->subMonths(fake()->numberBetween(0, 5))->startOfMonth();
            $day = fake()->numberBetween(1, min(28, $base->daysInMonth));
            $transactionDate = $base->copy()->day($day);

            Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'credit_card_id' => null,
                'title' => fake()->randomElement($type === Transaction::TYPE_INCOME ? $titlesIncome : $titlesExpense),
                'amount' => number_format(fake()->randomFloat(2, 10, 5000), 2, '.', ''),
                'type' => $type,
                'transaction_date' => $transactionDate->format('Y-m-d'),
                'description' => fake()->boolean(35) ? implode(' ', fake()->words(fake()->numberBetween(4, 10))) : null,
                'installment_number' => null,
                'installment_of' => null,
                'is_credit_card' => false,
            ]);
        }

        $this->command?->info('FinanceSeeder: 52 transações (caixa) criadas para test@test.com.');
    }
}
