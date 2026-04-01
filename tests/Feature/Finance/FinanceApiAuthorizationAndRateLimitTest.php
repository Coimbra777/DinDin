<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\SaasModule;
use App\Models\User;

class FinanceApiAuthorizationAndRateLimitTest extends FinanceApiTestCase
{
    public function test_reports_trend_returns_403_json_without_reports_module(): void
    {
        $user = User::factory()->create(['group_id' => 0]);
        $financeId = SaasModule::query()->where('slug', 'finance')->value('id');
        $this->assertNotNull($financeId);
        $user->saasModules()->sync([(int) $financeId]);

        $this->actingAs($user)
            ->getJson($this->financeApi('reports/trend'))
            ->assertForbidden()
            ->assertJson(['message' => 'Módulo não autorizado']);
    }

    public function test_finance_api_mutation_rate_limit_returns_429(): void
    {
        config(['finance.api_mutations_per_minute' => 3]);
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $makePayload = static fn (int $i): array => [
            'title' => 'RL '.$i,
            'amount' => 1,
            'type' => Transaction::TYPE_EXPENSE,
            'category_id' => $cat->id,
            'transaction_date' => now()->format('Y-m-d'),
        ];
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)
                ->postJson($this->financeApi('transactions'), $makePayload($i))
                ->assertCreated();
        }

        $this->actingAs($user)
            ->postJson($this->financeApi('transactions'), $makePayload(99))
            ->assertStatus(429);
    }
}
