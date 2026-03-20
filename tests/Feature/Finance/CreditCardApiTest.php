<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\CreditCard;

class CreditCardApiTest extends FinanceApiTestCase
{
    public function test_index_lists_cards(): void
    {
        $user = $this->financeUser();
        CreditCard::factory()->count(2)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson($this->financeApi('credit-cards'))
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }

    public function test_store_credit_card(): void
    {
        $user = $this->financeUser();

        $this->actingAs($user)
            ->postJson($this->financeApi('credit-cards'), [
                'name' => 'Cartão teste',
                'limit' => 8000,
                'closing_day' => 10,
                'due_day' => 17,
            ])
            ->assertStatus(201)
            ->assertJsonPath('name', 'Cartão teste');

        $this->assertDatabaseHas('finance_credit_cards', [
            'user_id' => $user->id,
            'name' => 'Cartão teste',
        ]);
    }
}
