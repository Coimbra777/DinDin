<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;
use Illuminate\Validation\ValidationException;

/** Regras transversais: pagamento e recorrência só para despesas. */
final class TransactionExpenseRules
{
    /**
     * Normaliza payload já validado (create/update).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeForPersistence(array $data): array
    {
        if (($data['type'] ?? '') !== Transaction::TYPE_EXPENSE) {
            $data['is_recurring'] = false;
            $data['recurrence_day'] = null;

            return $data;
        }

        $recurring = filter_var($data['is_recurring'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['is_recurring'] = $recurring;

        if (! $recurring) {
            $data['recurrence_day'] = null;

            return $data;
        }

        $day = $data['recurrence_day'] ?? null;
        if ($day === null || $day === '') {
            $tx = (string) ($data['transaction_date'] ?? '');
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $tx, $m)) {
                $data['recurrence_day'] = min(31, max(1, (int) $m[3]));
            } else {
                $data['recurrence_day'] = min(31, max(1, (int) now()->format('j')));
            }
        } else {
            $data['recurrence_day'] = min(31, max(1, (int) $day));
        }

        return $data;
    }

    public static function assertExpenseForPayment(Transaction $transaction): void
    {
        if ($transaction->type !== Transaction::TYPE_EXPENSE) {
            throw ValidationException::withMessages([
                'transaction' => ['O pagamento só se aplica a despesas.'],
            ]);
        }
    }
}
