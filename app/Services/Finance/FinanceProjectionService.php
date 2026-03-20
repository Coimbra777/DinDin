<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

/**
 * Projeção dos próximos 12 meses: repete o padrão do último mês com movimento real
 * e calcula acumulados (soma progressiva de receitas e despesas).
 *
 * Não usa média móvel nem projeção de parcelas — só totais reais do mês de referência.
 */
final class FinanceProjectionService
{
    private const MONTHS_AHEAD = 12;

    private const MAX_MONTHS_LOOKBACK = 18;

    /**
     * @return array{
     *     meses: list<array{
     *         mes: string,
     *         label: string,
     *         receita_mes: float,
     *         despesa_mes: float,
     *         receita_acumulada: float,
     *         despesa_acumulada: float,
     *         saldo_acumulado: float,
     *         receitas_previstas: float,
     *         despesas_previstas: float,
     *         saldo_projetado: float
     *     }>,
     *     meta: array{
     *         mes_referencia: string,
     *         label_referencia: string,
     *         receita_mes_referencia: float,
     *         despesa_mes_referencia: float
     *     }
     * }
     */
    public static function project(int $userId, ?Carbon $now = null): array
    {
        $now = $now ? $now->copy()->timezone(config('app.timezone')) : now(config('app.timezone'));
        $firstProjected = $now->copy()->startOfMonth()->addMonth();

        [$refKey, $incomeBase, $expenseBase] = self::resolveBaselineMonth($userId, $firstProjected);

        $cumIncome = 0.0;
        $cumExpense = 0.0;
        $meses = [];

        for ($i = 0; $i < self::MONTHS_AHEAD; $i++) {
            $cursor = $firstProjected->copy()->addMonths($i);
            $key = $cursor->format('Y-m');
            $cumIncome += $incomeBase;
            $cumExpense += $expenseBase;
            $saldoAcum = $cumIncome - $cumExpense;

            $meses[] = [
                'mes' => $key,
                'label' => self::monthLabelPt($cursor),
                'receita_mes' => round($incomeBase, 2),
                'despesa_mes' => round($expenseBase, 2),
                'receita_acumulada' => round($cumIncome, 2),
                'despesa_acumulada' => round($cumExpense, 2),
                'saldo_acumulado' => round($saldoAcum, 2),
                'receitas_previstas' => round($incomeBase, 2),
                'despesas_previstas' => round($expenseBase, 2),
                'saldo_projetado' => round($saldoAcum, 2),
            ];
        }

        $refCarbon = Carbon::parse($refKey.'-01')->startOfMonth();

        return [
            'meses' => $meses,
            'meta' => [
                'mes_referencia' => $refKey,
                'label_referencia' => self::monthLabelPt($refCarbon),
                'receita_mes_referencia' => round($incomeBase, 2),
                'despesa_mes_referencia' => round($expenseBase, 2),
            ],
        ];
    }

    /**
     * Último mês civil (até 18 meses para trás) com alguma receita ou despesa;
     * senão usa o mês imediatamente anterior ao primeiro projetado com zeros.
     *
     * @return array{0: string, 1: float, 2: float} [Y-m, receita_mês, despesa_mês]
     */
    private static function resolveBaselineMonth(int $userId, Carbon $firstProjected): array
    {
        $fallbackKey = $firstProjected->copy()->subMonth()->format('Y-m');

        for ($back = 1; $back <= self::MAX_MONTHS_LOOKBACK; $back++) {
            $key = $firstProjected->copy()->subMonths($back)->format('Y-m');
            $row = Transaction::aggregateMonthStats($userId, $key, null);
            $income = (float) ($row->income_total ?? 0);
            $expense = (float) ($row->expense_cash ?? 0) + (float) ($row->expense_card ?? 0);

            if ($income > 0.00001 || $expense > 0.00001) {
                return [$key, $income, $expense];
            }
        }

        return [$fallbackKey, 0.0, 0.0];
    }

    private static function monthLabelPt(Carbon $d): string
    {
        $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

        return $months[$d->month - 1].'/'.$d->format('Y');
    }
}
