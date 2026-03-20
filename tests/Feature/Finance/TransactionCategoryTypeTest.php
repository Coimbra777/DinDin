<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class TransactionCategoryTypeTest extends FinanceApiTestCase
{
    public function test_expense_transaction_rejects_income_category(): void
    {
        $user = $this->financeUser();
        $incomeCat = Category::factory()->income()->forUserId($user->id)->create(['name' => 'Salário teste']);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Compra',
                'amount' => 10.5,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $incomeCat->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_income_transaction_rejects_expense_category(): void
    {
        $user = $this->financeUser();
        $expCat = Category::factory()->expense()->forUserId($user->id)->create(['name' => 'Alimentação teste']);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Pagamento',
                'amount' => 100,
                'type' => Transaction::TYPE_INCOME,
                'category_id' => $expCat->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_income_transaction_accepts_income_category(): void
    {
        $user = $this->financeUser();
        $incomeCat = Category::factory()->income()->forUserId($user->id)->create(['name' => 'Freelance teste']);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Projeto X',
                'amount' => 2500,
                'type' => Transaction::TYPE_INCOME,
                'category_id' => $incomeCat->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('category_id', $incomeCat->id);

        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'type' => Transaction::TYPE_INCOME,
            'category_id' => $incomeCat->id,
        ]);
    }

    public function test_expense_transaction_accepts_expense_category(): void
    {
        $user = $this->financeUser();
        $expCat = Category::factory()->expense()->forUserId($user->id)->create(['name' => 'Transporte teste']);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Uber',
                'amount' => 22,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $expCat->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('category_id', $expCat->id);
    }
}
