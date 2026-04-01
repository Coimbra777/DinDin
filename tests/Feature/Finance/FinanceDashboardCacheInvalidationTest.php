<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class FinanceDashboardCacheInvalidationTest extends FinanceApiTestCase
{
    public function test_dashboard_totals_refresh_after_transaction_create(): void
    {
        $user = $this->financeUser();
        $month = now()->format('Y-m');
        $catIn = Category::factory()->income()->create(['user_id' => $user->id]);

        $before = $this->actingAs($user)
            ->getJson($this->financeApi('dashboard').'?month='.$month)
            ->assertOk()
            ->json('receitas_mes');

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), [
                'title' => 'Entrada cache test',
                'amount' => 75.25,
                'type' => Transaction::TYPE_INCOME,
                'category_id' => $catIn->id,
                'transaction_date' => now()->format('Y-m-d'),
            ])
            ->assertCreated();

        $after = $this->actingAs($user)
            ->getJson($this->financeApi('dashboard').'?month='.$month)
            ->assertOk()
            ->json('receitas_mes');

        $this->assertSame(0.0, (float) $before);
        $this->assertSame(75.25, (float) $after);
    }
}
