<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class RecurringTransactionGenerateTest extends FinanceApiTestCase
{
    public function test_generate_creates_child_with_pending_payment(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $template = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'title' => 'Assinatura',
            'amount' => 49.9,
            'transaction_date' => '2026-03-10',
            'is_recurring' => true,
            'recurrence_day' => 10,
            'parent_transaction_id' => null,
            'payment_status' => Transaction::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('recurring/generate'), ['month' => '2026-04'])
            ->assertOk()
            ->assertJsonPath('month', '2026-04')
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('skipped', 0);

        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'parent_transaction_id' => $template->id,
            'transaction_date' => '2026-04-10',
            'payment_status' => Transaction::STATUS_PENDING,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $child = Transaction::query()
            ->where('parent_transaction_id', $template->id)
            ->where('transaction_date', '2026-04-10')
            ->first();
        $this->assertNotNull($child);
        $this->assertFalse((bool) $child->is_recurring);
        $this->assertNull($child->recurrence_day);
    }

    public function test_generate_skips_month_of_template(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-03-15',
            'is_recurring' => true,
            'recurrence_day' => 15,
            'parent_transaction_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('recurring/generate'), ['month' => '2026-03'])
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('skipped', 1);
    }

    public function test_generate_skips_when_child_already_in_month(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $template = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-01-10',
            'is_recurring' => true,
            'recurrence_day' => 10,
            'parent_transaction_id' => null,
        ]);
        Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-04-10',
            'parent_transaction_id' => $template->id,
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('recurring/generate'), ['month' => '2026-04'])
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('skipped', 1);

        $this->assertSame(1, Transaction::query()->where('parent_transaction_id', $template->id)->count());
    }

    public function test_generate_does_not_use_other_users_templates(): void
    {
        $user = $this->financeUser();
        $other = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $other->id]);
        Transaction::factory()->forUserId((int) $other->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-03-05',
            'is_recurring' => true,
            'recurrence_day' => 5,
            'parent_transaction_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('recurring/generate'), ['month' => '2026-04'])
            ->assertOk()
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('skipped', 0);
    }

    public function test_store_income_cannot_be_recurring(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->income()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Salário',
                'amount' => 100,
                'type' => Transaction::TYPE_INCOME,
                'category_id' => $cat->id,
                'transaction_date' => '2026-04-01',
                'is_recurring' => true,
                'recurrence_day' => 5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['is_recurring', 'recurrence_day']);
    }

}
