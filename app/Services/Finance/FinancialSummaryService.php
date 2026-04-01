<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Regras e agregados financeiros (única entrada para cálculos além de scopes Eloquent no model).
 */
final class FinancialSummaryService
{
    /**
     * Um mês civil completo (YYYY-MM).
     *
     * @return array{0: string, 1: string} [start Y-m-d, end Y-m-d]
     */
    public function monthToDateRange(string $yearMonth): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $yearMonth, $m)) {
            throw new InvalidArgumentException('Mês inválido. Use o formato YYYY-MM.');
        }
        $y = (int) $m[1];
        $monthNum = (int) $m[2];
        if ($monthNum < 1 || $monthNum > 12) {
            throw new InvalidArgumentException('Mês inválido.');
        }
        $start = sprintf('%04d-%02d-01', $y, $monthNum);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $monthNum, $y);
        $end = sprintf('%04d-%02d-%02d', $y, $monthNum, $lastDay);

        return [$start, $end];
    }

    public function normalizeMonth(?string $month): string
    {
        if ($month === null || $month === '') {
            return now()->format('Y-m');
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return now()->format('Y-m');
        }
        try {
            $this->monthToDateRange($month);

            return $month;
        } catch (InvalidArgumentException) {
            return now()->format('Y-m');
        }
    }

    /**
     * @return array<string, string> [Y-m => label]
     */
    public function recentMonthsForSelect(int $count = 24): array
    {
        $out = [];
        $cursor = now()->startOfMonth();
        for ($i = 0; $i < $count; $i++) {
            $key = $cursor->format('Y-m');
            $out[$key] = $cursor->format('m/Y');
            $cursor = $cursor->copy()->subMonth();
        }

        return $out;
    }

    /**
     * @return Collection<int, object{ym: string, income_total: string|float|int, expense_total: string|float|int}>
     */
    public function monthlyIncomeExpenseGroupedThroughDate(int $userId, string $throughDateInclusive): Collection
    {
        $driver = DB::connection()->getDriverName();
        $ymExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', transaction_date)",
            'pgsql' => "to_char(transaction_date, 'YYYY-MM')",
            default => "date_format(transaction_date, '%Y-%m')",
        };

        return Transaction::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<=', $throughDateInclusive)
            ->selectRaw(
                "{$ymExpr} as ym, ".
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]
            )
            ->groupBy(DB::raw($ymExpr))
            ->orderBy('ym')
            ->get();
    }

    /**
     * @return object{income_total: float, expense_total: float}
     */
    public function incomeExpenseTotalsStrictlyBeforeDate(int $userId, string $beforeDateYmd): object
    {
        $row = Transaction::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<', $beforeDateYmd)
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]
            )->first();

        $inc = (float) ($row->income_total ?? 0);
        $exp = (float) ($row->expense_total ?? 0);

        return (object) ['income_total' => $inc, 'expense_total' => $exp];
    }

    /**
     * @return object{income_total: mixed, expense_total: mixed, tx_count: int}
     */
    public function aggregateMonthStats(int $userId, string $yearMonth, ?int $categoryId = null): object
    {
        [$start, $end] = $this->monthToDateRange($yearMonth);

        $q = Transaction::query()
            ->forUser($userId)
            ->whereBetween('transaction_date', [$start, $end]);

        if ($categoryId !== null) {
            $q->where('category_id', $categoryId);
        }

        $row = $q->selectRaw(
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total, '.
            'COUNT(*) as tx_count',
            [Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]
        )->first();

        return $row ?? (object) [
            'income_total' => 0,
            'expense_total' => 0,
            'tx_count' => 0,
        ];
    }

    /**
     * @return object{income_total: float, expense_total: float, balance: float}
     */
    public function cumulativeThroughMonthEnd(int $userId, string $yearMonth): object
    {
        [, $end] = $this->monthToDateRange($yearMonth);

        $row = Transaction::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<=', $end)
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [Transaction::TYPE_INCOME, Transaction::TYPE_EXPENSE]
            )->first();

        $inc = (float) ($row->income_total ?? 0);
        $exp = (float) ($row->expense_total ?? 0);

        return (object) [
            'income_total' => $inc,
            'expense_total' => $exp,
            'balance' => $inc - $exp,
        ];
    }

    /**
     * @return array{income: float, expense: float, available: float}
     */
    public function periodSummary(int $userId, array $filters): array
    {
        $month = $filters['month'] ?? null;
        if (is_string($month) && $month !== '') {
            $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
            $row = $this->aggregateMonthStats($userId, $month, $categoryId);
            $income = (float) ($row->income_total ?? 0);
            $expense = (float) ($row->expense_total ?? 0);

            return [
                'income' => $income,
                'expense' => $expense,
                'available' => $income - $expense,
            ];
        }

        $income = (float) Transaction::query()->forUser($userId)->income()->filter($filters)->sum('amount');
        $expense = (float) Transaction::query()->forUser($userId)->expense()->filter($filters)->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'available' => $income - $expense,
        ];
    }

    public function balanceForUser(int $userId, ?array $filters = null): float
    {
        $incomeQ = Transaction::query()->forUser($userId)->income();
        $expenseQ = Transaction::query()->forUser($userId)->expense();
        if ($filters) {
            $incomeQ->filter($filters);
            $expenseQ->filter($filters);
        }

        return (float) $incomeQ->sum('amount') - (float) $expenseQ->sum('amount');
    }

    /**
     * @return array<int, array{income: float, expense: float, net: float, category_name: string|null, category_type: string|null}>
     */
    public function totalsByCategoryForUser(int $userId, ?array $filters = null): array
    {
        $query = Transaction::query()
            ->forUser($userId)
            ->select([
                DB::raw('COALESCE(category_id, 0) as category_key'),
                DB::raw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income_total"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense_total"),
            ])
            ->groupBy(DB::raw('COALESCE(category_id, 0)'));

        if ($filters) {
            $query->filter($filters);
        }

        $rows = $query->get();
        $categories = Category::forUser($userId)->get(['id', 'name', 'type'])->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $income = (float) $row->income_total;
            $expense = (float) $row->expense_total;
            $key = (int) $row->category_key;
            $cid = $key === 0 ? null : $key;
            $cat = $cid ? $categories->get($cid) : null;
            $rawType = $cat ? (string) ($cat->type ?? '') : '';
            $categoryType = $cid ? ($rawType !== '' ? $rawType : Category::TYPE_EXPENSE) : null;

            $result[$key] = [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'category_name' => $cid ? ($cat ? $cat->name : '—') : 'Sem categoria',
                'category_type' => $categoryType,
            ];
        }

        return $result;
    }
}
