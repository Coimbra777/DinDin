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
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_transactions_index_respects_per_page_and_page(): void
    {
        $user = $this->financeUser();
        Transaction::factory()->count(25)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson($this->financeApi('transactions').'?per_page=10&page=2')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(10, 'data');
    }

    public function test_store_transaction_requires_category_id(): void
    {
        $user = $this->financeUser();

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Sem categoria',
                'amount' => 10,
                'type' => Transaction::TYPE_EXPENSE,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
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

    public function test_duplicate_transaction_creates_copies_in_following_months(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $source = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'title' => 'Aluguel base',
            'amount' => 1500,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-01-15',
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions/'.$source->id.'/duplicate'), [
                'months' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('count', 2);

        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'parent_transaction_id' => $source->id,
            'transaction_date' => '2026-02-15',
            'amount' => '1500.00',
        ]);
        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'parent_transaction_id' => $source->id,
            'transaction_date' => '2026-03-15',
        ]);
    }

    public function test_duplicate_transaction_rejects_second_copy_in_same_target_month(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $source = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'transaction_date' => '2026-01-10',
        ]);
        Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'parent_transaction_id' => $source->id,
            'transaction_date' => '2026-02-10',
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions/'.$source->id.'/duplicate'), [
                'months' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['months']);
    }

    public function test_user_cannot_duplicate_other_users_transaction(): void
    {
        $user = $this->financeUser();
        $other = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $other->id]);
        $source = Transaction::factory()->forUserId((int) $other->id)->create([
            'category_id' => $cat->id,
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions/'.$source->id.'/duplicate'), [
                'months' => 1,
            ])
            ->assertNotFound();
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
