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
        $legacyRecurring = $t->recurring_transaction_id !== null
            && (int) $t->recurring_transaction_id > 0;

        return [
            'id' => $t->id,
            'title' => $t->title,
            'amount' => (float) $t->amount,
            'type' => $t->type,
            'transaction_date' => $t->transaction_date->format('Y-m-d'),
            'payment_status' => $t->effectivePaymentStatus(),
            'due_date' => $t->due_date?->format('Y-m-d'),
            'is_overdue' => $t->isOverdue(),
            'description' => $t->description,
            'installment_number' => $t->installment_number,
            'installment_of' => $t->installment_of,
            'category_id' => $t->category_id,
            'parent_transaction_id' => $t->parent_transaction_id,
            /** Vínculo com regra antiga em finance_recurring_transactions (recorrência automática removida da app). */
            'recurring_transaction_id' => $t->recurring_transaction_id,
            'is_legacy_recurring' => $legacyRecurring,
            'is_recurring' => (bool) $t->is_recurring,
            'recurrence_day' => $t->recurrence_day,
            'category' => $t->category ? [
                'id' => $t->category->id,
                'name' => $t->category->name,
                'color' => $t->category->color,
                'type' => $t->category->type ?? null,
            ] : null,
        ];
    }
}
