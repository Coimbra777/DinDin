<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Finance\Transaction;
use Tests\TestCase;

class TransactionMonthTest extends TestCase
{
    public function test_normalize_month_accepts_yyyy_mm(): void
    {
        $this->assertSame('2024-06', Transaction::normalizeMonth('2024-06'));
    }

    public function test_month_to_date_range(): void
    {
        [$start, $end] = Transaction::monthToDateRange('2024-02');
        $this->assertSame('2024-02-01', $start);
        $this->assertSame('2024-02-29', $end);
    }
}
