<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\FinanceGoal;
use App\Models\Finance\Transaction;
use Carbon\Carbon;

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

    public function test_alerts_each_item_has_actionable_fields(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-06-15 12:00:00', config('app.timezone')));
        try {
            $user = $this->financeUser();
            $goal = FinanceGoal::factory()->forUserId($user->id)->create([
                'title' => 'Reserva',
                'target_amount' => 10000,
                'current_amount' => 200,
                'deadline' => '2025-08-01',
                'income_category_id' => null,
            ]);
            $goal->forceFill([
                'created_at' => Carbon::parse('2025-05-01 08:00:00', config('app.timezone')),
            ])->save();

            $response = $this->actingAs($user)
                ->getJson($this->financeApi('alerts').'?month=2025-06')
                ->assertOk()
                ->assertJsonStructure([
                    'month',
                    'alerts' => [
                        '*' => ['type', 'severity', 'title', 'message', 'action_hint'],
                    ],
                ]);

            $types = collect($response->json('alerts'))->pluck('type')->all();
            self::assertContains('goal_risk', $types);
            $goalAlert = collect($response->json('alerts'))->firstWhere('type', 'goal_risk');
            self::assertSame($goal->id, (int) ($goalAlert['meta']['goal_id'] ?? 0));
        } finally {
            Carbon::setTestNow();
        }
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
