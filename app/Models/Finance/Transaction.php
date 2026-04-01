<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use App\Services\Finance\FinancialSummaryService;
use Carbon\CarbonInterface;
use Database\Factories\Finance\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return TransactionFactory::new();
    }

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    /** Somente resposta/UX; não é persistido nem aceito em input. */
    public const STATUS_OVERDUE = 'overdue';

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
        'payment_status',
        'due_date',
        'description',
        'installment_number',
        'installment_of',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
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

    public function isOverdue(?CarbonInterface $asOf = null): bool
    {
        if ($this->payment_status === self::STATUS_PAID) {
            return false;
        }
        if ($this->due_date === null) {
            return false;
        }

        $today = ($asOf ?? now())->format('Y-m-d');

        return $this->due_date->format('Y-m-d') < $today;
    }

    /**
     * Estado efetivo para API/UX: pendente, pago ou atrasado (atrasado é sempre derivado).
     */
    public function effectivePaymentStatus(): string
    {
        if ($this->payment_status === self::STATUS_PAID) {
            return self::STATUS_PAID;
        }
        if ($this->isOverdue()) {
            return self::STATUS_OVERDUE;
        }

        return self::STATUS_PENDING;
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
            $summaries = app(FinancialSummaryService::class);
            [$start, $end] = $summaries->monthToDateRange($filters['month']);
            $query->whereBetween('transaction_date', [$start, $end]);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['payment_status']) && is_string($filters['payment_status'])) {
            $ps = $filters['payment_status'];
            if ($ps === self::STATUS_OVERDUE) {
                $query->where('payment_status', self::STATUS_PENDING)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->format('Y-m-d'));
            } elseif ($ps === self::STATUS_PENDING) {
                $query->where('payment_status', self::STATUS_PENDING)
                    ->where(function (Builder $q): void {
                        $q->whereNull('due_date')
                            ->orWhereDate('due_date', '>=', now()->format('Y-m-d'));
                    });
            } elseif ($ps === self::STATUS_PAID) {
                $query->where('payment_status', self::STATUS_PAID);
            }
        }

        return $query;
    }
}
