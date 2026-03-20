<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\User;
use Database\Factories\Finance\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return CategoryFactory::new();
    }

    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    /** Subgrupo de despesas (planilha); receitas ficam com group null. */
    public const GROUP_FIXED = 'fixa';

    public const GROUP_VARIABLE = 'variavel';

    public const GROUP_FINANCIAL = 'financeira';

    protected $table = 'finance_categories';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'group',
        'slug',
        'color',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    /**
     * Totais de transações por tipo (receita / despesa) para esta categoria.
     *
     * @return array{income: float, expense: float, net: float}
     */
    public function transactionTotalsByType(): array
    {
        $income = (float) $this->transactions()
            ->where('type', Transaction::TYPE_INCOME)
            ->sum('amount');

        $expense = (float) $this->transactions()
            ->where('type', Transaction::TYPE_EXPENSE)
            ->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (empty($category->slug) && !empty($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
