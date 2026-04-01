<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;

class CategoryTypeLockApiTest extends FinanceApiTestCase
{
    public function test_index_includes_has_transactions(): void
    {
        $user = $this->financeUser();
        $empty = Category::factory()->expense()->create(['user_id' => $user->id]);
        $used = Category::factory()->expense()->create(['user_id' => $user->id]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $used->id,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $res = $this->actingAs($user)
            ->getJson($this->financeApi('categories'))
            ->assertOk();

        $byId = collect($res->json('data'))->keyBy('id');
        $this->assertFalse($byId[(string) $empty->id]['has_transactions']);
        $this->assertTrue($byId[(string) $used->id]['has_transactions']);
    }

    public function test_cannot_change_type_when_category_has_transactions(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $this->actingAs($user)
            ->putJson($this->financeApi('categories/'.$cat->id), [
                'name' => $cat->name,
                'type' => 'income',
                'color' => $cat->color,
                'group' => null,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_can_change_name_when_category_has_transactions(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id, 'name' => 'Antigo']);
        Transaction::factory()->create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
        ]);

        $this->actingAs($user)
            ->putJson($this->financeApi('categories/'.$cat->id), [
                'name' => 'Novo nome',
                'type' => 'expense',
                'color' => $cat->color,
                'group' => $cat->group,
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Novo nome')
            ->assertJsonPath('type', 'expense')
            ->assertJsonPath('has_transactions', true);
    }

    public function test_can_change_type_when_category_has_no_transactions(): void
    {
        $user = $this->financeUser();
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->putJson($this->financeApi('categories/'.$cat->id), [
                'name' => $cat->name,
                'type' => 'income',
                'color' => $cat->color,
                'group' => null,
            ])
            ->assertOk()
            ->assertJsonPath('type', 'income')
            ->assertJsonPath('has_transactions', false);
    }
}
