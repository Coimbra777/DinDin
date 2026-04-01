<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

final class ReportService
{
    /**
     * Totais por categoria no mês (análise / gráficos).
     *
     * @return array<int, array{category_key: int, category_name: string, category_type: string|null, income: float, expense: float, net: float}>
     */
    public function categoryBreakdown(int $userId, ?string $monthQuery): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $filters = ['month' => $month];
        $totals = Transaction::totalsByCategoryForUser($userId, $filters);

        $out = [];
        foreach ($totals as $key => $row) {
            $out[] = [
                'category_key' => $key,
                'category_name' => $row['category_name'],
                'category_type' => $row['category_type'] ?? null,
                'income' => $row['income'],
                'expense' => $row['expense'],
                'net' => $row['net'],
            ];
        }

        return $out;
    }

    /**
     * Série mensal: receitas, despesas e saldo acumulado (últimos N meses).
     *
     * @return list<array{month: string, receitas: float, despesas: float, saldo_mes: float, saldo_acumulado: float}>
     */
    public function monthlyTrend(int $userId, int $months = 6): array
    {
        $months = max(1, min(36, $months));

        $keysNewestFirst = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $keysNewestFirst[] = $cursor->format('Y-m');
            $cursor = $cursor->copy()->subMonth();
        }

        $newestYm = $keysNewestFirst[0];
        $oldestYm = $keysNewestFirst[array_key_last($keysNewestFirst)];
        [, $throughEnd] = Transaction::monthToDateRange($newestYm);
        [$windowStart] = Transaction::monthToDateRange($oldestYm);

        $rows = Transaction::monthlyIncomeExpenseGroupedThroughDate($userId, $throughEnd);
        $byYm = [];
        foreach ($rows as $row) {
            $byYm[$row->ym] = [
                'income' => (float) $row->income_total,
                'expense' => (float) $row->expense_total,
            ];
        }

        $prior = Transaction::incomeExpenseTotalsStrictlyBeforeDate($userId, $windowStart);
        $running = (float) $prior->income_total - (float) $prior->expense_total;

        $cumulative = [];
        $walk = Carbon::createFromFormat('Y-m', $oldestYm)->startOfMonth();
        $endWalk = Carbon::createFromFormat('Y-m', $newestYm)->startOfMonth();

        while ($walk <= $endWalk) {
            $key = $walk->format('Y-m');
            $inc = $byYm[$key]['income'] ?? 0.0;
            $exp = $byYm[$key]['expense'] ?? 0.0;
            $running += $inc - $exp;
            $cumulative[$key] = round($running, 2);
            $walk->addMonth();
        }

        $out = [];
        foreach (array_reverse($keysNewestFirst) as $key) {
            $rec = $byYm[$key]['income'] ?? 0.0;
            $desp = $byYm[$key]['expense'] ?? 0.0;
            $out[] = [
                'month' => $key,
                'receitas' => round($rec, 2),
                'despesas' => round($desp, 2),
                'saldo_mes' => round($rec - $desp, 2),
                'saldo_acumulado' => $cumulative[$key],
            ];
        }

        return $out;
    }
}
