<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

class ProjectionFinanceApiTest extends FinanceApiTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_projection_uses_only_real_transactions_per_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-15 12:00:00', config('app.timezone')));

        $user = $this->financeUser();
        $catIn = Category::factory()->forUserId($user->id)->income()->create();
        $catOut = Category::factory()->forUserId($user->id)->expense()->create();

        Transaction::factory()->forUserId($user->id)->income()->create([
            'user_id' => $user->id,
            'category_id' => $catIn->id,
            'amount' => 500,
            'transaction_date' => '2026-05-10',
            'title' => 'Extra maio',
        ]);
        Transaction::factory()->forUserId($user->id)->expense()->create([
            'user_id' => $user->id,
            'category_id' => $catOut->id,
            'amount' => 100,
            'transaction_date' => '2026-05-20',
            'title' => 'Conta maio',
        ]);

        $res = $this->actingAs($user)->getJson($this->financeApi('projection'));
        $res->assertOk();
        $res->assertJsonStructure(['months']);

        $months = $res->json('months');
        $this->assertCount(12, $months);

        $may = collect($months)->firstWhere('month', '2026-05');
        $this->assertNotNull($may);
        $this->assertSame(500.0, (float) $may['income']);
        $this->assertSame(100.0, (float) $may['expense']);

        $april = collect($months)->firstWhere('month', '2026-04');
        $this->assertNotNull($april);
        $this->assertSame(0.0, (float) $april['income']);
        $this->assertSame(0.0, (float) $april['expense']);
    }

    public function test_projection_month_totals_match_transactions_index_month_filter(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-10', config('app.timezone')));

        $user = $this->financeUser();
        $catIn = Category::factory()->forUserId($user->id)->income()->create();
        $catOut = Category::factory()->forUserId($user->id)->expense()->create();

        Transaction::factory()->forUserId($user->id)->income()->create([
            'user_id' => $user->id,
            'category_id' => $catIn->id,
            'amount' => 200,
            'transaction_date' => '2026-04-05',
        ]);
        Transaction::factory()->forUserId($user->id)->expense()->create([
            'user_id' => $user->id,
            'category_id' => $catOut->id,
            'amount' => 50,
            'transaction_date' => '2026-04-15',
        ]);

        $proj = $this->actingAs($user)->getJson($this->financeApi('projection'))->json('months');
        $aprilProj = collect($proj)->firstWhere('month', '2026-04');

        $tx = $this->actingAs($user)
            ->getJson($this->financeApi('transactions').'?month=2026-04&per_page=100')
            ->assertOk();

        $sumIncome = 0.0;
        $sumExpense = 0.0;
        foreach ($tx->json('data') as $row) {
            if (($row['type'] ?? '') === Transaction::TYPE_INCOME) {
                $sumIncome += (float) ($row['amount'] ?? 0);
            } elseif (($row['type'] ?? '') === Transaction::TYPE_EXPENSE) {
                $sumExpense += (float) ($row['amount'] ?? 0);
            }
        }

        $this->assertSame($sumIncome, (float) $aprilProj['income']);
        $this->assertSame($sumExpense, (float) $aprilProj['expense']);
    }
}
