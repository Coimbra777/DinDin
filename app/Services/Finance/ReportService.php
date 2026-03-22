<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;

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
        $out = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $row = Transaction::aggregateMonthStats($userId, $key, null);
            $rec = (float) ($row->income_total ?? 0);
            $desp = (float) ($row->expense_total ?? 0);
            $out[] = [
                'month' => $key,
                'receitas' => round($rec, 2),
                'despesas' => round($desp, 2),
                'saldo_mes' => round($rec - $desp, 2),
            ];
            $cursor = $cursor->copy()->subMonth();
        }

        $out = array_reverse($out);
        foreach ($out as &$row) {
            $cum = Transaction::cumulativeStatsThroughMonthEnd($userId, (string) $row['month']);
            $row['saldo_acumulado'] = round($cum->balance, 2);
        }
        unset($row);

        return $out;
    }
}
