<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Transaction;

final class TransactionApiService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId, array $filters): array
    {
        $items = Transaction::forUser($userId)
            ->with(['category', 'creditCard'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        return $items->map(fn (Transaction $t) => TransactionResource::toArray($t))->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentForUser(int $userId, string $month, int $limit = 8): array
    {
        $filters = ['month' => $month];
        $items = Transaction::forUser($userId)
            ->with(['category', 'creditCard'])
            ->filter($filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $items->map(fn (Transaction $t) => TransactionResource::toArray($t))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $userId, array $data): array
    {
        $data['user_id'] = $userId;
        $t = Transaction::create($data);
        $t->load(['category', 'creditCard']);

        return TransactionResource::toArray($t);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(Transaction $transaction, array $data): array
    {
        $transaction->update($data);
        $transaction->load(['category', 'creditCard']);

        return TransactionResource::toArray($transaction);
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}
