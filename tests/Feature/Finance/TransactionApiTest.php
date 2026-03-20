<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class TransactionApiTest extends FinanceApiTestCase
{
    public function test_guest_cannot_access_transactions(): void
    {
        $this->getJson($this->financeApi('transactions'))
            ->assertStatus(302);
    }

    public function test_authenticated_user_lists_transactions(): void
    {
        $user = $this->financeUser();
        Transaction::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson($this->financeApi('transactions'))
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }

    public function test_store_transaction_persists_and_summary_reflects_expense(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $month = now()->format('Y-m');
        $date = now()->format('Y-m-d');

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Supermercado teste',
                'amount' => 100.5,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $cat->id,
                'transaction_date' => $date,
            ])
            ->assertCreated()
            ->assertJsonPath('title', 'Supermercado teste')
            ->assertJsonPath('amount', 100.5);

        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'title' => 'Supermercado teste',
            'amount' => '100.50',
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $summary = $this->actingAs($user)
            ->getJson($this->financeApi('summary').'?month='.$month)
            ->assertOk()
            ->json();

        $this->assertEquals(100.5, (float) $summary['expense_month']);
    }

    public function test_user_cannot_create_transaction_for_other_users_category(): void
    {
        $user = $this->financeUser();
        $other = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Fraude',
                'amount' => 10,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $cat->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(422);
    }
}
