<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

/**
 * Indicadores do mês (receita, despesas, saldo c/ cartão, comparação com média recente).
 * Usado por alertas e por metas (contexto de pressão no caixa).
 */
final class FinanceMonthMetrics
{
    public const SPENDING_SPIKE_RATIO = 1.25;

    /**
     * @return array{
     *     month: string,
     *     income: float,
     *     expense_cash: float,
     *     expense_card: float,
     *     total_expense: float,
     *     saldo_com_cartao: float,
     *     negative_balance: bool,
     *     spending_spike: bool,
     *     spending_spike_percent: float|null,
     *     avg_prior_expense: float
     * }
     */
    public function snapshot(int $userId, ?string $monthQuery = null): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $row = Transaction::aggregateMonthStats($userId, $month, null);
        $income = (float) ($row->income_total ?? 0);
        $expCash = (float) ($row->expense_cash ?? 0);
        $expCard = (float) ($row->expense_card ?? 0);
        $totalExpense = $expCash + $expCard;
        $saldoComCartao = $income - $expCash - $expCard;

        $avgPrior = $this->averageTotalExpensePriorMonths($userId, $month, 3);
        $spike = $avgPrior > 0 && $totalExpense > $avgPrior * self::SPENDING_SPIKE_RATIO;
        $spikePct = null;
        if ($spike && $avgPrior > 0) {
            $spikePct = round((($totalExpense / $avgPrior) - 1) * 100, 1);
        }

        return [
            'month' => $month,
            'income' => $income,
            'expense_cash' => $expCash,
            'expense_card' => $expCard,
            'total_expense' => round($totalExpense, 2),
            'saldo_com_cartao' => round($saldoComCartao, 2),
            'negative_balance' => $saldoComCartao < 0,
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
            $row = Transaction::aggregateMonthStats($userId, $key, null);
            $total = (float) ($row->expense_cash ?? 0) + (float) ($row->expense_card ?? 0);
            $sum += $total;
            $n++;
            $cursor = $cursor->copy()->subMonth();
        }

        return $n > 0 ? $sum / $n : 0.0;
    }
}
