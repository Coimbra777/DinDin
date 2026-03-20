<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class AlertsAndInsightsApiTest extends FinanceApiTestCase
{
    public function test_alerts_returns_json_structure(): void
    {
        $user = $this->financeUser();
        $month = now()->format('Y-m');

        $this->actingAs($user)
            ->getJson($this->financeApi('alerts').'?month='.$month)
            ->assertOk()
            ->assertJsonStructure(['month', 'alerts']);
    }

    public function test_insights_returns_categories_and_comparison(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id, 'name' => 'Alimentação']);
        Transaction::factory()->expense()->create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 200,
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $month = now()->format('Y-m');

        $this->actingAs($user)
            ->getJson($this->financeApi('insights').'?month='.$month)
            ->assertOk()
            ->assertJsonStructure([
                'month',
                'insights',
                'categorias',
                'comparacao_mes_anterior',
            ]);
    }
}
