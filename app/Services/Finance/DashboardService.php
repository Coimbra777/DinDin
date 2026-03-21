<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

final class DashboardService
{
    /**
     * Painel do mês: saldos, totais e últimas transações (sem recorrência automática).
     *
     * @return array<string, mixed>
     */
    public function buildPayload(int $userId, ?string $monthQuery): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        [$start, $end] = Transaction::monthToDateRange($month);

        $row = Transaction::aggregateMonthStats($userId, $month, null);
        $receitasMes = (float) ($row->income_total ?? 0);
        $despesasCaixa = (float) ($row->expense_cash ?? 0);
        $despesasCartao = (float) ($row->expense_card ?? 0);
        $saldoReal = $receitasMes - $despesasCaixa;
        $saldoComCartao = $receitasMes - $despesasCaixa - $despesasCartao;
        $totalTransacoes = (int) ($row->tx_count ?? 0);

        $acumulado = Transaction::cumulativeStatsThroughMonthEnd($userId, $month);
        $mesAnterior = Carbon::parse($start)->subMonth()->format('Y-m');
        $acumuladoAteInicioMes = Transaction::cumulativeStatsThroughMonthEnd($userId, $mesAnterior);

        $forecast = $this->getMonthlyForecast($userId, $month);
        $saldoPrevistoAcumuladoFimMes = round(
            $acumuladoAteInicioMes->saldo_caixa + $forecast['saldo_previsto_mes'],
            2
        );

        /** @see self::FORECAST_TYPE_REALIZED_ONLY — sem projeção além do já lançado no mês */
        $forecastType = self::FORECAST_TYPE_REALIZED_ONLY;

        $ultimas = Transaction::forUser($userId)
            ->with(['category:id,name,color,type', 'creditCard:id,name'])
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'month' => $month,
            'saldo_real' => round($saldoReal, 2),
            'saldo_atual' => round($acumulado->saldo_caixa, 2),
            'saldo_com_cartao' => round($saldoComCartao, 2),
            'saldo_acumulado' => round($acumulado->saldo_caixa, 2),
            'saldo_acumulado_com_cartao' => round($acumulado->saldo_com_cartao, 2),
            'acumulado_ate_inicio_mes' => round($acumuladoAteInicioMes->saldo_caixa, 2),
            'saldo_previsto_acumulado_fim_mes' => $saldoPrevistoAcumuladoFimMes,
            'receitas_mes' => round($receitasMes, 2),
            'despesas_mes' => round($despesasCaixa, 2),
            'despesas_caixa_mes' => round($despesasCaixa, 2),
            'despesas_cartao_mes' => round($despesasCartao, 2),
            'total_transacoes' => $totalTransacoes,
            'ultimas_transacoes' => $ultimas->map(fn (Transaction $t) => TransactionResource::toArray($t))->all(),
            'entradas_previstas_mes' => $forecast['entradas_previstas_mes'],
            'despesas_fixas_previstas_mes' => $forecast['despesas_fixas_previstas_mes'],
            'saldo_previsto_mes' => $forecast['saldo_previsto_mes'],
            'forecast_type' => $forecastType,
        ];
    }

    /**
     * “Previsão” = espelho do realizado no mês (caixa): não há modelo preditivo nem recorrência automática.
     *
     * @return array{entradas_previstas_mes: float, despesas_fixas_previstas_mes: float, saldo_previsto_mes: float}
     */
    public function getMonthlyForecast(int $userId, string $monthYyyyMm): array
    {
        $row = Transaction::aggregateMonthStats($userId, $monthYyyyMm, null);
        $receitasMes = (float) ($row->income_total ?? 0);
        $despesasCaixa = (float) ($row->expense_cash ?? 0);
        $saldoReal = round($receitasMes - $despesasCaixa, 2);

        return [
            'entradas_previstas_mes' => 0.0,
            'despesas_fixas_previstas_mes' => 0.0,
            'saldo_previsto_mes' => $saldoReal,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getUpcomingCommitments(int $userId, int $limit = 30): array
    {
        return [];
    }
}
