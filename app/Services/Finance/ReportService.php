<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

final class ReportService
{
    private const CACHE_VERSION = 'v1';

    private const TREND_CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly FinancialSummaryService $summaries,
        private readonly FinanceReadCache $readCache,
    ) {}

    /**
     * Totais por categoria no mês (análise / gráficos).
     *
     * @param  array<string, mixed>  $extraFilters  ex.: payment_status = pending|paid|overdue
     * @return array<int, array{category_key: int, category_name: string, category_type: string|null, income: float, expense: float, net: float}>
     */
    public function categoryBreakdown(int $userId, ?string $monthQuery, array $extraFilters = []): array
    {
        $month = $this->summaries->normalizeMonth($monthQuery);
        $filters = array_merge(['month' => $month], $extraFilters);
        $totals = $this->summaries->totalsByCategoryForUser($userId, $filters);

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
        $rev = $this->readCache->revision($userId);
        $cacheKey = 'finance.trend.'.self::CACHE_VERSION.'.'.$userId.'.'.$rev.'.'.$months;

        return Cache::remember(
            $cacheKey,
            self::TREND_CACHE_TTL_SECONDS,
            fn (): array => $this->computeMonthlyTrend($userId, $months)
        );
    }

    /**
     * @return list<array{month: string, receitas: float, despesas: float, saldo_mes: float, saldo_acumulado: float}>
     */
    private function computeMonthlyTrend(int $userId, int $months): array
    {
        $keysNewestFirst = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $months; $i++) {
            $keysNewestFirst[] = $cursor->format('Y-m');
            $cursor = $cursor->copy()->subMonth();
        }

        $newestYm = $keysNewestFirst[0];
        $oldestYm = $keysNewestFirst[array_key_last($keysNewestFirst)];
        [, $throughEnd] = $this->summaries->monthToDateRange($newestYm);
        [$windowStart] = $this->summaries->monthToDateRange($oldestYm);

        $rows = $this->summaries->monthlyIncomeExpenseGroupedThroughDate($userId, $throughEnd);
        $byYm = [];
        foreach ($rows as $row) {
            $byYm[$row->ym] = [
                'income' => (float) $row->income_total,
                'expense' => (float) $row->expense_total,
            ];
        }

        $prior = $this->summaries->incomeExpenseStrictlyBeforeDate($userId, $windowStart);
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
