<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class DashboardService
{
    private const CACHE_VERSION = 'v1';

    private const CACHE_TTL_SECONDS = 300;

    /** Valor de `forecast_type` na API: apenas dados já lançados no mês (sem modelo preditivo). */
    public const FORECAST_TYPE_REALIZED_ONLY = 'realized_only';

    public function __construct(
        private readonly FinancialSummaryService $summaries,
        private readonly FinanceReadCache $readCache,
    ) {}

    /**
     * Painel do mês: saldos, totais e últimas transações (sem recorrência automática).
     *
     * @return array<string, mixed>
     */
    public function buildPayload(int $userId, ?string $monthQuery): array
    {
        $month = $this->summaries->normalizeMonth($monthQuery);
        $rev = $this->readCache->revision($userId);
        $cacheKey = 'finance.dashboard.'.self::CACHE_VERSION.'.'.$userId.'.'.$rev.'.'.$month;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn (): array => $this->buildPayloadUncached($userId, $month));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayloadUncached(int $userId, string $month): array
    {
        [$start, $end] = $this->summaries->monthToDateRange($month);

        $row = $this->summaries->aggregateMonthStats($userId, $month, null);
        $receitasMes = (float) ($row->income_total ?? 0);
        $despesasMes = (float) ($row->expense_total ?? 0);
        $saldoMes = $receitasMes - $despesasMes;
        $totalTransacoes = (int) ($row->tx_count ?? 0);

        $acumulado = $this->summaries->cumulativeThroughMonthEnd($userId, $month);
        $mesAnterior = Carbon::parse($start)->subMonth()->format('Y-m');
        $acumuladoAteInicioMes = $this->summaries->cumulativeThroughMonthEnd($userId, $mesAnterior);

        $forecast = $this->getMonthlyForecast($userId, $month);
        $saldoPrevistoAcumuladoFimMes = round($acumulado->balance, 2);

        $forecastType = self::FORECAST_TYPE_REALIZED_ONLY;

        $ultimas = Transaction::forUser($userId)
            ->with(['category:id,name,color,type'])
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'month' => $month,
            'saldo_real' => round($saldoMes, 2),
            'saldo_atual' => round($acumulado->balance, 2),
            'saldo_acumulado' => round($acumulado->balance, 2),
            'acumulado_ate_inicio_mes' => round($acumuladoAteInicioMes->balance, 2),
            'saldo_previsto_acumulado_fim_mes' => $saldoPrevistoAcumuladoFimMes,
            'receitas_mes' => round($receitasMes, 2),
            'despesas_mes' => round($despesasMes, 2),
            'total_transacoes' => $totalTransacoes,
            'ultimas_transacoes' => $ultimas->map(fn (Transaction $t) => TransactionResource::toArray($t))->all(),
            'entradas_previstas_mes' => $forecast['entradas_previstas_mes'],
            'despesas_fixas_previstas_mes' => $forecast['despesas_fixas_previstas_mes'],
            'saldo_previsto_mes' => $forecast['saldo_previsto_mes'],
            'forecast_type' => $forecastType,
        ];
    }

    /**
     * “Previsão” = espelho do realizado no mês: não há modelo preditivo nem recorrência automática.
     *
     * @return array{entradas_previstas_mes: float, despesas_fixas_previstas_mes: float, saldo_previsto_mes: float}
     */
    public function getMonthlyForecast(int $userId, string $monthYyyyMm): array
    {
        $row = $this->summaries->aggregateMonthStats($userId, $monthYyyyMm, null);
        $receitasMes = (float) ($row->income_total ?? 0);
        $despesasMes = (float) ($row->expense_total ?? 0);
        $saldoMes = round($receitasMes - $despesasMes, 2);

        return [
            'entradas_previstas_mes' => 0.0,
            'despesas_fixas_previstas_mes' => 0.0,
            'saldo_previsto_mes' => $saldoMes,
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
