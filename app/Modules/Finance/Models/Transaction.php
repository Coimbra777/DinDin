<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\CreditCard\Models\CreditCard;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Transaction extends Model
{
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    protected $table = 'finance_transactions';

    protected $fillable = [
        'user_id',
        'category_id',
        'credit_card_id',
        'title',
        'amount',
        'type',
        'transaction_date',
        'description',
        'installment_number',
        'installment_of',
        'is_credit_card',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'is_credit_card' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class, 'credit_card_id');
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
        if (!empty($filters['type']) && in_array($filters['type'], [self::TYPE_INCOME, self::TYPE_EXPENSE], true)) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (!empty($filters['month']) && is_string($filters['month'])) {
            [$start, $end] = self::monthToDateRange($filters['month']);
            $query->whereBetween('transaction_date', [$start, $end]);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
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
        if (!preg_match('/^(\d{4})-(\d{2})$/', $yearMonth, $m)) {
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
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
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
     * Agregados de um mês civil (YYYY-MM) em uma única query — despesas separadas: caixa vs cartão.
     *
     * @return object{income_total: mixed, expense_cash: mixed, expense_card: mixed, tx_count: int}
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
            "SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as income_total, " .
            "SUM(CASE WHEN type = ? AND (is_credit_card = 0 OR is_credit_card IS NULL) THEN amount ELSE 0 END) as expense_cash, " .
            "SUM(CASE WHEN type = ? AND is_credit_card = 1 THEN amount ELSE 0 END) as expense_card, " .
            'COUNT(*) as tx_count',
            [$incomeType, $expenseType, $expenseType]
        )->first();

        return $row ?? (object) [
            'income_total' => 0,
            'expense_cash' => 0,
            'expense_card' => 0,
            'tx_count' => 0,
        ];
    }

    /**
     * Receitas, despesas e saldo líquido para os filtros aplicados (ex.: mês + categoria).
     *
     * @return array{
     *     income: float,
     *     expense: float,
     *     expense_cash: float,
     *     expense_credit_card: float,
     *     available: float,
     *     available_with_card: float
     * }
     */
    public static function periodSummary(int $userId, array $filters): array
    {
        $month = $filters['month'] ?? null;
        if (is_string($month) && $month !== '') {
            $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
            $row = self::aggregateMonthStats($userId, $month, $categoryId);
            $income = (float) ($row->income_total ?? 0);
            $expCash = (float) ($row->expense_cash ?? 0);
            $expCard = (float) ($row->expense_card ?? 0);

            return [
                'income' => $income,
                'expense' => $expCash,
                'expense_cash' => $expCash,
                'expense_credit_card' => $expCard,
                'available' => $income - $expCash,
                'available_with_card' => $income - $expCash - $expCard,
            ];
        }

        $income = (float) static::query()->forUser($userId)->income()->filter($filters)->sum('amount');
        $expCash = (float) static::query()->forUser($userId)->expense()->filter($filters)->where(function ($q) {
            $q->where('is_credit_card', false)->orWhereNull('is_credit_card');
        })->sum('amount');
        $expCard = (float) static::query()->forUser($userId)->expense()->filter($filters)->where('is_credit_card', true)->sum('amount');

        return [
            'income' => $income,
            'expense' => $expCash,
            'expense_cash' => $expCash,
            'expense_credit_card' => $expCard,
            'available' => $income - $expCash,
            'available_with_card' => $income - $expCash - $expCard,
        ];
    }

    /**
     * Saldo (receitas − despesas) para o utilizador.
     */
    public static function balanceForUser(int $userId, ?array $filters = null): float
    {
        $incomeQ = static::query()->forUser($userId)->income();
        $expenseQ = static::query()->forUser($userId)->expense()->where(function ($q) {
            $q->where('is_credit_card', false)->orWhereNull('is_credit_card');
        });
        if ($filters) {
            $incomeQ->filter($filters);
            $expenseQ->filter($filters);
        }

        return (float) $incomeQ->sum('amount') - (float) $expenseQ->sum('amount');
    }

    /**
     * Totais agregados por categoria: ['category_id' => ['income' => x, 'expense' => y, 'net' => z], ...]
     *
     * @return array<int, array{income: float, expense: float, net: float, category_name: string|null}>
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
        $categoryNames = Category::forUser($userId)->pluck('name', 'id');

        $result = [];
        foreach ($rows as $row) {
            $income = (float) $row->income_total;
            $expense = (float) $row->expense_total;
            $key = (int) $row->category_key;
            $cid = $key === 0 ? null : $key;
            $result[$key] = [
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
                'category_name' => $cid ? ($categoryNames[$cid] ?? '—') : 'Sem categoria',
            ];
        }

        return $result;
    }
}
