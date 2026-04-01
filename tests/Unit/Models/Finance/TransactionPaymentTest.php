<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Transaction::class)]
class TransactionPaymentTest extends TestCase
{
    public function test_is_overdue_when_pending_and_due_date_before_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00'));

        $t = new Transaction([
            'payment_status' => Transaction::STATUS_PENDING,
            'due_date' => '2026-04-01',
        ]);

        $this->assertTrue($t->isOverdue());
        $this->assertSame(Transaction::STATUS_OVERDUE, $t->effectivePaymentStatus());
    }

    public function test_not_overdue_when_paid_even_if_due_date_past(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00'));

        $t = new Transaction([
            'payment_status' => Transaction::STATUS_PAID,
            'due_date' => '2026-04-01',
        ]);

        $this->assertFalse($t->isOverdue());
        $this->assertSame(Transaction::STATUS_PAID, $t->effectivePaymentStatus());
    }

    public function test_pending_without_due_date_is_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 12:00:00'));

        $t = new Transaction([
            'payment_status' => Transaction::STATUS_PENDING,
            'due_date' => null,
        ]);

        $this->assertFalse($t->isOverdue());
        $this->assertSame(Transaction::STATUS_PENDING, $t->effectivePaymentStatus());
    }
}
