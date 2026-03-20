<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

/**
 * Frases e métricas automáticas a partir do mês corrente ou informado.
 *
 * @phpstan-type InsightItem array{type: string, message: string, meta?: array<string, mixed>}
 */
final class FinanceInsightService
{
    /**
     * @return array{month: string, insights: list<InsightItem>, categorias: list<array<string, mixed>>, comparacao_mes_anterior: array<string, float|int|string>}
     */
    public function forUser(int $userId, ?string $monthQuery = null): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $filters = ['month' => $month];
        $totals = Transaction::totalsByCategoryForUser($userId, $filters);

        $totalExpense = 0.0;
        foreach ($totals as $row) {
            $totalExpense += (float) $row['expense'];
        }

        $categorias = [];
        foreach ($totals as $key => $row) {
            $exp = (float) $row['expense'];
            if ($exp <= 0) {
                continue;
            }
            $pct = $totalExpense > 0 ? round(($exp / $totalExpense) * 100, 1) : 0.0;
            $categorias[] = [
                'category_key' => $key,
                'category_name' => $row['category_name'],
                'despesa' => round($exp, 2),
                'percentual_das_despesas' => $pct,
            ];
        }

        usort($categorias, fn ($a, $b) => $b['despesa'] <=> $a['despesa']);

        $insights = [];

        if ($categorias !== []) {
            $top = $categorias[0];
            if ($top['percentual_das_despesas'] >= 5) {
                $insights[] = [
                    'type' => 'top_category_share',
                    'message' => sprintf(
                        'Você gasta %.1f%% com %s.',
                        $top['percentual_das_despesas'],
                        $top['category_name']
                    ),
                    'meta' => [
                        'category_name' => $top['category_name'],
                        'percentual' => $top['percentual_das_despesas'],
                    ],
                ];
            }
        }

        $prev = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->subMonth()->format('Y-m');
        $curRow = Transaction::aggregateMonthStats($userId, $month, null);
        $prevRow = Transaction::aggregateMonthStats($userId, $prev, null);
        $curExp = (float) ($curRow->expense_cash ?? 0) + (float) ($curRow->expense_card ?? 0);
        $prevExp = (float) ($prevRow->expense_cash ?? 0) + (float) ($prevRow->expense_card ?? 0);

        $variacaoPct = 0.0;
        if ($prevExp > 0) {
            $variacaoPct = round((($curExp - $prevExp) / $prevExp) * 100, 1);
        }

        $comparacao = [
            'mes_atual' => $month,
            'mes_anterior' => $prev,
            'despesa_mes_atual' => round($curExp, 2),
            'despesa_mes_anterior' => round($prevExp, 2),
            'variacao_percentual' => $variacaoPct,
        ];

        if ($prevExp > 0 && $variacaoPct >= 10) {
            $insights[] = [
                'type' => 'spending_increase',
                'message' => sprintf('Seus gastos aumentaram %.1f%% em relação ao mês anterior.', $variacaoPct),
                'meta' => ['variacao_percentual' => $variacaoPct],
            ];
        } elseif ($prevExp > 0 && $variacaoPct <= -10) {
            $insights[] = [
                'type' => 'spending_decrease',
                'message' => sprintf('Seus gastos diminuíram %.1f%% em relação ao mês anterior.', abs($variacaoPct)),
                'meta' => ['variacao_percentual' => $variacaoPct],
            ];
        }

        return [
            'month' => $month,
            'insights' => $insights,
            'categorias' => array_slice($categorias, 0, 12),
            'comparacao_mes_anterior' => $comparacao,
        ];
    }
}
