<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Database\Factories\Finance\FinanceGoalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceGoal extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return FinanceGoalFactory::new();
    }

    protected $table = 'finance_goals';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target_amount',
        'current_amount',
        'deadline',
        'income_category_id',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'income_category_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
