<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\FinancialSummaryService;
use Tests\TestCase;

class FinancialSummaryMonthTest extends TestCase
{
    public function test_normalize_month_accepts_yyyy_mm(): void
    {
        $s = app(FinancialSummaryService::class);
        $this->assertSame('2024-06', $s->normalizeMonth('2024-06'));
    }

    public function test_month_to_date_range(): void
    {
        $s = app(FinancialSummaryService::class);
        [$start, $end] = $s->monthToDateRange('2024-02');
        $this->assertSame('2024-02-01', $start);
        $this->assertSame('2024-02-29', $end);
    }
}
