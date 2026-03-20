<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Finance\CreditInstallmentSimulatorService;
use PHPUnit\Framework\TestCase;

class CreditInstallmentSimulatorServiceTest extends TestCase
{
    public function test_installments_without_interest(): void
    {
        $service = new CreditInstallmentSimulatorService;
        $result = $service->simulate(1200.0, 12, 0.0);

        $this->assertSame(12, $result['installments']);
        $this->assertEquals(100.0, $result['installment_value']);
        $this->assertEquals(1200.0, $result['total_repayment']);
    }

    public function test_linear_interest_on_total(): void
    {
        $service = new CreditInstallmentSimulatorService;
        $result = $service->simulate(1000.0, 10, 10.0);

        $this->assertEquals(1100.0, $result['total_repayment']);
        $this->assertEquals(110.0, $result['installment_value']);
    }
}
