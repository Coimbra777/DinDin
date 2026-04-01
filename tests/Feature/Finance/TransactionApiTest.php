<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

class TransactionApiTest extends FinanceApiTestCase
{
    public function test_guest_cannot_access_transactions(): void
    {
        $this->getJson($this->financeApi('transactions'))
            ->assertUnauthorized();
    }

    public function test_authenticated_user_lists_transactions(): void
    {
        $user = $this->financeUser();
        $today = now()->format('Y-m-d');
        Transaction::factory()->count(2)->create([
            'user_id' => $user->id,
            'transaction_date' => $today,
        ]);

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
        $month = now()->format('Y-m');
        $start = now()->copy()->startOfMonth();
        for ($i = 0; $i < 25; $i++) {
            Transaction::factory()->create([
                'user_id' => $user->id,
                'transaction_date' => $start->copy()->addDays($i % 28)->format('Y-m-d'),
            ]);
        }

        $this->actingAs($user)
            ->getJson($this->financeApi('transactions').'?month='.$month.'&per_page=10&page=2')
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

    public function test_summary_saldo_previsto_acumulado_equals_saldo_acumulado_caixa(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $catIn = Category::factory()->income()->create(['user_id' => $user->id]);

        Transaction::factory()->forUserId($user->id)->create([
            'category_id' => $catIn->id,
            'type' => Transaction::TYPE_INCOME,
            'amount' => 5000,
            'transaction_date' => '2026-01-10',
        ]);
        Transaction::factory()->forUserId($user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 2000,
            'transaction_date' => '2026-02-05',
        ]);
        Transaction::factory()->forUserId($user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 500,
            'transaction_date' => '2026-03-12',
        ]);

        $summary = $this->actingAs($user)
            ->getJson($this->financeApi('summary').'?month=2026-03')
            ->assertOk()
            ->json();

        $acum = (float) $summary['saldo_acumulado_ate_mes'];
        $prev = (float) $summary['saldo_previsto_acumulado_fim_mes'];
        self::assertSame(2500.0, $acum);
        self::assertSame($acum, $prev);
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

    public function test_store_transaction_persists_due_date_and_pending_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 10:00:00'));
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Conta luz',
                'amount' => 50,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $cat->id,
                'transaction_date' => '2026-04-01',
                'due_date' => '2026-04-10',
                'payment_status' => Transaction::STATUS_PENDING,
            ])
            ->assertCreated()
            ->assertJsonPath('due_date', '2026-04-10')
            ->assertJsonPath('payment_status', 'pending')
            ->assertJsonPath('is_overdue', false);

        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'title' => 'Conta luz',
            'due_date' => '2026-04-10',
            'payment_status' => Transaction::STATUS_PENDING,
        ]);
    }

    public function test_api_exposes_overdue_when_due_date_passed_and_still_pending(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-20 10:00:00'));
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Boleto atraso',
                'amount' => 120,
                'type' => Transaction::TYPE_EXPENSE,
                'category_id' => $cat->id,
                'transaction_date' => '2026-04-01',
                'due_date' => '2026-04-05',
                'payment_status' => Transaction::STATUS_PENDING,
            ])
            ->assertCreated()
            ->assertJsonPath('payment_status', Transaction::STATUS_OVERDUE)
            ->assertJsonPath('is_overdue', true);
    }

    public function test_mark_as_paid_sets_status_paid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00:00'));
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $tx = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'transaction_date' => '2026-04-01',
            'payment_status' => Transaction::STATUS_PENDING,
            'due_date' => '2026-04-01',
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions/'.$tx->id.'/mark-as-paid'))
            ->assertOk()
            ->assertJsonPath('payment_status', Transaction::STATUS_PAID)
            ->assertJsonPath('is_overdue', false);

        $this->assertDatabaseHas('finance_transactions', [
            'id' => $tx->id,
            'payment_status' => Transaction::STATUS_PAID,
        ]);
    }

    public function test_mark_as_paid_rejects_income(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->income()->create(['user_id' => $user->id]);
        $tx = Transaction::factory()->forUserId((int) $user->id)->create([
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_INCOME,
            'transaction_date' => '2026-04-01',
        ]);

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions/'.$tx->id.'/mark-as-paid'))
            ->assertStatus(422);
    }
}

