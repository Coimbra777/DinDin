<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\Finance\TransactionApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_accepts_plain_array_without_request(): void
    {
        $user = User::factory()->create(['group_id' => 0]);
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);

        $service = app(TransactionApiService::class);
        $row = $service->create($user->id, [
            'title' => 'Compra unit',
            'amount' => 42.5,
            'type' => Transaction::TYPE_EXPENSE,
            'category_id' => $cat->id,
            'transaction_date' => now()->format('Y-m-d'),
            'description' => null,
            'installment_number' => null,
            'installment_of' => null,
        ]);

        $this->assertSame('Compra unit', $row['title']);
        $this->assertEquals(42.5, (float) $row['amount']);
        $this->assertDatabaseHas('finance_transactions', [
            'user_id' => $user->id,
            'title' => 'Compra unit',
        ]);
    }
}
