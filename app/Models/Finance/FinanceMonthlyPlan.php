<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Database\Factories\Finance\FinanceMonthlyPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceMonthlyPlan extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return FinanceMonthlyPlanFactory::new();
    }

    protected $table = 'finance_monthly_plans';

    protected $fillable = [
        'user_id',
        'year_month',
        'planned_expense',
        'planned_saving',
    ];

    protected $casts = [
        'planned_expense' => 'decimal:2',
        'planned_saving' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
