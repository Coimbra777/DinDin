<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;

/** Serialização JSON de transações para API / dashboard. */
final class TransactionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Transaction $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'transaction_date' => $t->transaction_date->format('Y-m-d'),
            'description' => $t->description,
            'installment_number' => $t->installment_number,
            'installment_of' => $t->installment_of,
            'credit_card_id' => $t->credit_card_id,
            'is_credit_card' => (bool) $t->is_credit_card,
            'category_id' => $t->category_id,
            'category' => $t->category ? [
                'id' => $t->category->id,
                'name' => $t->category->name,
                'color' => $t->category->color,
            ] : null,
            'credit_card' => $t->creditCard ? [
                'id' => $t->creditCard->id,
                'name' => $t->creditCard->name,
            ] : null,
        ];
    }
}
