<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTransaction extends Model
{
    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_WEEKLY = 'weekly';

    protected $table = 'finance_recurring_transactions';

    protected $fillable = [
        'user_id',
        'source_transaction_id',
        'description',
        'amount',
        'type',
        'category_id',
        'frequency',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'last_run_at',
        'is_active',
        'is_fixed',
        'installments_total',
        'installments_paid',
        'next_run_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_run_at' => 'datetime',
        'next_run_date' => 'date',
        'is_active' => 'boolean',
        'is_fixed' => 'boolean',
        'day_of_month' => 'integer',
        'day_of_week' => 'integer',
        'installments_total' => 'integer',
        'installments_paid' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function generatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurring_transaction_id');
    }

    public function sourceTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'source_transaction_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
