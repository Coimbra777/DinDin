<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;

/**
 * Projeção dos próximos N meses civis a partir do mês seguinte ao atual.
 * Usa os mesmos agregados mensais que listagens / dashboard.
 */
final class FinanceProjectionService
{
    public const MONTHS_AHEAD = 12;

    public function __construct(
        private readonly FinancialSummaryService $summaries,
    ) {}

    /**
     * @return array{months: list<array{month: string, income: float, expense: float, balance: float}>}
     */
    public function project(int $userId, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->timezone(config('app.timezone')) : now(config('app.timezone'));
        $currentMonthKey = $now->format('Y-m');
        $firstMonth = $now->copy()->startOfMonth()->addMonth();

        $opening = $this->summaries->cumulativeThroughMonthEnd($userId, $currentMonthKey);
        $balance = (float) $opening->balance;

        $months = [];
        for ($i = 0; $i < self::MONTHS_AHEAD; $i++) {
            $cursor = $firstMonth->copy()->addMonths($i);
            $key = $cursor->format('Y-m');
            $row = $this->summaries->aggregateMonthStats($userId, $key, null);
            $income = (float) ($row->income_total ?? 0);
            $expense = (float) ($row->expense_total ?? 0);

            $balance += $income - $expense;

            $months[] = [
                'month' => $key,
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'balance' => round($balance, 2),
            ];
        }

        return ['months' => $months];
    }
}
