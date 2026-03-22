<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\FinanceGoal;

class FinanceGoalApiTest extends FinanceApiTestCase
{
    public function test_index_returns_user_goals_only(): void
    {
        $user = $this->financeUser();
        $other = $this->financeUser();
        FinanceGoal::factory()->count(2)->create(['user_id' => $user->id]);
        FinanceGoal::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->getJson($this->financeApi('goals'))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_goal(): void
    {
        $user = $this->financeUser();

        $response = $this->actingAs($user)
            ->postJson($this->financeApi('goals'), [
                'title' => 'Reserva',
                'description' => 'Meta de teste',
                'target_amount' => 5000,
                'current_amount' => 500,
                'deadline' => now()->addYear()->format('Y-m-d'),
            ])
            ->assertStatus(201)
            ->assertJsonPath('title', 'Reserva');

        self::assertEqualsWithDelta(5000.0, (float) $response->json('target_amount'), 0.00001);

        $this->assertDatabaseHas('finance_goals', [
            'user_id' => $user->id,
            'title' => 'Reserva',
        ]);
    }
}
