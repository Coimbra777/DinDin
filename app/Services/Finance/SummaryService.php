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

        $saldoAteFimMes = round($acumulado->balance, 2);

        return [
            'forecast_type' => DashboardService::FORECAST_TYPE_REALIZED_ONLY,
            'month' => $month,
            'balance_all_time' => Transaction::balanceForUser($userId),
            'income_month' => $period['income'],
            'expense_month' => $period['expense'],
            'available_this_month' => $period['available'],
            'saldo_acumulado_ate_mes' => $saldoAteFimMes,
            'acumulado_ate_inicio_mes' => round($ateInicioMes->balance, 2),
            'saldo_previsto_acumulado_fim_mes' => $saldoAteFimMes,
        ];
    }
}
