<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Database\Factories\Finance\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Transaction extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    protected $table = 'finance_transactions';

    protected $fillable = [
        'user_id',
        'parent_transaction_id',
        'category_id',
        'recurring_transaction_id',
        'title',
        'amount',
        'type',
        'transaction_date',
        'description',
        'installment_number',
        'installment_of',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function recurringSource(): BelongsTo
    {
        return $this->belongsTo(RecurringTransaction::class, 'recurring_transaction_id');
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_transaction_id');
    }

    public function childDuplicates(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transaction_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['type']) && in_array($filters['type'], [self::TYPE_INCOME, self::TYPE_EXPENSE], true)) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['month']) && is_string($filters['month'])) {
            [$start, $end] = self::monthToDateRange($filters['month']);
            $query->whereBetween('transaction_date', [$start, $end]);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Um mês civil completo (YYYY-MM).
     *
     * @return array{0: string, 1: string} [start Y-m-d, end Y-m-d]
     */
    public static function monthToDateRange(string $yearMonth): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $yearMonth, $m)) {
            throw new InvalidArgumentException('Mês inválido. Use o formato YYYY-MM.');
        }
        $y = (int) $m[1];
        $month = (int) $m[2];
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Mês inválido.');
        }
        $start = sprintf('%04d-%02d-01', $y, $month);
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $y);
        $end = sprintf('%04d-%02d-%02d', $y, $month, $lastDay);

        return [$start, $end];
    }

    /**
     * Valida ou devolve o mês atual no formato YYYY-MM.
     */
    public static function normalizeMonth(?string $month): string
    {
        if ($month === null || $month === '') {
            return now()->format('Y-m');
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return now()->format('Y-m');
        }
        try {
            self::monthToDateRange($month);

            return $month;
        } catch (InvalidArgumentException) {
            return now()->format('Y-m');
        }
    }

    /**
     * Lista de meses para selects (mais recente primeiro).
     *
     * @return array<string, string> [Y-m => label]
     */
    public static function recentMonthsForSelect(int $count = 24): array
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
     * Totais de receita/despesa agrupados por mês civil (YYYY-MM) até à data indicada (inclusive).
     * Suporta MySQL e SQLite (testes).
     *
     * @return Collection<int, object{ym: string, income_total: string|float|int, expense_total: string|float|int}>
     */
    public static function monthlyIncomeExpenseGroupedThroughDate(int $userId, string $throughDateInclusive): Collection
    {
        $driver = DB::connection()->getDriverName();
        $ymExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', transaction_date)",
            'pgsql' => "to_char(transaction_date, 'YYYY-MM')",
            default => "date_format(transaction_date, '%Y-%m')",
        };

        return static::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<=', $throughDateInclusive)
            ->selectRaw(
                "{$ymExpr} as ym, ".
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [self::TYPE_INCOME, self::TYPE_EXPENSE]
            )
            ->groupBy(DB::raw($ymExpr))
            ->orderBy('ym')
            ->get();
    }

    /**
     * Soma receitas/despesas com data estritamente anterior a {@code $beforeDateYmd} (YYYY-MM-DD).
     *
     * @return object{income_total: float, expense_total: float}
     */
    public static function incomeExpenseTotalsStrictlyBeforeDate(int $userId, string $beforeDateYmd): object
    {
        $incomeType = self::TYPE_INCOME;
        $expenseType = self::TYPE_EXPENSE;

        $row = static::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<', $beforeDateYmd)
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [$incomeType, $expenseType]
            )->first();

        $inc = (float) ($row->income_total ?? 0);
        $exp = (float) ($row->expense_total ?? 0);

        return (object) ['income_total' => $inc, 'expense_total' => $exp];
    }

    /**
     * Agregados de um mês civil (YYYY-MM) em uma única query.
     *
     * @return object{income_total: mixed, expense_total: mixed, tx_count: int}
     */
    public static function aggregateMonthStats(int $userId, string $yearMonth, ?int $categoryId = null): object
    {
        [$start, $end] = self::monthToDateRange($yearMonth);
        $incomeType = self::TYPE_INCOME;
        $expenseType = self::TYPE_EXPENSE;

        $q = static::query()
            ->forUser($userId)
            ->whereBetween('transaction_date', [$start, $end]);

        if ($categoryId !== null) {
            $q->where('category_id', $categoryId);
        }

        $row = $q->selectRaw(
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
            'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total, '.
            'COUNT(*) as tx_count',
            [$incomeType, $expenseType]
        )->first();

        return $row ?? (object) [
            'income_total' => 0,
            'expense_total' => 0,
            'tx_count' => 0,
        ];
    }

    /**
     * Totais acumulados desde o primeiro lançamento até ao último dia do mês (inclusive).
     * Saldo = receitas − despesas (todas as despesas).
     *
     * @return object{income_total: float, expense_total: float, balance: float}
     */
    public static function cumulativeStatsThroughMonthEnd(int $userId, string $yearMonth): object
    {
        [, $end] = self::monthToDateRange($yearMonth);
        $incomeType = self::TYPE_INCOME;
        $expenseType = self::TYPE_EXPENSE;

        $row = static::query()
            ->forUser($userId)
            ->whereDate('transaction_date', '<=', $end)
            ->selectRaw(
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, '.
                'SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as expense_total',
                [$incomeType, $expenseType]
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
     * Receitas, despesas e saldo líquido para os filtros aplicados (ex.: mês + categoria).
     *
     * @return array{income: float, expense: float, available: float}
     */
    public static function periodSummary(int $userId, array $filters): array
    {
        $month = $filters['month'] ?? null;
        if (is_string($month) && $month !== '') {
            $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
            $row = self::aggregateMonthStats($userId, $month, $categoryId);
            $income = (float) ($row->income_total ?? 0);
            $expense = (float) ($row->expense_total ?? 0);

            return [
                'income' => $income,
                'expense' => $expense,
                'available' => $income - $expense,
            ];
        }

        $income = (float) static::query()->forUser($userId)->income()->filter($filters)->sum('amount');
        $expense = (float) static::query()->forUser($userId)->expense()->filter($filters)->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'available' => $income - $expense,
        ];
    }

    /**
     * Saldo (receitas − despesas) para o utilizador.
     */
    public static function balanceForUser(int $userId, ?array $filters = null): float
    {
        $incomeQ = static::query()->forUser($userId)->income();
        $expenseQ = static::query()->forUser($userId)->expense();
        if ($filters) {
            $incomeQ->filter($filters);
            $expenseQ->filter($filters);
        }

        return (float) $incomeQ->sum('amount') - (float) $expenseQ->sum('amount');
    }

    /**
     * Totais agregados por categoria: ['category_id' => ['income' => x, 'expense' => y, 'net' => z], ...]
     *
     * @return array<int, array{income: float, expense: float, net: float, category_name: string|null, category_type: string|null}>
     */
    public static function totalsByCategoryForUser(int $userId, ?array $filters = null): array
    {
        $query = static::query()
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
