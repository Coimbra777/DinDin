<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\CarbonInterface;

/**
 * Idempotência: no máximo uma transação filha por mês civil por template ({@see Transaction::$parent_transaction_id}).
 */
final class TransactionDuplicateGuard
{
    /**
     * Já existe filha com data de transação no mesmo mês civil que {@code $dateInMonth}?
     */
    public static function hasChildInCalendarMonth(int $userId, int $parentTransactionId, CarbonInterface $dateInMonth): bool
    {
        return self::existsChildForYearMonth(
            $userId,
            $parentTransactionId,
            (int) $dateInMonth->format('Y'),
            (int) $dateInMonth->format('n'),
        );
    }

    public static function existsChildForYearMonth(int $userId, int $parentTransactionId, int $year, int $month): bool
    {
        return Transaction::query()
            ->forUser($userId)
            ->where('parent_transaction_id', $parentTransactionId)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->exists();
    }
}
