<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Transaction;

final class SummaryService
{
    /**
     * @return array<string, mixed>
     */
    public function forMonth(int $userId, ?string $monthQuery): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $filters = ['month' => $month];
        $period = Transaction::periodSummary($userId, $filters);

        return [
            'month' => $month,
            'balance_all_time' => Transaction::balanceForUser($userId),
            'income_month' => $period['income'],
            'expense_month' => $period['expense'],
            'expense_cash_month' => $period['expense_cash'],
            'expense_credit_card_month' => $period['expense_credit_card'],
            'available_this_month' => $period['available'],
            'available_with_card_month' => $period['available_with_card'],
        ];
    }
}
