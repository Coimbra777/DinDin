<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Finance\TransactionCategoryTypeGuard;
use PHPUnit\Framework\TestCase;

class TransactionCategoryTypeGuardTest extends TestCase
{
    public function test_null_category_id_does_not_throw(): void
    {
        TransactionCategoryTypeGuard::assertCompatible(1, null, 'income');
        TransactionCategoryTypeGuard::assertCompatible(1, null, 'expense');
        $this->assertTrue(true);
    }
}
