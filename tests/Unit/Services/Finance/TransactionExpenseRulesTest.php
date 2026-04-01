<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionExpenseRules;
use Tests\TestCase;

class TransactionExpenseRulesTest extends TestCase
{
    public function test_strips_recurring_for_income(): void
    {
        $out = TransactionExpenseRules::normalizeForPersistence([
            'type' => Transaction::TYPE_INCOME,
            'is_recurring' => true,
            'recurrence_day' => 15,
            'transaction_date' => '2026-04-01',
        ]);

        $this->assertFalse($out['is_recurring']);
        $this->assertNull($out['recurrence_day']);
    }

    public function test_derives_recurrence_day_from_transaction_date(): void
    {
        $out = TransactionExpenseRules::normalizeForPersistence([
            'type' => Transaction::TYPE_EXPENSE,
            'is_recurring' => true,
            'transaction_date' => '2026-04-17',
        ]);

        $this->assertTrue($out['is_recurring']);
        $this->assertSame(17, $out['recurrence_day']);
    }
}
