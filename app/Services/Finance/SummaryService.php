<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Carbon\Carbon;

final class SummaryService
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forMonth(int $userId, ?string $monthQuery): array
    {
        $month = Transaction::normalizeMonth($monthQuery);
        $filters = ['month' => $month];
        $period = Transaction::periodSummary($userId, $filters);
        $acumulado = Transaction::cumulativeStatsThroughMonthEnd($userId, $month);
        $mesAnterior = Carbon::parse($month.'-01')->subMonth()->format('Y-m');
        $ateInicioMes = Transaction::cumulativeStatsThroughMonthEnd($userId, $mesAnterior);
        $forecast = $this->dashboard->getMonthlyForecast($userId, $month);
        $previstoAcumuladoFim = round($ateInicioMes->saldo_caixa + $forecast['saldo_previsto_mes'], 2);

        return [
            'forecast_type' => DashboardService::FORECAST_TYPE_REALIZED_ONLY,
            'month' => $month,
            'balance_all_time' => Transaction::balanceForUser($userId),
            'income_month' => $period['income'],
            'expense_month' => $period['expense'],
            'expense_cash_month' => $period['expense_cash'],
            'expense_credit_card_month' => $period['expense_credit_card'],
            'available_this_month' => $period['available'],
            'available_with_card_month' => $period['available_with_card'],
            'saldo_acumulado_ate_mes' => round($acumulado->saldo_caixa, 2),
            'saldo_acumulado_com_cartao_ate_mes' => round($acumulado->saldo_com_cartao, 2),
            'acumulado_ate_inicio_mes' => round($ateInicioMes->saldo_caixa, 2),
            'saldo_previsto_acumulado_fim_mes' => $previstoAcumuladoFim,
        ];
    }
}
