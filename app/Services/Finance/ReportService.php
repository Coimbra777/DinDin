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
     * Série mensal: receitas, despesas caixa, despesas cartão e saldos (últimos N meses).
     *
     * @return list<array{month: string, receitas: float, despesas_caixa: float, despesas_cartao: float, saldo_real: float, saldo_com_cartao: float}>
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
            $cash = (float) ($row->expense_cash ?? 0);
            $card = (float) ($row->expense_card ?? 0);
            $out[] = [
                'month' => $key,
                'receitas' => round($rec, 2),
                'despesas_caixa' => round($cash, 2),
                'despesas_cartao' => round($card, 2),
                'saldo_real' => round($rec - $cash, 2),
                'saldo_com_cartao' => round($rec - $cash - $card, 2),
            ];
            $cursor = $cursor->copy()->subMonth();
        }

        return array_reverse($out);
    }
}
