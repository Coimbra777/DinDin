<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;

/**
 * Indicadores do mês (receita, despesas, saldo, comparação com média recente).
 * Usado por alertas e por metas (contexto de pressão no caixa).
 */
final class FinanceMonthMetrics
{
    public const SPENDING_SPIKE_RATIO = 1.25;

    public function __construct(
        private readonly FinancialSummaryService $summaries,
    ) {}

    /**
     * @return array{
     *     month: string,
     *     income: float,
     *     expense_total: float,
     *     total_expense: float,
     *     saldo: float,
     *     negative_balance: bool,
     *     spending_spike: bool,
     *     spending_spike_percent: float|null,
     *     avg_prior_expense: float
     * }
     */
    public function snapshot(int $userId, ?string $monthQuery = null): array
    {
        $month = $this->summaries->normalizeMonth($monthQuery);
        $row = $this->summaries->aggregateMonthStats($userId, $month, null);
        $income = (float) ($row->income_total ?? 0);
        $expense = (float) ($row->expense_total ?? 0);
        $saldo = $income - $expense;

        $avgPrior = $this->averageTotalExpensePriorMonths($userId, $month, 3);
        $spike = $avgPrior > 0 && $expense > $avgPrior * self::SPENDING_SPIKE_RATIO;
        $spikePct = null;
        if ($spike && $avgPrior > 0) {
            $spikePct = round((($expense / $avgPrior) - 1) * 100, 1);
        }

        return [
            'month' => $month,
            'income' => $income,
            'expense_total' => round($expense, 2),
            'total_expense' => round($expense, 2),
            'saldo' => round($saldo, 2),
            'negative_balance' => $saldo < 0,
            'spending_spike' => $spike,
            'spending_spike_percent' => $spikePct,
            'avg_prior_expense' => round($avgPrior, 2),
        ];
    }

    private function averageTotalExpensePriorMonths(int $userId, string $yearMonth, int $count): float
    {
        $cursor = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth()->subMonth();
        $sum = 0.0;
        $n = 0;
        for ($i = 0; $i < $count; $i++) {
            $key = $cursor->format('Y-m');
            $row = $this->summaries->aggregateMonthStats($userId, $key, null);
            $sum += (float) ($row->expense_total ?? 0);
            $n++;
            $cursor = $cursor->copy()->subMonth();
        }

        return $n > 0 ? $sum / $n : 0.0;
    }
}
