<?php

declare(strict_types=1);

namespace App\Modules\CreditCard\Models;

use App\Modules\Finance\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditCard extends Model
{
    protected $table = 'finance_credit_cards';

    protected $fillable = [
        'user_id',
        'name',
        'credit_limit',
        'closing_day',
        'due_day',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'closing_day' => 'integer',
        'due_day' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'credit_card_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
